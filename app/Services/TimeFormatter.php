<?php
namespace App\Services;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

/**
 * Replicates the legacy display-time math (scheduledev lib/Time.class.php).
 *
 * Stored times are minutes past midnight in "server time"; the legacy UI
 * shifts them by hourOffset = cal_login.timezone - conf timezone (0) when
 * rendering. Employees typically have timezone = 1, so displayed time =
 * stored + 60 minutes. The shift is display-only — stored values are
 * never adjusted. displayToStored() is the inverse, used when an employee
 * submits adjusted times through a form.
 */
final class TimeFormatter
{
    /** Stored minutes -> displayed minutes. */
    public static function displayMinutes(int $storedMinutes, float $offset): int
    {
        return (($storedMinutes + (int)round(60 * $offset)) % 1440 + 1440) % 1440;
    }

    /** Displayed minutes -> stored minutes (inverse of displayMinutes). */
    public static function displayToStored(int $displayMinutes, float $offset): int
    {
        return (($displayMinutes - (int)round(60 * $offset)) % 1440 + 1440) % 1440;
    }

    /** Stored minutes -> "HH:MM" (24h, like legacy timeFormat = 24). */
    public static function formatTime(int $storedMinutes, float $offset): string
    {
        $m = self::displayMinutes($storedMinutes, $offset);
        return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }

    /** Parse "HH:MM" into minutes past midnight, null if invalid. */
    public static function parseTime(string $time): ?int
    {
        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', trim($time), $m)) {
            return null;
        }
        return (int)$m[1] * 60 + (int)$m[2];
    }

    /**
     * Hours like legacy: "25.5", but whole numbers without decimals ("3").
     * (cpanel.template.php:581-585 strips ".0".)
     */
    public static function formatHours(float $hours): string
    {
        $s = rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.');
        return $s === '' ? '0' : $s;
    }

    /** Booking date like the legacy list: "2026/09/07". */
    public static function formatDate(int $ts): string
    {
        return date('Y/m/d', $ts);
    }

    /** Swedish day heading, e.g. "Måndag 7 september". */
    public static function formatDayHeading(int $ts): string
    {
        static $days = ['Söndag', 'Måndag', 'Tisdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lördag'];
        static $months = [1 => 'januari', 'februari', 'mars', 'april', 'maj', 'juni',
            'juli', 'augusti', 'september', 'oktober', 'november', 'december'];
        return $days[(int)date('w', $ts)] . ' ' . (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts)];
    }
}
