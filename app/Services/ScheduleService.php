<?php
namespace App\Services;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

use App\Core\View;
use App\Repositories\BookingRepository;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Week math and schedule assembly.
 *
 * Weeks are ISO-8601 (Monday start, ISO year 'o' — year-boundary safe;
 * legacy pairs 'Y' with 'W' which is wrong around New Year). The week
 * range matches legacy MyCalendar: Monday 00:00 .. Sunday 00:00, and the
 * booking query filters start_date >= monday AND end_date <= sunday,
 * exactly like DBEngine::get_resource_reservations().
 */
final class ScheduleService
{
    private BookingRepository $bookings;

    public function __construct(?BookingRepository $bookings = null)
    {
        $this->bookings = $bookings ?? new BookingRepository();
    }

    /** "2026-W37" for today. */
    public function currentIsoWeek(): string
    {
        return date('o') . '-W' . date('W');
    }

    /** Validate and normalize a week id like "2026-W37"; null if invalid. */
    public function normalizeIsoWeek(?string $week): ?string
    {
        if ($week === null || !preg_match('/^(\d{4})-W(\d{2})$/', $week, $m)) {
            return null;
        }
        $year = (int)$m[1];
        $num = (int)$m[2];
        if ($year < 2000 || $year > 2100 || $num < 1 || $num > 53) {
            return null;
        }
        // Reject week 53 in years that only have 52 ISO weeks.
        $d = (new DateTimeImmutable('now', new DateTimeZone('Europe/Stockholm')))->setISODate($year, $num);
        if ((int)$d->format('o') !== $year || (int)$d->format('W') !== $num) {
            return null;
        }
        return sprintf('%04d-W%02d', $year, $num);
    }

    /** Monday 00:00 and Sunday 00:00 unix timestamps for an ISO week. */
    public function weekBounds(string $isoWeek): array
    {
        [$year, $num] = sscanf($isoWeek, '%d-W%d');
        $monday = (new DateTimeImmutable('now', new DateTimeZone('Europe/Stockholm')))
            ->setISODate($year, $num)
            ->setTime(0, 0, 0);
        $sunday = $monday->modify('+6 days');
        return ['start' => $monday->getTimestamp(), 'end' => $sunday->getTimestamp()];
    }

    public function adjacentWeek(string $isoWeek, int $delta): string
    {
        [$year, $num] = sscanf($isoWeek, '%d-W%d');
        $d = (new DateTimeImmutable('now', new DateTimeZone('Europe/Stockholm')))
            ->setISODate($year, $num)
            ->modify(($delta >= 0 ? '+' : '') . (7 * $delta) . ' days');
        return $d->format('o') . '-W' . $d->format('W');
    }

    public function weekNumber(string $isoWeek): int
    {
        return (int)substr($isoWeek, 6);
    }

    /** "7–13 september 2026" style range label for the week. */
    public function weekRangeLabel(string $isoWeek): string
    {
        $b = $this->weekBounds($isoWeek);
        static $months = [1 => 'januari', 'februari', 'mars', 'april', 'maj', 'juni',
            'juli', 'augusti', 'september', 'oktober', 'november', 'december'];
        $sm = (int)date('n', $b['start']);
        $em = (int)date('n', $b['end']);
        $sd = (int)date('j', $b['start']);
        $ed = (int)date('j', $b['end']);
        if ($sm === $em) {
            return $sd . '–' . $ed . ' ' . $months[$em] . ' ' . date('Y', $b['end']);
        }
        return $sd . ' ' . $months[$sm] . ' – ' . $ed . ' ' . $months[$em] . ' ' . date('Y', $b['end']);
    }

    /** Minutes for one booking, from raw stored minute columns. */
    public function calculateBookingMinutes(array $booking): int
    {
        return (int)$booking['endtime'] - (int)$booking['starttime'];
    }

    public function calculateWeekMinutes(array $bookings): int
    {
        $total = 0;
        foreach ($bookings as $b) {
            $total += $this->calculateBookingMinutes($b);
        }
        return $total;
    }

    /**
     * Week schedule for a resource: bookings enriched for display and
     * grouped per day (Monday..Sunday), plus totals.
     */
    public function getWeekSchedule(string $machid, string $isoWeek, float $hourOffset): array
    {
        $bounds = $this->weekBounds($isoWeek);
        $rows = $this->bookings->findForResourceBetween($machid, $bounds['start'], $bounds['end']);
        $bookings = array_map(fn(array $r) => $this->enrich($r, $hourOffset), $rows);

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $ts = strtotime("+$i days", $bounds['start']);
            $days[date('Y-m-d', $ts)] = [
                'ts'       => $ts,
                'heading'  => TimeFormatter::formatDayHeading($ts),
                'isToday'  => date('Y-m-d', $ts) === date('Y-m-d'),
                'bookings' => [],
                'minutes'  => 0,
            ];
        }
        foreach ($bookings as $b) {
            $key = date('Y-m-d', (int)$b['start_date']);
            if (isset($days[$key])) {
                $days[$key]['bookings'][] = $b;
                $days[$key]['minutes'] += $b['minutes'];
            }
        }

        $totalMinutes = $this->calculateWeekMinutes($rows);
        return [
            'isoWeek'      => $isoWeek,
            'weekNumber'   => $this->weekNumber($isoWeek),
            'rangeLabel'   => $this->weekRangeLabel($isoWeek),
            'days'         => $days,
            'bookings'     => $bookings,
            'totalMinutes' => $totalMinutes,
            'totalHours'   => TimeFormatter::formatHours($totalMinutes / 60),
            'jobCount'     => count($bookings),
        ];
    }

    /** Today's bookings for the dashboard. */
    public function getTodaySchedule(string $machid, float $hourOffset): array
    {
        $midnight = strtotime('today');
        $rows = $this->bookings->findForResourceBetween($machid, $midnight, $midnight);
        return array_map(fn(array $r) => $this->enrich($r, $hourOffset), $rows);
    }

    public function getBooking(string $resid, string $machid, float $hourOffset): ?array
    {
        $row = $this->bookings->findOneForResource($resid, $machid);
        return $row === null ? null : $this->enrich($row, $hourOffset);
    }

    /** Attach display fields to a raw booking row. */
    private function enrich(array $r, float $hourOffset): array
    {
        $minutes = $this->calculateBookingMinutes($r);
        $r['minutes']       = $minutes;
        $r['hours']         = TimeFormatter::formatHours($minutes / 60);
        $r['display_start'] = TimeFormatter::formatTime((int)$r['starttime'], $hourOffset);
        $r['display_end']   = TimeFormatter::formatTime((int)$r['endtime'], $hourOffset);
        $r['display_date']  = TimeFormatter::formatDate((int)$r['start_date']);
        $r['day_heading']   = TimeFormatter::formatDayHeading((int)$r['start_date']);
        $r['customer']      = trim(View::text($r['fname']) . ' ' . View::text($r['lname']));
        $r['display_address'] = trim(View::text($r['address']), " \t,");
        $r['display_city']  = View::text($r['city']);
        $r['comment']       = View::text($r['summary']);
        $r['old_hours_disp'] = ($r['old_hours'] !== null && (float)$r['old_hours'] > 0)
            ? TimeFormatter::formatHours((float)$r['old_hours'])
            : null;
        $r['status']        = ReportService::bookingStatus((int)$r['is_pending']);
        $r['editable']      = (int)$r['is_pending'] < 2;
        return $r;
    }
}
