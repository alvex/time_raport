<?php
namespace App\Core;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        // Own name + path so this app never collides with scheduledev's
        // PHPSESSID cookie on the same host.
        session_name('TIMERAPPORT_SID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => self::basePath(),
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        ini_set('session.use_strict_mode', '1');
        session_start();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        if (isset($_SESSION['_flash'][$key])) {
            $msg = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $msg;
        }
        return null;
    }

    private static function basePath(): string
    {
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        return $dir === '' ? '/' : $dir . '/';
    }
}
