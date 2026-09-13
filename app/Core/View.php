<?php
namespace App\Core;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

final class View
{
    /** Render a template inside the layout (or standalone with $layout = null). */
    public static function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
        }
        $file = TR_ROOT . '/app/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View not found: ' . $template);
        }
        if ($template === 'error') {
            $layout = null; // error.php is a complete standalone page
        }
        extract($data, EXTR_SKIP);
        if ($layout !== null) {
            $tr_content_template = $file;
            require TR_ROOT . '/app/Views/' . $layout . '.php';
        } else {
            require $file;
        }
    }

    /** HTML-escape for output. Everything printed in views goes through e(). */
    public static function e(?string $s): string
    {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Normalize a raw DB string to clean UTF-8 for display.
     *
     * The legacy latin1 tables hold MIXED content: true latin1 bytes,
     * UTF-8 bytes stored as latin1, and HTML entities typed into names
     * ("&amp;"). We connect with charset=latin1 (raw passthrough) and decode
     * per string: valid UTF-8 is kept, everything else is treated as
     * latin1/cp1252; entities are then decoded so text matches what the
     * legacy pages visually show. Display-only — this app never writes text.
     */
    public static function text(?string $s): string
    {
        if ($s === null || $s === '') {
            return '';
        }
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
        }
        return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** CSS badge class for a report status label. */
    public static function badgeClass(string $status): string
    {
        return [
            'Utkast'          => 'badge-utkast',
            'Inskickad'       => 'badge-inskickad',
            'Godkänd'         => 'badge-godkand',
            'Behöver rättas'  => 'badge-invalid',
        ][$status] ?? 'badge-none';
    }

    /** Build an app URL: url('week', ['week' => '2026-W37']). */
    public static function url(string $page, array $params = []): string
    {
        $qs = http_build_query(['page' => $page] + $params);
        return 'index.php?' . $qs;
    }
}
