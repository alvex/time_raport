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
    public const STATUS_LABELS = [0 => 'Ej rapporterad', 1 => 'Inskickad', 2 => 'Godkänd'];

    private BookingRepository $bookings;
    private ScheduleService $schedule;

    public function __construct(?BookingRepository $bookings = null, ?ScheduleService $schedule = null)
    {
        $this->bookings = $bookings ?? new BookingRepository();
        $this->schedule = $schedule ?? new ScheduleService($this->bookings);
    }

    public static function bookingStatus(int $isPending): string
    {
        return self::STATUS_LABELS[$isPending] ?? 'Okänd';
    }

    /** Week status: any draft -> Utkast; else any submitted -> Inskickad; else Godkänd. */
    public static function weekStatus(array $bookings): string
    {
        if ($bookings === []) {
            return '–';
        }
        $states = array_map(static fn($b) => (int)$b['is_pending'], $bookings);
        if (in_array(0, $states, true)) {
            return self::STATUS_LABELS[0];
        }
        if (in_array(1, $states, true)) {
            return self::STATUS_LABELS[1];
        }
        return self::STATUS_LABELS[2];
    }

    /** Everything the preview/submit pages need for one week. */
    public function buildWeekReport(string $machid, string $isoWeek, float $hourOffset): array
    {
        $week = $this->schedule->getWeekSchedule($machid, $isoWeek, $hourOffset);
        $submittable = array_values(array_filter(
            $week['bookings'],
            static fn($b) => (int)$b['is_pending'] < 2
        ));
        $week['weekStatus']   = self::weekStatus($week['bookings']);
        $week['submittable']  = $submittable;
        // Invalid working times (invalidCount, set by ScheduleService)
        // block submission until corrected — enforced server-side here
        // and in submitReport(), not only in the UI.
        $week['canSubmit']    = $submittable !== [] && $week['invalidCount'] === 0;
        $week['allApproved']  = $week['bookings'] !== [] && $submittable === [];
        return $week;
    }

    /**
     * Submit the week: mark the eligible bookings is_pending = 1 and send
     * the report email (log mode in dev). Server-side recomputation only —
     * no client-supplied hours are trusted. Resubmitting a week whose rows
     * are is_pending = 1 is allowed (legacy behavior, e.g. after edits);
     * a fully approved week is refused.
     *
     * Legacy ordering is kept: state is committed first, then the mail is
     * sent; a mail failure is logged but does not roll back the state
     * (Admin.class.php:332-340).
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
        if (!$report['canSubmit']) {
            return ['ok' => false, 'reason' => 'approved', 'submitted' => 0, 'mailOk' => false];
        }

        $resids = array_map(static fn($b) => (string)$b['resid'], $report['submittable']);

        $this->bookings->beginTransaction();
        try {
            // Re-check under lock (no-op locks on MyISAM; the UPDATE's
            // "is_pending < 2" condition is the atomic guard there).
            $locked = $this->bookings->lockForUpdate($resids, $machid);
            $eligible = array_map(
                static fn($r) => (string)$r['resid'],
                array_filter($locked, static fn($r) => (int)$r['is_pending'] < 2)
            );
            if ($eligible === []) {
                $this->bookings->rollBack();
                return ['ok' => false, 'reason' => 'approved', 'submitted' => 0, 'mailOk' => false];
            }
            $count = $this->bookings->markPending($eligible, $machid);
            $this->bookings->commit();
        } catch (\Throwable $e) {
            $this->bookings->rollBack();
            throw $e;
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
     * The report email: same information as the legacy preview table
     * (templates/resources.template.php:261) — date, customer, address,
     * hours (with original hours when adjusted), comment, week total —
     * with all values recomputed server-side and escaped.
     */
    private function buildEmailHtml(array $report, string $employeeName): string
    {
        $e = static fn(?string $s) => View::e($s);
        $rows = '';
        foreach ($report['bookings'] as $b) {
            $hours = $e($b['hours']);
            if ($b['old_hours_disp'] !== null && (int)$b['is_pending'] === 1 && $b['old_hours_disp'] !== $b['hours']) {
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
            . ' (' . $e($report['rangeLabel']) . ')</h2>'
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
