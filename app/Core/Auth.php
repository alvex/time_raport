<?php
namespace App\Core;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

use App\Repositories\ResourceRepository;

/**
 * Authentication against the legacy cal_login table.
 *
 * v1 verifies md5($password) against cal_login.password (char(32)) for
 * employee accounts (`keys` = 2) whose cal_resources.status = 'a'.
 * The column cannot hold a modern hash, so migration is documented as a
 * separate proposal in docs/SECURITY.md — accounts are NOT rehashed here.
 *
 * The authenticated resource id (cal_login.memberid == cal_resources.machid)
 * lives ONLY in the session; it is never read from the URL.
 */
final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            return false;
        }
        if (self::tooManyAttempts($email)) {
            return false;
        }

        $repo = new ResourceRepository();
        $row = $repo->findActiveEmployeeByEmail($email);
        if ($row === null) {
            self::registerFailure($email);
            return false;
        }

        if (!hash_equals(strtolower($row['password']), md5($password))) {
            self::registerFailure($email);
            return false;
        }

        Session::regenerate();
        $_SESSION['resource_id'] = $row['memberid'];   // == cal_resources.machid
        $_SESSION['name']        = View::text($row['fname']);
        $_SESSION['hour_offset'] = (float)$row['timezone'] - (float)tr_config('legacy_tz_base', 0.0);
        $_SESSION['login_time']  = time();
        self::clearFailures($email);
        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['resource_id']);
    }

    /** The ONLY source of the authenticated machid/memberid. */
    public static function id(): string
    {
        return (string)$_SESSION['resource_id'];
    }

    public static function name(): string
    {
        return (string)($_SESSION['name'] ?? '');
    }

    /** Legacy display offset: cal_login.timezone - conf timezone (0). */
    public static function hourOffset(): float
    {
        return (float)($_SESSION['hour_offset'] ?? 0.0);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // --- File-based login rate limiting (no DB changes allowed) ---

    public static function tooManyAttempts(string $email): bool
    {
        $state = self::readLimit($email);
        $max = (int)tr_config('login_max_failures', 5);
        $window = (int)tr_config('login_lockout_seconds', 900);
        return $state !== null
            && $state['count'] >= $max
            && (time() - $state['last']) < $window;
    }

    private static function registerFailure(string $email): void
    {
        $state = self::readLimit($email) ?? ['count' => 0, 'last' => 0];
        $window = (int)tr_config('login_lockout_seconds', 900);
        if ((time() - $state['last']) >= $window) {
            $state['count'] = 0;
        }
        $state['count']++;
        $state['last'] = time();
        @file_put_contents(self::limitFile($email), json_encode($state), LOCK_EX);
    }

    private static function clearFailures(string $email): void
    {
        @unlink(self::limitFile($email));
    }

    private static function readLimit(string $email): ?array
    {
        $file = self::limitFile($email);
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string)@file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    private static function limitFile(string $email): string
    {
        $dir = TR_STORAGE . '/ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        return $dir . '/' . sha1($ip . '|' . $email) . '.json';
    }
}
