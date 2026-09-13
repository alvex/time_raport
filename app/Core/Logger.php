<?php
namespace App\Core;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

final class Logger
{
    public static function error(string $message): void
    {
        self::write('ERROR', $message);
    }

    public static function warn(string $message): void
    {
        self::write('WARN', $message);
    }

    public static function info(string $message): void
    {
        self::write('INFO', $message);
    }

    private static function write(string $level, string $message): void
    {
        $dir = TR_STORAGE . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        // .log.php with a leading "<?php exit;" line so the file is inert
        // even if requested directly through the web server.
        $file = $dir . '/app-' . date('Y-m-d') . '.log.php';
        if (!is_file($file)) {
            @file_put_contents($file, "<?php exit; ?>\n", LOCK_EX);
        }
        $line = sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), $level, $message);
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
