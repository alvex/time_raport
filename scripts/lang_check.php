<?php
/**
 * Language-module verification (CLI only, read-only against the DB).
 *
 *   C:\xampp\php\php.exe scripts\lang_check.php
 *
 * Covers: locale selection + fallback, cookie manipulation, key parity
 * across sv/es/ru, parameter interpolation, plural rules (incl. Russian
 * 1/2-4/5+), open-redirect hardening of the language switch, UTF-8
 * rendering of Spanish/Russian, all views rendering in all locales, and
 * that numbers (week totals, times) are identical across languages.
 */
if (PHP_SAPI !== 'cli') { exit; }
define('TIMERAPPORT', 1);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\LangController;
use App\Core\Translator;
use App\Core\View;
use App\Services\ReportService;
use App\Services\ScheduleService;

session_start();
$_SESSION['resource_id'] = 'sc15dc94520993a8'; // Katya Gruz (läsning)
$_SESSION['name'] = 'Katya';
$_SESSION['hour_offset'] = 0.0;
$_SESSION['_csrf'] = str_repeat('a', 64);

$fail = 0;
$check = function (string $l, bool $ok) use (&$fail) { echo ($ok?'PASS':'FAIL').": $l\n"; if(!$ok) $fail++; };

// 1. Val av språk + 2. ogiltiga värden -> svenska
foreach (['sv', 'es', 'ru'] as $l) {
    Translator::persist($l);
    $check("locale '$l' kan väljas", Translator::locale() === $l);
}
Translator::persist('de');
$check("ogiltigt 'de' → sv", Translator::locale() === 'sv');
Translator::persist('../../etc/passwd');
$check('path-traversal-försök → sv', Translator::locale() === 'sv');

// 3. Manipulerad cookie ignoreras
unset($_SESSION['lang']);
$_COOKIE[Translator::COOKIE] = "ru'; DROP TABLE x;--";
Translator::init();
$check('manipulerad cookie → sv', Translator::locale() === 'sv');
$_COOKIE[Translator::COOKIE] = 'ru';
Translator::init();
$check('giltig cookie ru följs (persistens utan session)', Translator::locale() === 'ru');
unset($_COOKIE[Translator::COOKIE]);

// 4. Nyckelparitet (plural-suffix normaliseras; alla pluralbaser kräver .other)
$files = [];
foreach (['sv', 'es', 'ru'] as $l) {
    $files[$l] = require dirname(__DIR__) . "/app/Lang/$l.php";
}
$base = function (array $a): array {
    $keys = [];
    foreach (array_keys($a) as $k) {
        $keys[preg_replace('/\.(one|few|many|other)$/', '.*', $k)] = true;
    }
    ksort($keys);
    return array_keys($keys);
};
$svKeys = $base($files['sv']);
foreach (['es', 'ru'] as $l) {
    $diff = array_merge(array_diff($svKeys, $base($files[$l])), array_diff($base($files[$l]), $svKeys));
    $check("nyckelparitet sv↔$l", $diff === []);
    if ($diff !== []) { echo "   diff: " . implode(', ', $diff) . "\n"; }
}
foreach ($files as $l => $a) {
    $bad = [];
    foreach (array_keys($a) as $k) {
        if (preg_match('/^(.*)\.(one|few|many)$/', $k, $m) && !isset($a[$m[1] . '.other'])) {
            $bad[] = $m[1];
        }
    }
    $check("$l: alla pluralbaser har .other", $bad === []);
}

// 5. Saknad nyckel → svensk fallback / nyckeln själv
Translator::persist('ru');
$check('okänd nyckel returnerar nyckeln', t('finns.inte.alls') === 'finns.inte.alls');
$check('forcerad sv-hämtning', t('status.approved', [], 'sv') === 'Godkänd');

// 6. Parametrar
Translator::persist('es');
$check('parameter {name}', t('dash.hello', ['name' => 'María']) === 'Hola, María');
$check('parameter {week}', t('week.title', ['week' => 37]) === 'Semana 37');

// Plural: sv/es one/other, ru 1/2-4/5+/decimal
Translator::persist('sv');
$check('sv 1 timme', tp('unit.hours', 1) === 'timme');
$check('sv 4 timmar', tp('unit.hours', 4) === 'timmar');
Translator::persist('es');
$check('es 1 hora', tp('unit.hours', 1) === 'hora');
$check('es 2 horas', tp('unit.hours', 2) === 'horas');
Translator::persist('ru');
$check('ru 1 час', tp('unit.hours', 1) === 'час');
$check('ru 3 часа', tp('unit.hours', 3) === 'часа');
$check('ru 5 часов', tp('unit.hours', 5) === 'часов');
$check('ru 21 час', tp('unit.hours', 21) === 'час');
$check('ru 11 часов', tp('unit.hours', 11) === 'часов');
$check('ru 3.5 часа (decimal)', tp('unit.hours', 3.5) === 'часа');
$check('ru mening {n}: 2 смены', str_contains(tp('report.blocked', 2), '2 смены'));
$check('ru mening {n}: 5 смен', str_contains(tp('report.blocked', 5), '5 смен'));

// 8. Open redirect-härdning (7. CSRF täcks av central dispatch — HTTP-test separat)
$cases = [
    ['?page=week&week=2026-W37', 'index.php?page=week&week=2026-W37', 'intern retur behålls'],
    ['index.php?page=job&resid=abc', 'index.php?page=job&resid=abc', 'index.php-form behålls'],
    ['https://evil.example/x', 'index.php?page=dashboard', 'absolut URL avvisas'],
    ['//evil.example/x', 'index.php?page=dashboard', 'schemalös extern URL avvisas'],
    ['?page=report_submit', 'index.php?page=dashboard', 'POST-route avvisas som retur'],
    ['?page=okand', 'index.php?page=dashboard', 'okänd sida avvisas'],
    ['/annan/app?page=week', 'index.php?page=dashboard', 'främmande path avvisas'],
    [null, 'index.php?page=dashboard', 'saknad retur → dashboard'],
];
foreach ($cases as [$in, $want, $label]) {
    $got = LangController::safeReturnUrl($in);
    $check("redirect: $label", $got === $want);
    if ($got !== $want) { echo "   in=" . var_export($in, true) . " got=$got\n"; }
}
$_SESSION_BACKUP = $_SESSION; unset($_SESSION['resource_id']);
$check('utloggad → login som fallback', LangController::safeReturnUrl('https://evil.example') === 'index.php?page=login');
$_SESSION = $_SESSION_BACKUP;

// 10-12. Rendera alla vyer på alla språk; siffror oförändrade
$machid = 'sc15dc94520993a8';
$schedule = new ScheduleService();
$reports = new ReportService();
$renders = [];
foreach (['sv', 'es', 'ru'] as $l) {
    Translator::persist($l);
    $week = $reports->buildWeekReport($machid, '2026-W37', 0.0);
    $today = $schedule->getTodaySchedule($machid, 0.0);
    $pages = [
        'login' => fn() => View::render('login', ['error' => t('login.error_invalid')], null),
        'dashboard' => fn() => View::render('dashboard', ['name' => 'Katya', 'week' => $week, 'today' => $today, 'nextJob' => $today[0] ?? null]),
        'week' => fn() => View::render('week', ['week' => $week, 'prevWeek' => '2026-W36', 'nextWeek' => '2026-W38', 'currentWeek' => $schedule->currentIsoWeek(), 'notice' => null, 'warning' => null]),
        'job' => fn() => View::render('job', ['b' => $week['bookings'][0], 'backWeek' => '2026-W37', 'notice' => null, 'warning' => null]),
        'report_preview' => fn() => View::render('report_preview', ['report' => $week, 'onceToken' => 'tok', 'notice' => null, 'warning' => null]),
        'history' => fn() => View::render('history', ['month' => $reports->monthSummary($machid, '2026-09'), 'weeks' => $reports->deriveHistory($machid, 0.0, 12), 'notice' => null, 'warning' => null]),
        'error' => fn() => View::render('error', ['title' => t('err.404_title'), 'message' => t('err.404_msg')]),
    ];
    foreach ($pages as $name => $fn) {
        ob_start();
        try { $fn(); $renders[$l][$name] = ob_get_clean(); }
        catch (Throwable $e) { ob_end_clean(); $renders[$l][$name] = 'EXC: ' . $e->getMessage(); }
        $check("render $name [$l]", !str_starts_with($renders[$l][$name], 'EXC:') && strlen($renders[$l][$name]) > 300);
    }
    $check("html lang=\"$l\" sätts", str_contains($renders[$l]['week'] ?? '', 'lang="' . $l . '"'));
}
$check('es: accenttecken i UI', str_contains($renders['es']['week'], 'Semana') && str_contains($renders['es']['login'], 'Contraseña'));
$check('ru: kyrilliska i UI', str_contains($renders['ru']['week'], 'Неделя') && str_contains($renders['ru']['week'], 'сентября'));
$check('sv-data (kundnamn) oförändrad på ru-sida', str_contains($renders['ru']['week'], 'Engblom'));
$nums = fn(string $h) => [substr_count($h, '13:00–17:00'), substr_count($h, '>11 '), substr_count($h, '2026')];
$check('tider/totaler identiska sv↔es↔ru',
    $nums($renders['sv']['week']) === $nums($renders['es']['week'])
    && $nums($renders['sv']['week']) === $nums($renders['ru']['week']));
$check('rapportmailets veckoetikett förblir svensk',
    $schedule->weekRangeLabel('2026-W37', 'sv') === '7–13 september 2026');
$check('månadsetikett lokaliserad (sv/es/ru)',
    str_contains($renders['sv']['history'], 'September 2026')
    && str_contains($renders['es']['history'], 'Septiembre 2026')
    && str_contains($renders['ru']['history'], 'Сентябрь 2026'));
$monthHours = function (string $html): ?string {
    return preg_match('/class="month-hours">([^<]+)</u', $html, $m) ? trim($m[1]) : null;
};
$monthNum = fn(?string $s) => $s === null ? null : preg_replace('/[^0-9.]/', '', $s);
$check('månadssumma identisk sv↔es↔ru',
    $monthNum($monthHours($renders['sv']['history'])) !== null
    && $monthNum($monthHours($renders['sv']['history'])) !== ''
    && $monthNum($monthHours($renders['sv']['history'])) === $monthNum($monthHours($renders['es']['history']))
    && $monthNum($monthHours($renders['sv']['history'])) === $monthNum($monthHours($renders['ru']['history'])));
Translator::persist('sv');

file_put_contents(sys_get_temp_dir() . '/tr_week_ru.html', $renders['ru']['week']);
echo "\n(ru-veckovyn sparad för visuell kontroll: " . sys_get_temp_dir() . "\\tr_week_ru.html)\n";
echo $fail === 0 ? "\nALL LANGUAGE CHECKS PASSED\n" : "\n$fail CHECK(S) FAILED\n";
exit($fail ? 1 : 0);
