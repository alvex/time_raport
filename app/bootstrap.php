<?php
if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

/**
 * Bootstrap: timezone, error handling, autoloading, config, session.
 * The legacy DB stores dates as unix timestamps at local midnight and
 * times as minutes past midnight; all date math in this app uses
 * Europe/Stockholm regardless of php.ini (which says Europe/Berlin).
 */
date_default_timezone_set('Europe/Stockholm');

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

define('TR_ROOT', dirname(__DIR__));
define('TR_STORAGE', TR_ROOT . '/storage');

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }
    $path = TR_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$GLOBALS['tr_config'] = require (is_file(TR_ROOT . '/config/config.php')
    ? TR_ROOT . '/config/config.php'
    : TR_ROOT . '/config/config.example.php');

function tr_config(string $key, $default = null)
{
    return $GLOBALS['tr_config'][$key] ?? $default;
}

set_exception_handler(static function (Throwable $e): void {
    \App\Core\Logger::error('Uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    \App\Core\View::render('error', [
        'title'   => 'Något gick fel',
        'message' => 'Det gick inte att läsa informationen just nu. Försök igen eller kontakta administratören.',
    ]);
    exit;
});

set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) {
        return false;
    }
    throw new ErrorException($str, 0, $no, $file, $line);
});

if (PHP_SAPI !== 'cli') {
    \App\Core\Session::start();
}
