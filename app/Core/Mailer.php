<?php
namespace App\Core;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

final class Mailer
{
    /**
     * Send an HTML email. mail_mode 'log' (dev default) writes the message
     * to storage/mail/ instead of sending; 'mail' uses PHP mail() and
     * requires working SMTP/sendmail config in php.ini.
     */
    public static function send(string $to, string $subject, string $html): bool
    {
        $mode = tr_config('mail_mode', 'log');

        if ($mode === 'log') {
            $dir = TR_STORAGE . '/mail';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $safeSubject = preg_replace('/[^A-Za-z0-9_-]+/', '_', mb_substr($subject, 0, 40));
            $file = $dir . '/' . date('Ymd_His') . '_' . $safeSubject . '.html';
            $meta = "<!-- To: $to | Subject: $subject | " . date('c') . " -->\n";
            $ok = @file_put_contents($file, $meta . $html) !== false;
            Logger::info("Mail (log mode) to=$to subject=\"$subject\" file=" . basename($file));
            return $ok;
        }

        $fromName = tr_config('mail_from_name', 'HemFresh TimeRapport');
        $from     = tr_config('mail_from', $to);
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $from . '>',
        ]);
        $ok = @mail($to, $encodedSubject, $html, $headers);
        if (!$ok) {
            Logger::error("mail() failed to=$to subject=\"$subject\"");
        }
        return $ok;
    }
}
