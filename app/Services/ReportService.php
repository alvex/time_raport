<?php
namespace App\Services;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

use App\Core\Logger;
use App\Core\Mailer;
use App\Core\View;
use App\Repositories\BookingRepository;

/**
 * The weekly time report, built on the legacy model: there is NO report
 * table. Submitting a report = setting is_pending = 1 on the week's
 * bookings (AdminDB::pending_reservation) + an HTML email to jobbEmail.
 * Approval happens in scheduledev admin (is_pending = 2). History is
 * derived per ISO week from the bookings' is_pending states.
 */
final class ReportService
{
    /**
     * is_pending -> language-neutral status keys. Display labels come
     * from the Translator ('status.<key>'); logic compares keys only.
     */
    public const STATUS_KEYS = [0 => 'draft', 1 => 'submitted', 2 => 'approved'];

    private BookingRepository $bookings;
    private ScheduleService $schedule;

    public function __construct(?BookingRepository $bookings = null, ?ScheduleService $schedule = null)
    {
        $this->bookings = $bookings ?? new BookingRepository();
        $this->schedule = $schedule ?? new ScheduleService($this->bookings);
    }

    public static function bookingStatus(int $isPending): string
    {
        return self::STATUS_KEYS[$isPending] ?? 'none';
    }

    /** Week status key: any draft -> draft; else any submitted -> submitted; else approved. */
    public static function weekStatus(array $bookings): string
    {
        if ($bookings === []) {
            return 'none';
        }
        $states = array_map(static fn($b) => (int)$b['is_pending'], $bookings);
        if (in_array(0, $states, true)) {
            return 'draft';
        }
        if (in_array(1, $states, true)) {
            return 'submitted';
        }
        return 'approved';
    }

    /**
     * Everything the preview/submit pages need for one week.
     *
     * Submission rule (enforced here, in submitReport() and ultimately by
     * the repository's conditional UPDATE — never only in the UI):
     *   Draft (0)     may be edited and submitted.
     *   Submitted (1) is locked and can never be submitted again.
     *   Approved (2)  is locked and can never be submitted again.
     * A week may be submitted only when EVERY booking is a valid draft;
     * a mixed week (drafts alongside submitted/approved rows) is blocked
     * entirely to avoid partial or duplicate reports.
     */
    public function buildWeekReport(string $machid, string $isoWeek, float $hourOffset): array
    {
        $week = $this->schedule->getWeekSchedule($machid, $isoWeek, $hourOffset);
        $week['weekStatus']      = self::weekStatus($week['bookings']);
        $week['allApproved']     = $week['bookings'] !== [] && $week['weekStatus'] === 'approved';
        // Fully submitted (possibly partly approved) — waiting for admin.
        $week['submittedLocked'] = $week['bookings'] !== []
            && $week['draftCount'] === 0 && !$week['allApproved'];
        // Drafts mixed with submitted/approved rows — resubmission blocked.
        $week['mixedLocked']     = $week['draftCount'] > 0 && $week['lockedCount'] > 0;
        $week['canSubmit']       = $week['bookings'] !== []
            && $week['lockedCount'] === 0
            && $week['invalidCount'] === 0;
        return $week;
    }

    /**
     * Submit the week. Allowed ONLY when every booking is a draft
     * (is_pending = 0) with valid times; submitted/approved/mixed weeks
     * are refused before any write.
     *
     * MyISAM gives no real transactions, so the exactly-once guarantee
     * comes from the repository's single conditional UPDATE
     * (WHERE ... AND is_pending = 0, atomic under the table lock) plus a
     * strict row-count check: only the request whose UPDATE flipped ALL
     * expected rows 0 -> 1 counts as the successful first submission and
     * sends the report email. A concurrent/duplicate request matches 0
     * (or fewer) rows, is logged, sends NO email and reports failure —
     * so a double click, re-POST, stale tab or hand-crafted POST can
     * neither resubmit nor duplicate the email. The one-time token and
     * CSRF checks remain as outer layers but are not relied upon.
     */
    public function submitReport(string $machid, string $isoWeek, float $hourOffset, string $employeeName): array
    {
        $report = $this->buildWeekReport($machid, $isoWeek, $hourOffset);

        if ($report['bookings'] === []) {
            return ['ok' => false, 'reason' => 'empty', 'submitted' => 0, 'mailOk' => false];
        }
        if ($report['invalidCount'] > 0) {
            return ['ok' => false, 'reason' => 'invalid', 'submitted' => 0, 'mailOk' => false,
                    'invalidCount' => $report['invalidCount']];
        }
        if ($report['allApproved']) {
            return ['ok' => false, 'reason' => 'approved', 'submitted' => 0, 'mailOk' => false];
        }
        if ($report['mixedLocked']) {
            return ['ok' => false, 'reason' => 'locked_mixed', 'submitted' => 0, 'mailOk' => false];
        }
        if (!$report['canSubmit']) {
            return ['ok' => false, 'reason' => 'locked_submitted', 'submitted' => 0, 'mailOk' => false];
        }

        // Every booking is a draft here; the conditional UPDATE is the
        // authoritative re-check against the database's current state.
        $resids = array_map(static fn($b) => (string)$b['resid'], $report['bookings']);
        $count = $this->bookings->markPending($resids, $machid);

        if ($count !== count($resids)) {
            // Fewer (or zero) rows flipped than expected: a concurrent
            // submission or state change won the race. Nothing more is
            // written and NO email is sent — the winning request already
            // sent the one report email.
            Logger::warn("Report submit race/mismatch for $machid week $isoWeek: "
                . "expected " . count($resids) . " rows, updated $count");
            return ['ok' => false, 'reason' => 'locked_submitted', 'submitted' => $count, 'mailOk' => false];
        }

        $subject = $employeeName . ' Tid Redovisning!';
        $mailOk = Mailer::send(
            (string)tr_config('jobb_email'),
            $subject,
            $this->buildEmailHtml($report, $employeeName)
        );
        if (!$mailOk) {
            Logger::warn("Report mail failed for $machid week $isoWeek (state already submitted)");
        }

        return ['ok' => true, 'reason' => null, 'submitted' => $count, 'mailOk' => $mailOk];
    }

    /**
     * Report history derived from bookings, newest week first.
     * One range query, bucketed per ISO week in PHP.
     */
    public function deriveHistory(string $machid, float $hourOffset, int $weeksBack = 12): array
    {
        $currentWeek = $this->schedule->currentIsoWeek();
        $firstWeek = $this->schedule->adjacentWeek($currentWeek, -($weeksBack - 1));
        $start = $this->schedule->weekBounds($firstWeek)['start'];
        $end = $this->schedule->weekBounds($currentWeek)['end'];

        $rows = $this->bookings->findForResourceBetween($machid, $start, $end);
        $buckets = [];
        foreach ($rows as $r) {
            $ts = (int)$r['start_date'];
            $key = date('o', $ts) . '-W' . date('W', $ts);
            $buckets[$key][] = $r;
        }

        $weeks = [];
        for ($i = 0; $i < $weeksBack; $i++) {
            $isoWeek = $this->schedule->adjacentWeek($currentWeek, -$i);
            $bucket = $buckets[$isoWeek] ?? [];
            $minutes = $this->schedule->calculateWeekMinutes($bucket);
            $weeks[] = [
                'isoWeek'    => $isoWeek,
                'weekNumber' => $this->schedule->weekNumber($isoWeek),
                'rangeLabel' => $this->schedule->weekRangeLabel($isoWeek),
                'jobCount'   => count($bucket),
                'totalHours' => TimeFormatter::formatHours($minutes / 60),
                'status'     => self::weekStatus($bucket),
            ];
        }
        return $weeks;
    }

    /**
     * History for the weeks of ONE month, newest first: every ISO week
     * that overlaps the month (a week spanning a month boundary belongs
     * to both months). Same row shape as deriveHistory().
     */
    public function deriveMonthHistory(string $machid, float $hourOffset, string $month): array
    {
        $bounds = $this->schedule->monthBounds($month);
        // Sista dagen i månaden, mitt på dagen — robust mot DST-skiften.
        $lastDay = $bounds['end'] - 43200;
        $firstWeek = date('o', $bounds['start']) . '-W' . date('W', $bounds['start']);
        $lastWeek = date('o', $lastDay) . '-W' . date('W', $lastDay);

        $start = $this->schedule->weekBounds($firstWeek)['start'];
        $end = $this->schedule->weekBounds($lastWeek)['end'];
        $rows = $this->bookings->findForResourceBetween($machid, $start, $end);
        $buckets = [];
        foreach ($rows as $r) {
            $ts = (int)$r['start_date'];
            $key = date('o', $ts) . '-W' . date('W', $ts);
            $buckets[$key][] = $r;
        }

        $weeks = [];
        $isoWeek = $lastWeek;
        while (true) {
            $bucket = $buckets[$isoWeek] ?? [];
            $minutes = $this->schedule->calculateWeekMinutes($bucket);
            $weeks[] = [
                'isoWeek'    => $isoWeek,
                'weekNumber' => $this->schedule->weekNumber($isoWeek),
                'rangeLabel' => $this->schedule->weekRangeLabel($isoWeek),
                'jobCount'   => count($bucket),
                'totalHours' => TimeFormatter::formatHours($minutes / 60),
                'status'     => self::weekStatus($bucket),
            ];
            if ($isoWeek === $firstWeek) {
                break;
            }
            $isoWeek = $this->schedule->adjacentWeek($isoWeek, -1);
        }
        return $weeks;
    }

    /**
     * Monthly hours summary for the history page. Read-only.
     *
     * Same basis as the weekly history totals: every non-blackout
     * booking for the authenticated resource, regardless of report
     * status (draft/submitted/approved all count). The sum is BOOKED
     * (scheduled) time — (endtime − starttime)/60 from the raw stored
     * minute columns, negative (invalid) rows clamped to 0 exactly like
     * calculateWeekMinutes — not actual check-in/check-out time, which
     * this system does not record. The display hourOffset shifts start
     * AND end equally, so durations are offset-independent.
     *
     * The month window is bookings with start_date in
     * [first day 00:00, first day of next month 00:00) — see
     * BookingRepository::findDurationsForResourceStartingIn (no JOIN,
     * each booking counted exactly once).
     */
    public function monthSummary(string $machid, string $month): array
    {
        $bounds = $this->schedule->monthBounds($month);
        $rows = $this->bookings->findDurationsForResourceStartingIn($machid, $bounds['start'], $bounds['end']);
        $minutes = $this->schedule->calculateWeekMinutes($rows);
        [$year, $num] = sscanf($month, '%d-%d');
        return [
            'month'        => $month,
            'yearNum'      => $year,
            'monthNum'     => $num,
            'label'        => $this->schedule->monthLabel($month),
            'jobCount'     => count($rows),
            'totalMinutes' => $minutes,
            'totalHours'   => TimeFormatter::formatHours($minutes / 60),
            'prevMonth'    => $this->schedule->adjacentMonth($month, -1),
            'nextMonth'    => $this->schedule->adjacentMonth($month, 1),
            'isCurrent'    => $month === $this->schedule->currentMonth(),
            'years'        => $this->yearOptions($machid, $year),
        ];
    }

    /**
     * Year options for the month picker, newest first: the resource's
     * own booking span, always including the current and the selected
     * year so the select can mirror any valid URL state.
     */
    private function yearOptions(string $machid, int $selectedYear): array
    {
        $span = $this->bookings->startDateSpan($machid);
        $cur = (int)date('Y');
        $min = $span !== null ? (int)date('Y', $span['min']) : $cur;
        $max = $span !== null ? (int)date('Y', $span['max']) : $cur;
        $min = max(2000, min($min, $cur, $selectedYear));
        $max = min(2100, max($max, $cur, $selectedYear));
        return range($max, $min);
    }

    /**
     * The report email: same information as the legacy preview table
     * (templates/resources.template.php:261) — date, customer, address,
     * hours (with original hours when adjusted), comment, week total —
     * with all values recomputed server-side and escaped.
     *
     * ALWAYS SWEDISH regardless of the employee's UI language: the mail
     * goes to the administrative approval process at jobb@hemfresh.se,
     * which works in Swedish (legacy behavior). The week range label is
     * therefore rebuilt with the sv locale explicitly.
     */
    private function buildEmailHtml(array $report, string $employeeName): string
    {
        $rangeLabelSv = $this->schedule->weekRangeLabel($report['isoWeek'], 'sv');
        $e = static fn(?string $s) => View::e($s);
        $rows = '';
        foreach ($report['bookings'] as $b) {
            $hours = $e($b['hours']);
            if ($b['old_hours_disp'] !== null && $b['old_hours_disp'] !== $b['hours']) {
                $hours .= ' (' . $e($b['old_hours_disp']) . ')';
            }
            $rows .= '<tr>'
                . '<td>' . $e($b['display_date']) . '</td>'
                . '<td>' . $e($b['customer']) . '</td>'
                . '<td>' . $e($b['display_address'] . ($b['display_city'] !== '' ? ', ' . $b['display_city'] : '')) . '</td>'
                . '<td>' . $e($b['display_start']) . '–' . $e($b['display_end']) . '</td>'
                . '<td style="text-align:right">' . $hours . '</td>'
                . '<td>' . $e($b['comment']) . '</td>'
                . "</tr>\n";
        }
        return '<html><body>'
            . '<h2>' . $e($employeeName) . ' – Tidrapport vecka ' . $e((string)$report['weekNumber'])
            . ' (' . $e($rangeLabelSv) . ')</h2>'
            . '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse">'
            . '<tr><th>Datum</th><th>Kund</th><th>Adress</th><th>Tid</th><th>Timmar</th><th>Kommentar</th></tr>'
            . "\n" . $rows
            . '<tr><td colspan="4" style="text-align:right"><b>Totalt:</b></td>'
            . '<td style="text-align:right"><b>' . $e($report['totalHours']) . '</b></td><td></td></tr>'
            . '</table>'
            . '<p>Skickad från HemFresh TimeRapport ' . date('Y-m-d H:i') . '</p>'
            . '</body></html>';
    }
}
