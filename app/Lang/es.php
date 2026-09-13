<?php
if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }

/** Español — mismas claves que sv.php. Solo texto plano. */
return [
    // Común
    'common.sending'        => 'Enviando…',
    'common.hours_short'    => 'h',
    'common.customer_no'    => 'Cliente n.º',
    'common.key_aria'       => 'Llevar la llave del cliente',
    'common.today_marker'   => 'hoy',
    'common.home'           => 'Ir a la página de inicio',
    'common.booked_note'    => '(reservado: {h} h)',

    // Navegación / cabecera
    'nav.aria'              => 'Menú principal',
    'nav.today'             => 'Hoy',
    'nav.week'              => 'Semana',
    'nav.report'            => 'Informe',
    'nav.history'           => 'Historial',
    'nav.logout'            => 'Cerrar sesión',

    // Selector de idioma
    'lang.label'            => 'Idioma',
    'lang.sv'               => 'Svenska',
    'lang.es'               => 'Español',
    'lang.ru'               => 'Русский',

    // Inicio de sesión
    'login.title'           => 'Iniciar sesión',
    'login.email'           => 'Correo electrónico',
    'login.password'        => 'Contraseña',
    'login.submit'          => 'Iniciar sesión',
    'login.error_invalid'   => 'Correo o contraseña incorrectos.',
    'login.error_locked'    => 'Demasiados intentos. Espere 15 minutos e inténtelo de nuevo.',

    // Panel (Hoy)
    'dash.hello'            => 'Hola, {name}',
    'dash.this_week'        => 'esta semana',
    'dash.report_label'     => 'informe',
    'dash.today_title'      => 'Hoy',
    'dash.no_jobs_today'    => 'No hay trabajos para hoy.',
    'dash.week_schedule'    => 'Horario semanal',
    'dash.report_btn'       => 'Informe de horas',

    // Vista semanal
    'week.title'            => 'Semana {week}',
    'week.prev'             => 'Semana anterior',
    'week.next'             => 'Semana siguiente',
    'week.to_current'       => 'Ir a esta semana',
    'week.no_jobs'          => 'No hay trabajos esta semana.',
    'week.total'            => 'Total semana {week}',
    'week.review'           => 'Revisar informe de la semana {week}',

    // Tiempo de trabajo no válido
    'invalid.title'         => 'El turno no tiene tiempo de trabajo',
    'invalid.reason_negative' => 'La hora de fin es anterior a la de inicio.',
    'invalid.fix_hint'      => 'Corrija el horario antes de enviar el informe semanal.',
    'invalid.fix_btn'       => 'Corregir turno',

    // Estados
    'status.draft'          => 'Sin reportar',
    'status.submitted'      => 'Enviado',
    'status.approved'       => 'Aprobado',
    'status.invalid'        => 'Corregir',
    'status.none'           => '–',

    // Indicaciones
    'maps.start'            => 'Iniciar navegación',
    'maps.short'            => 'Cómo llegar',

    // Detalle del trabajo
    'job.address'           => 'Dirección',
    'job.comment'           => 'Comentario',
    'job.adjust'            => 'Ajustar horario',
    'job.start'             => 'Inicio',
    'job.end'               => 'Fin',
    'job.save'              => 'Guardar horario',
    'job.hint_original'     => 'Las horas reservadas originales se guardan y se muestran en el informe.',
    'job.hint_cancelled'    => '¿Se canceló el turno? Ponga la misma hora de inicio y de fin para reportar 0 horas.',
    'job.back'              => 'Volver a la semana',
    'job.notfound_title'    => 'Trabajo no encontrado',
    'job.notfound_msg'      => 'La reserva no existe o no le pertenece.',
    'job.updated'           => 'El horario se ha actualizado.',
    'job.invalid_format'    => 'Indique las horas como HH:MM, en pasos de 5 minutos.',
    'job.end_before_start'  => 'La hora de fin no puede ser anterior a la de inicio (mismo día).',
    'job.locked'            => 'El trabajo ya está aprobado y no se puede modificar.',
    'job.locked_submitted'  => 'El informe se ha enviado y ya no se puede modificar.',
    'job.locked_approved'   => 'El informe está aprobado y bloqueado.',
    'job.update_failed'     => 'No se pudo actualizar el trabajo: está bloqueado o ya enviado.',

    // Informe de horas
    'report.title'          => 'Informe de horas',
    'report.h_date'         => 'Fecha',
    'report.h_customer'     => 'Cliente',
    'report.h_time'         => 'Horario',
    'report.h_hours'        => 'Horas',
    'report.total_label'    => 'Total',
    'report.week_locked'    => 'La semana está aprobada y bloqueada.',
    'report.confirm_info'   => 'Revise las líneas de arriba. Al enviar, los trabajos de la semana se marcan como enviados y el informe pasa a aprobación.',
    'report.submit'         => 'Enviar para aprobación',
    'report.confirm_again'  => '¿Está seguro? Pulse otra vez para enviar',
    'report.none'           => 'No hay trabajos que reportar esta semana.',
    'report.back'           => 'Volver al horario',
    'report.sent'           => 'El informe de la semana {week} se ha enviado para su aprobación.',
    'report.mail_failed'    => '(Nota: no se pudo enviar el correo de confirmación; el administrador ha sido informado.)',
    'report.already'        => 'El informe ya se ha enviado.',
    'report.approved_locked' => 'La semana ya está aprobada y no se puede volver a enviar.',
    'report.submitted_locked' => 'Enviado: pendiente de aprobación. El informe no se puede modificar ni volver a enviar.',
    'report.mixed_locked'   => 'La semana ya contiene turnos enviados o aprobados y no se puede volver a enviar.',
    'report.empty'          => 'No hay trabajos que reportar en esa semana.',
    'report.blocked.one'    => '{n} turno debe corregirse antes de poder enviar el informe.',
    'report.blocked.other'  => '{n} turnos deben corregirse antes de poder enviar el informe.',

    // Historial
    'hist.title'            => 'Informes de horas',
    'hist.sub'              => 'Últimas 12 semanas',
    'hist.none'             => 'No hay trabajos en las últimas 12 semanas.',

    // Errores
    'err.404_title'         => 'Página no encontrada',
    'err.404_msg'           => 'La página que intentó abrir no existe.',
    'err.csrf_title'        => 'La sesión ha caducado',
    'err.csrf_msg'          => 'No se pudo verificar el formulario. Vuelva atrás e inténtelo de nuevo.',
    'err.generic_title'     => 'Algo salió mal',
    'err.generic_msg'       => 'No se pudo cargar la información. Inténtelo de nuevo o contacte con el administrador.',
    'err.default_title'     => 'Error',

    // Fechas
    'date.day_heading'      => '{day} {d} de {month}',
    'date.range_same'       => '{d1}–{d2} de {month} de {year}',
    'date.range_cross'      => '{d1} de {m1} – {d2} de {m2} de {year}',
    'date.day.0'  => 'Domingo', 'date.day.1' => 'Lunes',   'date.day.2' => 'Martes',
    'date.day.3'  => 'Miércoles', 'date.day.4' => 'Jueves', 'date.day.5' => 'Viernes',
    'date.day.6'  => 'Sábado',
    'date.month.1'  => 'enero',   'date.month.2'  => 'febrero',   'date.month.3'  => 'marzo',
    'date.month.4'  => 'abril',   'date.month.5'  => 'mayo',      'date.month.6'  => 'junio',
    'date.month.7'  => 'julio',   'date.month.8'  => 'agosto',    'date.month.9'  => 'septiembre',
    'date.month.10' => 'octubre', 'date.month.11' => 'noviembre', 'date.month.12' => 'diciembre',

    // Unidades (plural)
    'unit.hours.one'        => 'hora',
    'unit.hours.other'      => 'horas',
    'unit.pass.one'         => 'turno',
    'unit.pass.other'       => 'turnos',
    'unit.jobs.one'         => 'trabajo',
    'unit.jobs.other'       => 'trabajos',
];
