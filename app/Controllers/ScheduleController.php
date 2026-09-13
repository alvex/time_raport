<?php
namespace App\Controllers;

if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Session;
use App\Core\View;
use App\Repositories\BookingRepository;
use App\Services\ScheduleService;
use App\Services\TimeFormatter;

final class ScheduleController
{
    public function week(): void
    {
        $machid = Auth::id();
        $schedule = new ScheduleService();

        $isoWeek = $schedule->normalizeIsoWeek($_GET['week'] ?? null) ?? $schedule->currentIsoWeek();
        $week = $schedule->getWeekSchedule($machid, $isoWeek, Auth::hourOffset());

        View::render('week', [
            'week'        => $week,
            'prevWeek'    => $schedule->adjacentWeek($isoWeek, -1),
            'nextWeek'    => $schedule->adjacentWeek($isoWeek, 1),
            'currentWeek' => $schedule->currentIsoWeek(),
            'notice'      => Session::getFlash('notice'),
            'warning'     => Session::getFlash('warning'),
        ]);
    }

    public function job(): void
    {
        $machid = Auth::id();
        $resid = (string)($_GET['resid'] ?? '');
        $schedule = new ScheduleService();

        // Ownership enforced in SQL (resid AND machid); a colleague's
        // booking id simply does not exist for this session.
        $booking = $resid === '' ? null : $schedule->getBooking($resid, $machid, Auth::hourOffset());
        if ($booking === null) {
            http_response_code(404);
            View::render('error', [
                'title'   => 'Jobbet hittades inte',
                'message' => 'Bokningen finns inte eller tillhör inte dig.',
            ]);
            return;
        }

        View::render('job', [
            'b'       => $booking,
            'backWeek'=> date('o', (int)$booking['start_date']) . '-W' . date('W', (int)$booking['start_date']),
            'notice'  => Session::getFlash('notice'),
            'warning' => Session::getFlash('warning'),
        ]);
    }

    /**
     * Employee adjusts a booking's times before submitting the report
     * (legacy changeTimes = 1 flow). Times arrive as DISPLAY times and are
     * converted back to stored minutes with the inverse hour offset.
     * old_hours keeps the ORIGINAL booked hours: preserved once set
     * (legacy Reservation.class.php:1218 semantics).
     */
    public function jobUpdateTimes(): void
    {
        $machid = Auth::id();
        $offset = Auth::hourOffset();
        $resid = (string)($_POST['resid'] ?? '');

        $repo = new BookingRepository();
        $schedule = new ScheduleService($repo);
        $booking = $resid === '' ? null : $schedule->getBooking($resid, $machid, $offset);

        if ($booking === null) {
            http_response_code(404);
            View::render('error', [
                'title'   => 'Jobbet hittades inte',
                'message' => 'Bokningen finns inte eller tillhör inte dig.',
            ]);
            return;
        }

        if (!$booking['editable']) {
            Session::flash('warning', 'Jobbet är redan godkänt och kan inte ändras.');
            header('Location: ' . View::url('job', ['resid' => $resid]));
            return;
        }

        $startDisp = TimeFormatter::parseTime((string)($_POST['start_time'] ?? ''));
        $endDisp = TimeFormatter::parseTime((string)($_POST['end_time'] ?? ''));

        if ($startDisp === null || $endDisp === null
            || $startDisp % 5 !== 0 || $endDisp % 5 !== 0) {
            Session::flash('warning', 'Ange tider som HH:MM i 5-minuterssteg.');
            header('Location: ' . View::url('job', ['resid' => $resid]));
            return;
        }

        $startStored = TimeFormatter::displayToStored($startDisp, $offset);
        $endStored = TimeFormatter::displayToStored($endDisp, $offset);

        if ($startStored >= $endStored) {
            Session::flash('warning', 'Sluttiden måste vara efter starttiden (samma dag).');
            header('Location: ' . View::url('job', ['resid' => $resid]));
            return;
        }

        // Original hours: keep existing old_hours once set, otherwise the
        // hours as they were booked before this change.
        $oldHours = ((float)($booking['old_hours'] ?? 0) > 0)
            ? (float)$booking['old_hours']
            : round(((int)$booking['endtime'] - (int)$booking['starttime']) / 60, 1);

        $changed = $repo->updateTimes($resid, $machid, $startStored, $endStored, $oldHours);
        if ($changed > 0) {
            Logger::info("Times updated resid=$resid machid=$machid "
                . "{$booking['starttime']}-{$booking['endtime']} -> $startStored-$endStored");
            Session::flash('notice', 'Tiderna är uppdaterade.');
        } else {
            Session::flash('warning', 'Jobbet kunde inte uppdateras (redan godkänt?).');
        }
        header('Location: ' . View::url('job', ['resid' => $resid]));
    }
}
