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
            Session::flash('warning', $report['invalidCount'] . ' pass behöver rättas innan rapporten kan skickas.');
            header('Location: ' . View::url('week', ['week' => $isoWeek]));
            return;
        }

        View::render('report_preview', [
            'report'    => $report,
            'onceToken' => Csrf::issueOnce('report_' . $isoWeek),
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
            Session::flash('notice', 'Rapporten är redan inskickad.');
            header('Location: ' . View::url('history'));
            return;
        }

        $result = $reports->submitReport($machid, $isoWeek, Auth::hourOffset(), Auth::name());

        if ($result['ok']) {
            $msg = 'Tidrapporten för vecka ' . $schedule->weekNumber($isoWeek) . ' är inskickad för godkännande.';
            if (!$result['mailOk']) {
                $msg .= ' (Obs: e-postbekräftelsen kunde inte skickas — administratören är informerad.)';
            }
            Session::flash('notice', $msg);
        } elseif ($result['reason'] === 'invalid') {
            Session::flash('warning', $result['invalidCount'] . ' pass behöver rättas innan rapporten kan skickas.');
        } elseif ($result['reason'] === 'approved') {
            Session::flash('warning', 'Veckan är redan godkänd och kan inte skickas igen.');
        } else {
            Session::flash('warning', 'Det finns inga jobb att rapportera för den veckan.');
        }
        header('Location: ' . View::url('history'));
    }

    public function history(): void
    {
        $machid = Auth::id();
        $reports = new ReportService();

        View::render('history', [
            'weeks'   => $reports->deriveHistory($machid, Auth::hourOffset(), 12),
            'notice'  => Session::getFlash('notice'),
            'warning' => Session::getFlash('warning'),
        ]);
    }
}
