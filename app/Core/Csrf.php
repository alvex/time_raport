<?php
namespace App\Core;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

final class Csrf
{
    /** Per-session token, embedded as _token in every form. */
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function validate(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }

    /**
     * One-time token for idempotent submits (report submission):
     * issued when the preview renders, consumed on the first POST.
     * A double click or back-button resubmit fails the consume and is
     * answered with a friendly notice instead of a second submission.
     */
    public static function issueOnce(string $key): string
    {
        $token = bin2hex(random_bytes(16));
        $_SESSION['_once'][$key] = $token;
        return $token;
    }

    public static function consumeOnce(string $key, ?string $token): bool
    {
        if (!is_string($token) || empty($_SESSION['_once'][$key])) {
            return false;
        }
        $ok = hash_equals($_SESSION['_once'][$key], $token);
        unset($_SESSION['_once'][$key]);
        return $ok;
    }
}
