<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<div class="week-nav">
  <a class="btn btn-nav" href="<?= View::e(View::url('week', ['week' => $prevWeek])) ?>">&lsaquo; Föregående</a>
  <div class="week-nav-title">
    <div class="week-nav-week">Vecka <?= View::e((string)$week['weekNumber']) ?></div>
    <div class="week-nav-range"><?= View::e($week['rangeLabel']) ?></div>
    <?php if ($week['isoWeek'] !== $currentWeek): ?>
    <a class="week-nav-today" href="<?= View::e(View::url('week')) ?>">Denna vecka</a>
    <?php endif; ?>
  </div>
  <a class="btn btn-nav" href="<?= View::e(View::url('week', ['week' => $nextWeek])) ?>">Nästa &rsaquo;</a>
</div>

<?php foreach ($week['days'] as $day): ?>
  <?php if ($day['bookings'] === []) continue; ?>
  <h2 class="day-heading<?= $day['isToday'] ? ' day-today' : '' ?>">
    <?= View::e($day['heading']) ?><?= $day['isToday'] ? ' · idag' : '' ?>
  </h2>
  <?php foreach ($day['bookings'] as $b): ?>
  <div class="card job-card">
    <a class="job-main" href="<?= View::e(View::url('job', ['resid' => $b['resid']])) ?>">
      <div class="job-row">
        <div class="job-time"><?= View::e($b['display_start']) ?>–<?= View::e($b['display_end']) ?></div>
        <span class="badge <?= View::e(View::badgeClass($b['status'])) ?>"><?= View::e($b['status']) ?></span>
      </div>
      <div class="job-customer"><?= View::e($b['customer']) ?></div>
      <?php if ($b['display_address'] !== ''): ?>
      <div class="job-address">📍 <?= View::e($b['display_address']) ?><?= $b['display_city'] !== '' ? ', ' . View::e($b['display_city']) : '' ?></div>
      <?php endif; ?>
      <div class="job-hours"><?= View::e($b['hours']) ?> h<?php if ($b['old_hours_disp'] !== null && $b['old_hours_disp'] !== $b['hours']): ?>
        <span class="job-oldhours">(bokat: <?= View::e($b['old_hours_disp']) ?> h)</span><?php endif; ?></div>
    </a>
    <?php if ($b['maps_url'] !== null): ?>
    <div class="job-actions">
      <a class="btn btn-maps" href="<?= View::e($b['maps_url']) ?>" target="_blank" rel="noopener">🧭 Vägbeskrivning</a>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
<?php endforeach; ?>

<?php if ($week['jobCount'] === 0): ?>
  <div class="card empty-card">Inga jobb den här veckan.</div>
<?php else: ?>
  <div class="card total-card">
    <span>Totalt vecka <?= View::e((string)$week['weekNumber']) ?></span>
    <strong><?= View::e($week['totalHours']) ?> h</strong>
  </div>
  <div class="action-row">
    <a class="btn btn-primary" href="<?= View::e(View::url('report_preview', ['week' => $week['isoWeek']])) ?>">
      Tidrapport för vecka <?= View::e((string)$week['weekNumber']) ?>
    </a>
  </div>
<?php endif; ?>
