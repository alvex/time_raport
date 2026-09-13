<?php
namespace App\Core;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

/**
 * UI translation module. Locales are hard-whitelisted (LOCALES) and the
 * language files are loaded from a fixed map — user input never reaches
 * a file path, so path traversal/dynamic includes are impossible.
 *
 * Resolution order: session -> TIMERAPPORT_LANG cookie -> 'sv'.
 * Swedish is the complete fallback: a key missing in the active locale
 * falls back to sv; a key missing everywhere returns the key itself
 * (visible in dev, harmless in prod).
 *
 * Only interface strings are translated. Database data (names,
 * addresses, comments) and the report email (an administrative,
 * Swedish-language process) are untouched.
 */
final class Translator
{
    public const LOCALES = ['sv', 'es', 'ru'];
    public const DEFAULT = 'sv';
    public const COOKIE = 'TIMERAPPORT_LANG';

    private static string $locale = self::DEFAULT;
    /** @var array<string, array<string, string>> per-locale string cache */
    private static array $cache = [];

    /** Resolve the active locale from session/cookie (bootstrap). */
    public static function init(): void
    {
        $candidate = $_SESSION['lang'] ?? ($_COOKIE[self::COOKIE] ?? null);
        self::$locale = self::sanitize(is_string($candidate) ? $candidate : null);
    }

    /** Whitelist-validate a locale; anything else becomes Swedish. */
    public static function sanitize(?string $locale): string
    {
        return in_array($locale, self::LOCALES, true) ? $locale : self::DEFAULT;
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    /** Persist a (validated) choice in session + long-lived cookie. */
    public static function persist(string $locale): void
    {
        $locale = self::sanitize($locale);
        self::$locale = $locale;
        $_SESSION['lang'] = $locale;
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            setcookie(self::COOKIE, $locale, [
                'expires'  => time() + 365 * 24 * 3600,
                'path'     => Session::basePath(),
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
        }
    }

    /**
     * Translate a key with {param} substitution. Optional $locale forces
     * a specific language regardless of the active one (used e.g. to
     * keep the report email Swedish).
     */
    public static function get(string $key, array $params = [], ?string $locale = null): string
    {
        $locale = $locale === null ? self::$locale : self::sanitize($locale);
        $str = self::strings($locale)[$key]
            ?? self::strings(self::DEFAULT)[$key]
            ?? $key;
        return self::interpolate($str, $params);
    }

    /**
     * Pluralized translation. Looks up "$key.$form" where $form follows
     * the locale's rule; falls back to "$key.other", then Swedish.
     * {n} is available as a parameter in the string.
     *  - sv/es: one / other
     *  - ru: one (1, 21...), few (2-4, 22-24... and decimals), many (0, 5-20...)
     */
    public static function plural(string $key, int|float $n, array $params = [], ?string $locale = null): string
    {
        $locale = $locale === null ? self::$locale : self::sanitize($locale);
        $form = self::pluralForm($locale, $n);
        $strings = self::strings($locale);
        $fallback = self::strings(self::DEFAULT);
        $str = $strings["$key.$form"]
            ?? $strings["$key.other"]
            ?? $fallback['' . "$key." . self::pluralForm(self::DEFAULT, $n)]
            ?? $fallback["$key.other"]
            ?? $key;
        $nText = (string)(fmod((float)$n, 1.0) === 0.0 ? (int)$n : $n);
        return self::interpolate($str, $params + ['n' => $nText]);
    }

    private static function pluralForm(string $locale, int|float $n): string
    {
        if ($locale === 'ru') {
            if (fmod((float)$n, 1.0) !== 0.0) {
                return 'few';       // 3,5 часа
            }
            $i = abs((int)$n);
            $mod10 = $i % 10;
            $mod100 = $i % 100;
            if ($mod10 === 1 && $mod100 !== 11) {
                return 'one';
            }
            if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
                return 'few';
            }
            return 'many';
        }
        return ((float)$n === 1.0) ? 'one' : 'other';
    }

    private static function interpolate(string $str, array $params): string
    {
        if ($params === []) {
            return $str;
        }
        $repl = [];
        foreach ($params as $k => $v) {
            $repl['{' . $k . '}'] = (string)$v;
        }
        return strtr($str, $repl);
    }

    /** @return array<string, string> */
    private static function strings(string $locale): array
    {
        if (!isset(self::$cache[$locale])) {
            // Fixed literal map — never derived from user input.
            $files = [
                'sv' => TR_ROOT . '/app/Lang/sv.php',
                'es' => TR_ROOT . '/app/Lang/es.php',
                'ru' => TR_ROOT . '/app/Lang/ru.php',
            ];
            self::$cache[$locale] = require $files[$locale];
        }
        return self::$cache[$locale];
    }
}
