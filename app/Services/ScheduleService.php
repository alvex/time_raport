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

    /**
     * Localized week range label, e.g. "7–13 september 2026" (sv),
     * "7–13 de septiembre de 2026" (es), "7–13 сентября 2026" (ru).
     * $locale forces a language (the report email passes 'sv').
     * No intl extension: month names live in the language files.
     */
    public function weekRangeLabel(string $isoWeek, ?string $locale = null): string
    {
        $b = $this->weekBounds($isoWeek);
        $sm = (int)date('n', $b['start']);
        $em = (int)date('n', $b['end']);
        $sd = (int)date('j', $b['start']);
        $ed = (int)date('j', $b['end']);
        $year = date('Y', $b['end']);
        if ($sm === $em) {
            return t('date.range_same', [
                'd1' => $sd, 'd2' => $ed, 'month' => t("date.month.$em", [], $locale), 'year' => $year,
            ], $locale);
        }
        return t('date.range_cross', [
            'd1' => $sd, 'm1' => t("date.month.$sm", [], $locale),
            'd2' => $ed, 'm2' => t("date.month.$em", [], $locale), 'year' => $year,
        ], $locale);
    }

    /** Minutes for one booking, from raw stored minute columns. */
    public function calculateBookingMinutes(array $booking): int
    {
        return (int)$booking['endtime'] - (int)$booking['starttime'];
    }

    /**
     * Week total. Zero-duration rows (cancelled visits) contribute 0 by
     * nature; invalid rows (end before start) are clamped so they never
     * subtract from the total — they also block report submission.
     */
    public function calculateWeekMinutes(array $bookings): int
    {
        $total = 0;
        foreach ($bookings as $b) {
            $total += max(0, $this->calculateBookingMinutes($b));
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
            'invalidCount' => count(array_filter($bookings, static fn($b) => $b['invalid'])),
            'draftCount'   => count(array_filter($bookings, static fn($b) => (int)$b['is_pending'] === 0)),
            'lockedCount'  => count(array_filter($bookings, static fn($b) => (int)$b['is_pending'] >= 1)),
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

    /**
     * Google Maps directions URL for a customer address, or null when the
     * address is unusable (empty or no letters/digits — legacy rows can
     * hold "," or "."). City alone is not a destination; it is only
     * appended to a real street address. The inputs are expected to be
     * View::text()-normalized already (å/ä/ö, UTF-8-in-latin1 and HTML
     * entities resolved), so rawurlencode() gets clean UTF-8.
     * No coordinates are fetched or stored — Google Maps uses the
     * device's own position as the starting point.
     */
    public static function mapsUrl(string $address, string $city, ?string $travelmode = null): ?string
    {
        $address = trim($address, " \t,.");
        if ($address === '' || !preg_match('/[\p{L}\d]/u', $address)) {
            return null;
        }
        $city = trim($city, " \t,.");
        $destination = $city === '' ? $address : $address . ', ' . $city;
        $url = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($destination);
        // Whitelisted Google Maps travel modes only.
        if (in_array($travelmode, ['driving', 'walking', 'bicycling', 'transit'], true)) {
            $url .= '&travelmode=' . $travelmode;
        }
        return $url;
    }

    /** Attach display fields to a raw booking row. */
    private function enrich(array $r, float $hourOffset): array
    {
        $minutes = $this->calculateBookingMinutes($r);
        // Invalid working time = END BEFORE START only. Zero duration
        // (start == end) is a legitimate state — e.g. the customer
        // cancelled the cleaning — and is reported as 0 h. Submitted and
        // approved rows are fully locked and can no longer be corrected
        // by the employee, so only DRAFT rows (is_pending = 0) are
        // flagged; the flag blocks report submission.
        $invalid = $minutes < 0 && (int)$r['is_pending'] === 0;
        $r['minutes']       = $minutes;
        $r['invalid']       = $invalid;   // views translate t('invalid.reason_negative')
        $r['hours']         = TimeFormatter::formatHours(max(0, $minutes) / 60);
        $r['display_start'] = TimeFormatter::formatTime((int)$r['starttime'], $hourOffset);
        $r['display_end']   = TimeFormatter::formatTime((int)$r['endtime'], $hourOffset);
        $r['display_date']  = TimeFormatter::formatDate((int)$r['start_date']);
        $r['day_heading']   = TimeFormatter::formatDayHeading((int)$r['start_date']);
        $r['customer']      = trim(View::text($r['fname']) . ' ' . View::text($r['lname']));
        $r['customer_no']   = (string)$r['customer_id'];
        // cal_login.keys på KUNDRADEN: 1 = kundens fysiska nyckel finns hos
        // HemFresh och ska tas med (legacy mycalendar.template.php:381).
        // Samma kolumn markerar anställd-konton med 2 — därav strikt === 1.
        $r['has_key']       = (int)($r['customer_keys'] ?? 0) === 1;
        $r['display_address'] = trim(View::text($r['address']), " \t,");
        $r['display_city']  = View::text($r['city']);
        $r['comment']       = View::text($r['summary']);
        $r['maps_url']      = self::mapsUrl($r['display_address'], $r['display_city']);
        // Dashboard's "Starta navigation": same destination, car mode preset.
        $r['maps_url_driving'] = self::mapsUrl($r['display_address'], $r['display_city'], 'driving');
        $r['old_hours_disp'] = ($r['old_hours'] !== null && (float)$r['old_hours'] > 0)
            ? TimeFormatter::formatHours((float)$r['old_hours'])
            : null;
        // 'invalid' outranks the report status visually. Language-neutral
        // key; views translate via t('status.' . status_key).
        // Editable = DRAFT ONLY: a submitted (1) or approved (2) row is
        // fully locked for the employee.
        $r['status_key']    = $invalid ? 'invalid' : ReportService::bookingStatus((int)$r['is_pending']);
        $r['editable']      = (int)$r['is_pending'] === 0;
        return $r;
    }
}
