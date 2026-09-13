<?php
if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

/**
 * Copy to config.php for local overrides (config.php is gitignored).
 *
 * db_charset = latin1: the legacy cal_* tables are latin1 with MIXED content
 * (true latin1 rows and UTF-8-bytes-in-latin1 rows). Connecting as latin1
 * returns the raw stored bytes untouched; App\Core\View::text() then decodes
 * each string individually. Verified 2026-09-13 via HEX() comparison —
 * see docs/DATABASE.md. Do not change to utf8mb4: MariaDB would then
 * transcode the raw bytes and double-encode the UTF-8 rows.
 */
return [
    'db_dsn'      => 'mysql:host=localhost;port=3306;dbname=fresh_bokning;charset=latin1',
    'db_user'     => 'root',
    'db_pass'     => '',

    // Where submitted week reports are sent (legacy: $conf['app']['jobbEmail']).
    'jobb_email'  => 'jobb@hemfresh.se',
    'mail_from'   => 'jobb@hemfresh.se',
    'mail_from_name' => 'HemFresh TimeRapport',

    // 'log' = write emails to storage/mail/ instead of sending (dev default).
    // 'mail' = send for real via PHP mail() — enable only after SMTP is verified.
    'mail_mode'   => 'log',

    // Legacy parity: $conf['app']['timezone'] = 0 in scheduledev config.
    // hourOffset shown to a user = cal_login.timezone - this value.
    'legacy_tz_base' => 0.0,

    // Login rate limiting.
    'login_max_failures' => 5,
    'login_lockout_seconds' => 900,
];
