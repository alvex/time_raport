<?php
if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ReportController;
use App\Controllers\ScheduleController;

/**
 * Route whitelist: page => [controller, method, requiresAuth, postOnly]
 */
return [
    'login'          => [AuthController::class,      'showLogin',      false, false],
    'do_login'       => [AuthController::class,      'doLogin',        false, true],
    'logout'         => [AuthController::class,      'logout',         true,  true],
    'dashboard'      => [DashboardController::class, 'index',          true,  false],
    'week'           => [ScheduleController::class,  'week',           true,  false],
    'job'            => [ScheduleController::class,  'job',            true,  false],
    'job_update'     => [ScheduleController::class,  'jobUpdateTimes', true,  true],
    'report_preview' => [ReportController::class,    'preview',        true,  false],
    'report_submit'  => [ReportController::class,    'submit',         true,  true],
    'history'        => [ReportController::class,    'history',        true,  false],
];
