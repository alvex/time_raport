<?php
/**
 * FAS 2 charset gate (CLI only, read-only).
 * Compares HEX() of stored bytes with what PHP receives under a latin1
 * vs utf8mb4 connection, and shows how View::text() normalizes them.
 *
 *   C:\xampp\php\php.exe scripts\charset_check.php
 */
if (PHP_SAPI !== 'cli') { exit; }
define('TIMERAPPORT', 1);
require dirname(__DIR__) . '/app/bootstrap.php';

$samples = [
    ["SELECT name AS v, HEX(name) AS h FROM cal_resources WHERE name LIKE 'RESETILL%' LIMIT 1", 'cal_resources.name (true latin1)'],
    ["SELECT address AS v, HEX(address) AS h FROM cal_login WHERE address LIKE 'Kungsklippev%' LIMIT 1", 'cal_login.address (utf8-in-latin1)'],
    ["SELECT lname AS v, HEX(lname) AS h FROM cal_login WHERE lname LIKE '%fqvist%' AND lname LIKE '%amp%' LIMIT 1", 'cal_login.lname (html entity)'],
    ["SELECT city AS v, HEX(city) AS h FROM cal_login WHERE city LIKE '%Saltsj%' LIMIT 1", 'cal_login.city (true latin1)'],
];

foreach (['latin1', 'utf8mb4'] as $charset) {
    echo "=== Connection charset: $charset ===\n";
    $pdo = new PDO(
        "mysql:host=localhost;port=3306;dbname=fresh_bokning;charset=$charset",
        tr_config('db_user'), tr_config('db_pass'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    foreach ($samples as [$sql, $label]) {
        $row = $pdo->query($sql)->fetch();
        if (!$row) { echo "  $label: (no sample row)\n"; continue; }
        $received = bin2hex($row['v']);
        $stored = strtolower($row['h']);
        $match = $received === $stored ? 'RAW MATCH' : 'transcoded';
        echo "  $label\n";
        echo "    stored bytes  : $stored\n";
        echo "    received bytes: $received  [$match]\n";
        echo "    View::text()  : " . \App\Core\View::text($row['v']) . "\n";
    }
    echo "\n";
}
echo "Expected: latin1 connection = RAW MATCH on every row; View::text() shows correct Swedish.\n";
