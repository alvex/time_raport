<?php
namespace App\Controllers;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

use App\Core\Auth;
use App\Core\View;
use App\Services\ReportService;
use App\Services\ScheduleService;

final class DashboardController
{
    public function index(): void
    {
        $machid = Auth::id();
        $offset = Auth::hourOffset();
        $schedule = new ScheduleService();
        $reports = new ReportService();

        $isoWeek = $schedule->currentIsoWeek();
        $week = $reports->buildWeekReport($machid, $isoWeek, $offset);
        $today = $schedule->getTodaySchedule($machid, $offset);

        // The next job today that hasn't ended yet (displayed clock time).
        $nowDisplay = (int)date('G') * 60 + (int)date('i');
        $nextJob = null;
        foreach ($today as $job) {
            $endDisp = \App\Services\TimeFormatter::displayMinutes((int)$job['endtime'], $offset);
            if ($endDisp >= $nowDisplay) {
                $nextJob = $job;
                break;
            }
        }

        View::render('dashboard', [
            'name'    => Auth::name(),
            'week'    => $week,
            'today'   => $today,
            'nextJob' => $nextJob,
        ]);
    }
}
