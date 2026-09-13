<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;

$tr_pin  = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-5.5-7-11a7 7 0 1 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>';
$tr_nav  = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 17c0-4 3-7 8-7h5"/><path d="M13 5.5 17.5 10 13 14.5"/></svg>';
$tr_warn = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2.6 22.6 20.4a1 1 0 0 1-.86 1.5H2.26a1 1 0 0 1-.86-1.5L12 2.6z"/><path d="M12 9v5" stroke="#fff" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="17.2" r="1.2" fill="#fff"/></svg>';
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<div class="weekpicker">
  <a class="weekpicker-btn" href="<?= View::e(View::url('week', ['week' => $prevWeek])) ?>" aria-label="<?= View::e(t('week.prev')) ?>">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 6 8.5 12l6 6"/></svg>
  </a>
  <div class="weekpicker-title">
    <h1><?= View::e(t('week.title', ['week' => $week['weekNumber']])) ?></h1>
    <p><?= View::e($week['rangeLabel']) ?></p>
    <?php if ($week['isoWeek'] !== $currentWeek): ?>
    <a class="weekpicker-today" href="<?= View::e(View::url('week')) ?>"><?= View::e(t('week.to_current')) ?></a>
    <?php endif; ?>
  </div>
  <a class="weekpicker-btn" href="<?= View::e(View::url('week', ['week' => $nextWeek])) ?>" aria-label="<?= View::e(t('week.next')) ?>">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.5 6l6 6-6 6"/></svg>
  </a>
</div>

<?php foreach ($week['days'] as $day): ?>
  <?php if ($day['bookings'] === []) continue; ?>
  <div class="day-head<?= $day['isToday'] ? ' day-today' : '' ?>">
    <h2><?= View::e($day['heading']) ?><?= $day['isToday'] ? ' · ' . View::e(t('common.today_marker')) : '' ?></h2>
    <span class="day-count"><?= count($day['bookings']) ?> <?= View::e(tp('unit.pass', count($day['bookings']))) ?></span>
  </div>

  <?php foreach ($day['bookings'] as $b): ?>
  <article class="card job-card<?= $b['invalid'] ? ' job-invalid' : '' ?>">
    <a class="job-main" href="<?= View::e(View::url('job', ['resid' => $b['resid']])) ?>">
      <div class="job-row">
        <div>
          <div class="job-time"><?= View::e($b['display_start']) ?>–<?= View::e($b['display_end']) ?></div>
          <div class="job-duration"><?= View::e($b['hours']) ?> <?= View::e(tp('unit.hours', (float)$b['hours'])) ?><?php
            if ($b['old_hours_disp'] !== null && $b['old_hours_disp'] !== $b['hours']): ?>
            <span class="job-oldhours"><?= View::e(t('common.booked_note', ['h' => $b['old_hours_disp']])) ?></span><?php endif; ?></div>
        </div>
        <span class="badge <?= View::e(View::badgeClass($b['status_key'])) ?>"><?= View::e(t('status.' . $b['status_key'])) ?></span>
      </div>
      <div class="job-customer"><?php View::partial('customer', ['b' => $b]); ?></div>
      <?php if ($b['display_address'] !== ''): ?>
      <div class="job-address">
        <span class="job-pin"><?= $tr_pin ?></span>
        <span><?= View::e($b['display_address']) ?><?php if ($b['display_city'] !== ''): ?><br><?= View::e($b['display_city']) ?><?php endif; ?></span>
      </div>
      <?php endif; ?>
    </a>

    <?php if ($b['invalid']): ?>
    <div class="invalid-panel">
      <span class="invalid-icon"><?= $tr_warn ?></span>
      <div>
        <strong><?= View::e(t('invalid.title')) ?></strong><br>
        <?= View::e(t('invalid.reason_negative')) ?> <?= View::e(t('invalid.fix_hint')) ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($b['invalid'] || $b['maps_url'] !== null): ?>
    <div class="job-actions">
      <?php if ($b['invalid']): ?>
      <a class="btn btn-fix" href="<?= View::e(View::url('job', ['resid' => $b['resid']])) ?>"><?= View::e(t('invalid.fix_btn')) ?></a>
      <?php endif; ?>
      <?php if ($b['maps_url'] !== null): ?>
      <a class="btn btn-maps" href="<?= View::e($b['maps_url']) ?>" target="_blank" rel="noopener noreferrer">
        <?= $tr_nav ?> <?= View::e($b['invalid'] ? t('maps.short') : t('maps.start')) ?>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>
<?php endforeach; ?>

<?php if ($week['jobCount'] === 0): ?>
  <div class="card empty-card"><?= View::e(t('week.no_jobs')) ?></div>
<?php else: ?>
  <div class="card total-card">
    <span><?= View::e(t('week.total', ['week' => $week['weekNumber']])) ?></span>
    <strong><?= View::e($week['totalHours']) ?> <?= View::e(t('common.hours_short')) ?></strong>
  </div>

  <?php if ($week['invalidCount'] > 0): ?>
    <span class="btn btn-primary btn-block btn-disabled" aria-disabled="true">
      <?= View::e(t('week.review', ['week' => $week['weekNumber']])) ?>
    </span>
    <p class="report-blocked" role="alert">
      <?= View::e(tp('report.blocked', $week['invalidCount'])) ?>
    </p>
  <?php else: ?>
    <a class="btn btn-primary btn-block" href="<?= View::e(View::url('report_preview', ['week' => $week['isoWeek']])) ?>">
      <?= View::e(t('week.review', ['week' => $week['weekNumber']])) ?>
    </a>
  <?php endif; ?>
<?php endif; ?>
