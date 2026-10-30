<?php
namespace App\Controllers;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\ReportService;
use App\Services\ScheduleService;

final class ReportController
{
    public function preview(): void
    {
        $machid = Auth::id();
        $schedule = new ScheduleService();
        $reports = new ReportService();

        $isoWeek = $schedule->normalizeIsoWeek($_GET['week'] ?? null) ?? $schedule->currentIsoWeek();
        $report = $reports->buildWeekReport($machid, $isoWeek, Auth::hourOffset());

        // Server-side gate: a week with invalid working times cannot reach
        // the preview/submit step (the URL cannot bypass the week view).
        if ($report['invalidCount'] > 0) {
            Session::flash('warning', tp('report.blocked', $report['invalidCount']));
            header('Location: ' . View::url('week', ['week' => $isoWeek]));
            return;
        }

        View::render('report_preview', [
            'report'    => $report,
            // One-time token only when submission is actually possible.
            'onceToken' => $report['canSubmit'] ? Csrf::issueOnce('report_' . $isoWeek) : null,
            'notice'    => Session::getFlash('notice'),
            'warning'   => Session::getFlash('warning'),
        ]);
    }

    public function submit(): void
    {
        $machid = Auth::id();
        $schedule = new ScheduleService();
        $reports = new ReportService();

        $isoWeek = $schedule->normalizeIsoWeek($_POST['week'] ?? null);
        if ($isoWeek === null) {
            header('Location: ' . View::url('history'));
            return;
        }

        // One-time token: a double click or back-button resubmit fails
        // here and gets a friendly notice instead of a second submission.
        if (!Csrf::consumeOnce('report_' . $isoWeek, $_POST['_once'] ?? null)) {
            Session::flash('notice', t('report.already'));
            header('Location: ' . View::url('history'));
            return;
        }

        $result = $reports->submitReport($machid, $isoWeek, Auth::hourOffset(), Auth::name());

        if ($result['ok']) {
            $msg = t('report.sent', ['week' => $schedule->weekNumber($isoWeek)]);
            if (!$result['mailOk']) {
                $msg .= ' ' . t('report.mail_failed');
            }
            Session::flash('notice', $msg);
        } elseif ($result['reason'] === 'invalid') {
            Session::flash('warning', tp('report.blocked', $result['invalidCount']));
        } elseif ($result['reason'] === 'approved') {
            Session::flash('warning', t('report.approved_locked'));
        } elseif ($result['reason'] === 'locked_mixed') {
            Session::flash('warning', t('report.mixed_locked'));
        } elseif ($result['reason'] === 'locked_submitted') {
            Session::flash('warning', t('report.submitted_locked'));
        } else {
            Session::flash('warning', t('report.empty'));
        }
        header('Location: ' . View::url('history'));
    }

    public function history(): void
    {
        $machid = Auth::id();
        $reports = new ReportService();
        $schedule = new ScheduleService();

        // Month selection: canonical ?month=YYYY-MM (prev/next links),
        // or month_y + month_m from the no-JS picker form. Anything
        // invalid or manipulated silently falls back to the current
        // month (Europe/Stockholm) — never an error page.
        $raw = $_GET['month'] ?? null;
        if (!is_string($raw)
            && isset($_GET['month_y'], $_GET['month_m'])
            && is_string($_GET['month_y']) && is_string($_GET['month_m'])) {
            $raw = sprintf('%04d-%02d', (int)$_GET['month_y'], (int)$_GET['month_m']);
        }
        $month = $schedule->normalizeMonth(is_string($raw) ? $raw : null)
            ?? $schedule->currentMonth();

        View::render('history', [
            'month'   => $reports->monthSummary($machid, $month),
            // Veckolistan följer månadsvalet: bara veckor som tillhör
            // (överlappar) den visade månaden.
            'weeks'   => $reports->deriveMonthHistory($machid, Auth::hourOffset(), $month),
            'notice'  => Session::getFlash('notice'),
            'warning' => Session::getFlash('warning'),
        ]);
    }
}
