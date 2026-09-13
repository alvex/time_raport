<?php
if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

/** Svenska — standardspråk och fullständig fallback. Endast vanlig text. */
return [
    // Gemensamt
    'common.sending'        => 'Skickar…',
    'common.hours_short'    => 'h',
    'common.customer_no'    => 'Kundnr',
    'common.key_aria'       => 'Ta med kundnyckel',
    'common.today_marker'   => 'idag',
    'common.home'           => 'Till startsidan',
    'common.booked_note'    => '(bokat: {h} h)',

    // Navigation / header
    'nav.aria'              => 'Huvudmeny',
    'nav.today'             => 'Idag',
    'nav.week'              => 'Vecka',
    'nav.report'            => 'Tidrapport',
    'nav.history'           => 'Historik',
    'nav.logout'            => 'Logga ut',

    // Språkväljare
    'lang.label'            => 'Språk',
    'lang.sv'               => 'Svenska',
    'lang.es'               => 'Español',
    'lang.ru'               => 'Русский',

    // Login
    'login.title'           => 'Logga in',
    'login.email'           => 'E-post',
    'login.password'        => 'Lösenord',
    'login.submit'          => 'Logga in',
    'login.error_invalid'   => 'Fel e-post eller lösenord.',
    'login.error_locked'    => 'För många försök. Vänta 15 minuter och försök igen.',

    // Dashboard (Idag)
    'dash.hello'            => 'Hej {name}',
    'dash.this_week'        => 'denna vecka',
    'dash.report_label'     => 'tidrapport',
    'dash.today_title'      => 'Idag',
    'dash.no_jobs_today'    => 'Inga jobb idag.',
    'dash.week_schedule'    => 'Veckans schema',
    'dash.report_btn'       => 'Tidrapport',

    // Veckovyn
    'week.title'            => 'Vecka {week}',
    'week.prev'             => 'Föregående vecka',
    'week.next'             => 'Nästa vecka',
    'week.to_current'       => 'Till denna vecka',
    'week.no_jobs'          => 'Inga jobb den här veckan.',
    'week.total'            => 'Totalt vecka {week}',
    'week.review'           => 'Granska tidrapport för vecka {week}',

    // Ogiltig arbetstid
    'invalid.title'         => 'Passet saknar arbetstid',
    'invalid.reason_negative' => 'Sluttiden är före starttiden.',
    'invalid.fix_hint'      => 'Rätta tiden innan veckans rapport skickas.',
    'invalid.fix_btn'       => 'Rätta pass',

    // Statusar
    'status.draft'          => 'Ej rapporterad',
    'status.submitted'      => 'Inskickad',
    'status.approved'       => 'Godkänd',
    'status.invalid'        => 'Behöver rättas',
    'status.none'           => '–',

    // Vägbeskrivning
    'maps.start'            => 'Starta vägbeskrivning',
    'maps.short'            => 'Vägbeskrivning',

    // Jobbdetalj
    'job.address'           => 'Adress',
    'job.comment'           => 'Kommentar',
    'job.adjust'            => 'Justera tider',
    'job.start'             => 'Start',
    'job.end'               => 'Slut',
    'job.save'              => 'Spara tider',
    'job.hint_original'     => 'Ursprungligt bokade timmar sparas och visas i tidrapporten.',
    'job.hint_cancelled'    => 'Blev passet avbokat? Sätt samma start- och sluttid så rapporteras 0 timmar.',
    'job.back'              => 'Tillbaka till veckan',
    'job.notfound_title'    => 'Jobbet hittades inte',
    'job.notfound_msg'      => 'Bokningen finns inte eller tillhör inte dig.',
    'job.updated'           => 'Tiderna är uppdaterade.',
    'job.invalid_format'    => 'Ange tider som HH:MM i 5-minuterssteg.',
    'job.end_before_start'  => 'Sluttiden får inte vara före starttiden (samma dag).',
    'job.locked'            => 'Jobbet är redan godkänt och kan inte ändras.',
    'job.locked_submitted'  => 'Tidrapporten är inskickad och kan inte längre ändras.',
    'job.locked_approved'   => 'Tidrapporten är godkänd och låst.',
    'job.update_failed'     => 'Jobbet kunde inte uppdateras — det är låst eller redan inskickat.',

    // Tidrapport
    'report.title'          => 'Tidrapport',
    'report.h_date'         => 'Datum',
    'report.h_customer'     => 'Kund',
    'report.h_time'         => 'Tid',
    'report.h_hours'        => 'Timmar',
    'report.total_label'    => 'Totalt',
    'report.week_locked'    => 'Veckan är godkänd och låst.',
    'report.confirm_info'   => 'Kontrollera raderna ovan. När du skickar markeras veckans jobb som inskickade och rapporten skickas för godkännande.',
    'report.submit'         => 'Skicka för godkännande',
    'report.confirm_again'  => 'Är du säker? Tryck igen för att skicka',
    'report.none'           => 'Inga jobb att rapportera för den här veckan.',
    'report.back'           => 'Tillbaka till schemat',
    'report.sent'           => 'Tidrapporten för vecka {week} är inskickad för godkännande.',
    'report.mail_failed'    => '(Obs: e-postbekräftelsen kunde inte skickas — administratören är informerad.)',
    'report.already'        => 'Rapporten är redan inskickad.',
    'report.approved_locked' => 'Veckan är redan godkänd och kan inte skickas igen.',
    'report.submitted_locked' => 'Inskickad – väntar på godkännande. Rapporten kan inte ändras eller skickas igen.',
    'report.mixed_locked'   => 'Veckan innehåller redan inskickade eller godkända pass och kan därför inte skickas igen.',
    'report.empty'          => 'Det finns inga jobb att rapportera för den veckan.',
    'report.blocked.one'    => '{n} pass behöver rättas innan rapporten kan skickas.',
    'report.blocked.other'  => '{n} pass behöver rättas innan rapporten kan skickas.',

    // Historik
    'hist.title'            => 'Tidrapporter',
    'hist.sub'              => 'Senaste 12 veckorna',
    'hist.none'             => 'Inga jobb de senaste 12 veckorna.',

    // Fel
    'err.404_title'         => 'Sidan finns inte',
    'err.404_msg'           => 'Sidan du försökte nå finns inte.',
    'err.csrf_title'        => 'Sessionen har gått ut',
    'err.csrf_msg'          => 'Formuläret kunde inte verifieras. Gå tillbaka och försök igen.',
    'err.generic_title'     => 'Något gick fel',
    'err.generic_msg'       => 'Det gick inte att läsa informationen just nu. Försök igen eller kontakta administratören.',
    'err.default_title'     => 'Fel',

    // Datum (mönster + namn; ryska använder genitiv i sina månadsnamn)
    'date.day_heading'      => '{day} {d} {month}',
    'date.range_same'       => '{d1}–{d2} {month} {year}',
    'date.range_cross'      => '{d1} {m1} – {d2} {m2} {year}',
    'date.day.0'  => 'Söndag',  'date.day.1' => 'Måndag',  'date.day.2' => 'Tisdag',
    'date.day.3'  => 'Onsdag',  'date.day.4' => 'Torsdag', 'date.day.5' => 'Fredag',
    'date.day.6'  => 'Lördag',
    'date.month.1'  => 'januari',  'date.month.2'  => 'februari', 'date.month.3'  => 'mars',
    'date.month.4'  => 'april',    'date.month.5'  => 'maj',      'date.month.6'  => 'juni',
    'date.month.7'  => 'juli',     'date.month.8'  => 'augusti',  'date.month.9'  => 'september',
    'date.month.10' => 'oktober',  'date.month.11' => 'november', 'date.month.12' => 'december',

    // Enheter (plural)
    'unit.hours.one'        => 'timme',
    'unit.hours.other'      => 'timmar',
    'unit.pass.one'         => 'pass',
    'unit.pass.other'       => 'pass',
    'unit.jobs.one'         => 'jobb',
    'unit.jobs.other'       => 'jobb',
];
