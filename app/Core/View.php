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

    /** Render a partial from app/Views/partials/ with local variables. */
    public static function partial(string $name, array $data = []): void
    {
        $file = TR_ROOT . '/app/Views/partials/' . $name . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Partial not found: ' . $name);
        }
        extract($data, EXTR_SKIP);
        require $file;
    }

    /** Designsystemets statusbricke-klass för en statusnyckel. */
    public static function statusClass(string $statusKey): string
    {
        return [
            'draft'     => 'hf-status--open',
            'submitted' => 'hf-status--sent',
            'approved'  => 'hf-status--approved',
            'invalid'   => 'hf-status--attn',
        ][$statusKey] ?? 'hf-status--open';
    }

    /**
     * Hours for UI display: decimal comma per the design system
     * ("3,5 h", "127,5 h"). TimeFormatter::formatHours keeps the legacy
     * dot for emails and parity checks; only views call this.
     */
    public static function hours(string $hours): string
    {
        return str_replace('.', ',', $hours);
    }

    /**
     * Inline SVG line icon from the design system (24-grid, 2px stroke,
     * rounded caps, drawn in currentColor). $class usually 'hf-icon' or
     * 'hf-icon hf-icon--sm'.
     */
    public static function icon(string $name, string $class = 'hf-icon'): string
    {
        static $paths = [
            // Navigering
            'home'      => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M10 21v-6h4v6"/>',
            'calendar'  => '<rect x="3" y="4.5" width="18" height="16.5" rx="2"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>',
            'report'    => '<path d="M14 3H6.5A1.5 1.5 0 0 0 5 4.5v15A1.5 1.5 0 0 0 6.5 21h11a1.5 1.5 0 0 0 1.5-1.5V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
            'history'   => '<path d="M3 12a9 9 0 1 0 2.6-6.4L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/>',
            // Sekundär information
            'pin'       => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
            'hash'      => '<path d="M4 9h16M4 15h16M10 3 8 21M16 3l-2 18"/>',
            'comment'   => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-4.9A8 8 0 1 1 21 12z"/>',
            'key'       => '<circle cx="7.5" cy="15.5" r="4.5"/><path d="m10.7 12.3 9.8-9.8M17 6l3 3M14.5 8.5l2 2"/>',
            // Åtgärder
            'navigate'  => '<path d="M3 11 21 3l-8 18-2-8z"/>',
            'pencil'    => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
            'send'      => '<path d="m21.5 2.5-7 19-3.5-8-8-3.5z"/><path d="M21.5 2.5 11 13"/>',
            // Påminnelse
            'bell'      => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 8-3 8h18s-3-1-3-8"/><path d="M10.3 21a2 2 0 0 0 3.4 0"/>',
            // Status och avvikelse
            'open'      => '<circle cx="12" cy="12" r="9" stroke-dasharray="3.5 3"/>',
            'approved'  => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
            'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
            'chev-left' => '<path d="m15 18-6-6 6-6"/>',
            'chev-right'=> '<path d="m9 18 6-6-6-6"/>',
        ];
        if (!isset($paths[$name])) {
            return '';
        }
        return '<svg class="' . self::e($class) . '" viewBox="0 0 24 24" aria-hidden="true">' . $paths[$name] . '</svg>';
    }

    /** Build an app URL: url('week', ['week' => '2026-W37']). */
    public static function url(string $page, array $params = []): string
    {
        $qs = http_build_query(['page' => $page] + $params);
        return 'index.php?' . $qs;
    }
}
