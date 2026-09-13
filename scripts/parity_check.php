<?php
/**
 * Parity check against the legacy UI (CLI only, read-only).
 * Prints the computed week rows + totals for one resource and week so
 * they can be diffed against scheduledev's ctrlpnl_resource.php list.
 *
 *   C:\xampp\php\php.exe scripts\parity_check.php <machid|name-fragment> [2026-W37]
 *
 * Also runs internal unit asserts for the time/week math.
 */
if (PHP_SAPI !== 'cli') { exit; }
define('TIMERAPPORT', 1);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Services\ReportService;
use App\Services\ScheduleService;
use App\Services\TimeFormatter;

// ---- unit asserts -----------------------------------------------------
$svc = new ScheduleService();

assert(TimeFormatter::displayMinutes(570, 1.0) === 630);
assert(TimeFormatter::formatTime(570, 1.0) === '10:30');
assert(TimeFormatter::formatTime(570, 0.0) === '09:30');
assert(TimeFormatter::displayToStored(TimeFormatter::displayMinutes(480, 1.0), 1.0) === 480);
assert(TimeFormatter::displayMinutes(1400, 1.0) === 20);              // midnight wrap
assert(TimeFormatter::formatHours(3.0) === '3');
assert(TimeFormatter::formatHours(25.5) === '25.5');
assert(TimeFormatter::formatHours(0.5) === '0.5');
assert(TimeFormatter::parseTime('09:30') === 570);
assert(TimeFormatter::parseTime('24:00') === null);

assert($svc->normalizeIsoWeek('2026-W37') === '2026-W37');
assert($svc->normalizeIsoWeek('2026-W54') === null);
assert($svc->normalizeIsoWeek('banan') === null);
assert($svc->adjacentWeek('2026-W53', 1) === '2027-W01');             // year boundary
assert($svc->adjacentWeek('2027-W01', -1) === '2026-W53');
assert($svc->adjacentWeek('2026-W01', -1) === '2025-W52');   // 2025 has 52 ISO weeks
$b = $svc->weekBounds('2026-W37');
assert(date('Y-m-d D', $b['start']) === '2026-09-07 Mon');
assert(date('Y-m-d D', $b['end']) === '2026-09-13 Sun');
echo "Unit asserts: OK\n\n";

// ---- live comparison --------------------------------------------------
$arg = $argv[1] ?? null;
if ($arg === null) {
    echo "Usage: php scripts/parity_check.php <machid|name-fragment> [YYYY-Www]\n";
    exit(0);
}

$stmt = Database::get()->prepare(
    "SELECT r.machid, r.name, r.status, l.timezone, l.email
     FROM cal_resources r
     LEFT JOIN cal_login l ON l.memberid = r.machid AND l.`keys` = 2
     WHERE r.machid = ? OR r.name LIKE ? LIMIT 5"
);
$stmt->execute([$arg, '%' . $arg . '%']);
$resources = $stmt->fetchAll();
if ($resources === []) {
    echo "No resource matches '$arg'.\n";
    exit(1);
}
if (count($resources) > 1) {
    echo "Multiple matches — pick a machid:\n";
    foreach ($resources as $r) {
        printf("  %s  %-30s status=%s login=%s\n", $r['machid'], \App\Core\View::text($r['name']), $r['status'], $r['email'] ?? '-');
    }
    exit(0);
}

$res = $resources[0];
$offset = $res['timezone'] !== null ? (float)$res['timezone'] : 0.0;
$isoWeek = $svc->normalizeIsoWeek($argv[2] ?? null) ?? $svc->currentIsoWeek();

printf("Resource: %s (%s)  login-tz=%s  hourOffset=%+.1f h\nWeek: %s\n\n",
    \App\Core\View::text($res['name']), $res['machid'], $res['timezone'] ?? 'NO LOGIN ROW', $offset, $isoWeek);

$reports = new ReportService();
$report = $reports->buildWeekReport($res['machid'], $isoWeek, $offset);

printf("%-12s %-30s %-13s %8s %6s  %s\n", 'Datum', 'Kund', 'Tid', 'Timmar', 'Status', 'Adress');
foreach ($report['bookings'] as $bk) {
    printf("%-12s %-30s %5s–%5s %8s %6s  %s\n",
        $bk['display_date'],
        mb_substr($bk['customer'], 0, 30),
        $bk['display_start'], $bk['display_end'],
        $bk['hours'] . ($bk['old_hours_disp'] !== null && $bk['old_hours_disp'] !== $bk['hours'] ? '(' . $bk['old_hours_disp'] . ')' : ''),
        (string)$bk['is_pending'],
        mb_substr($bk['display_address'] . ', ' . $bk['display_city'], 0, 45)
    );
}
printf("\nJobb: %d   Total: %s h   Veckostatus: %s\n",
    $report['jobCount'], $report['totalHours'], $report['weekStatus']);
echo "\nCompare with: http://localhost/scheduledev/ctrlpnl_resource.php?view=2&machid={$res['machid']}&date="
    . date('m-d-Y', $svc->weekBounds($isoWeek)['start']) . "\n";
