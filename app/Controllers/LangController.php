<?php
namespace App\Controllers;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

use App\Core\Auth;
use App\Core\Translator;
use App\Core\View;

/**
 * Language switching. POST-only (central CSRF applies), locale validated
 * against Translator's whitelist, and the return target is rebuilt from
 * a parsed, whitelisted page name + known parameters — a raw URL is
 * never echoed into the Location header, so open redirects are
 * impossible by construction.
 */
final class LangController
{
    public function set(): void
    {
        Translator::persist((string)($_POST['locale'] ?? ''));
        header('Location: ' . self::safeReturnUrl($_POST['return'] ?? null));
    }

    public static function safeReturnUrl(?string $raw): string
    {
        if (is_string($raw) && $raw !== '' && strlen($raw) <= 512) {
            $parts = parse_url($raw);
            $host = $parts['host'] ?? null;
            $scheme = $parts['scheme'] ?? null;
            $path = $parts['path'] ?? '';
            // Only same-app forms: "index.php?...", "?..." or bare query.
            if ($host === null && $scheme === null
                && ($path === '' || $path === 'index.php')) {
                parse_str($parts['query'] ?? '', $params);
                $page = (string)($params['page'] ?? '');
                $routes = require TR_ROOT . '/app/routes.php';
                // Rebuild from scratch: whitelisted GET page + known params.
                if (isset($routes[$page]) && $routes[$page][3] === false) {
                    $keep = [];
                    foreach (['week', 'resid'] as $k) {
                        if (isset($params[$k]) && is_string($params[$k])) {
                            $keep[$k] = $params[$k];
                        }
                    }
                    return View::url($page, $keep);
                }
            }
        }
        return View::url(Auth::check() ? 'dashboard' : 'login');
    }
}
