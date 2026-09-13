<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;

$tr_pin  = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-5.5-7-11a7 7 0 1 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>';
$tr_nav  = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 17c0-4 3-7 8-7h5"/><path d="M13 5.5 17.5 10 13 14.5"/></svg>';
$tr_warn = '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2.6 22.6 20.4a1 1 0 0 1-.86 1.5H2.26a1 1 0 0 1-.86-1.5L12 2.6z"/><path d="M12 9v5" stroke="#fff" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="17.2" r="1.2" fill="#fff"/></svg>';
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<div class="weekpicker">
  <a class="weekpicker-btn" href="<?= View::e(View::url('week', ['week' => $prevWeek])) ?>" aria-label="Föregående vecka">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 6 8.5 12l6 6"/></svg>
  </a>
  <div class="weekpicker-title">
    <h1>Vecka <?= View::e((string)$week['weekNumber']) ?></h1>
    <p><?= View::e($week['rangeLabel']) ?></p>
    <?php if ($week['isoWeek'] !== $currentWeek): ?>
    <a class="weekpicker-today" href="<?= View::e(View::url('week')) ?>">Till denna vecka</a>
    <?php endif; ?>
  </div>
  <a class="weekpicker-btn" href="<?= View::e(View::url('week', ['week' => $nextWeek])) ?>" aria-label="Nästa vecka">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.5 6l6 6-6 6"/></svg>
  </a>
</div>

<?php foreach ($week['days'] as $day): ?>
  <?php if ($day['bookings'] === []) continue; ?>
  <div class="day-head<?= $day['isToday'] ? ' day-today' : '' ?>">
    <h2><?= View::e($day['heading']) ?><?= $day['isToday'] ? ' · idag' : '' ?></h2>
    <span class="day-count"><?= count($day['bookings']) ?> pass</span>
  </div>

  <?php foreach ($day['bookings'] as $b): ?>
  <article class="card job-card<?= $b['invalid'] ? ' job-invalid' : '' ?>">
    <a class="job-main" href="<?= View::e(View::url('job', ['resid' => $b['resid']])) ?>">
      <div class="job-row">
        <div>
          <div class="job-time"><?= View::e($b['display_start']) ?>–<?= View::e($b['display_end']) ?></div>
          <div class="job-duration"><?= View::e($b['hours']) ?> <?= $b['hours'] === '1' ? 'timme' : 'timmar' ?><?php
            if ($b['old_hours_disp'] !== null && $b['old_hours_disp'] !== $b['hours']): ?>
            <span class="job-oldhours">(bokat: <?= View::e($b['old_hours_disp']) ?> h)</span><?php endif; ?></div>
        </div>
        <span class="badge <?= View::e(View::badgeClass($b['status'])) ?>"><?= View::e($b['status']) ?></span>
      </div>
      <div class="job-customer"><?= View::e($b['customer']) ?></div>
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
        <strong>Passet saknar arbetstid</strong><br>
        <?= View::e($b['invalid_reason']) ?> Rätta tiden innan veckans rapport skickas.
      </div>
    </div>
    <?php endif; ?>

    <?php if ($b['invalid'] || $b['maps_url'] !== null): ?>
    <div class="job-actions">
      <?php if ($b['invalid']): ?>
      <a class="btn btn-fix" href="<?= View::e(View::url('job', ['resid' => $b['resid']])) ?>">Rätta pass</a>
      <?php endif; ?>
      <?php if ($b['maps_url'] !== null): ?>
      <a class="btn btn-maps" href="<?= View::e($b['maps_url']) ?>" target="_blank" rel="noopener noreferrer">
        <?= $tr_nav ?> <?= $b['invalid'] ? 'Vägbeskrivning' : 'Starta vägbeskrivning' ?>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>
<?php endforeach; ?>

<?php if ($week['jobCount'] === 0): ?>
  <div class="card empty-card">Inga jobb den här veckan.</div>
<?php else: ?>
  <div class="card total-card">
    <span>Totalt vecka <?= View::e((string)$week['weekNumber']) ?></span>
    <strong><?= View::e($week['totalHours']) ?> h</strong>
  </div>

  <?php if ($week['invalidCount'] > 0): ?>
    <span class="btn btn-primary btn-block btn-disabled" aria-disabled="true">
      Granska tidrapport för vecka <?= View::e((string)$week['weekNumber']) ?>
    </span>
    <p class="report-blocked" role="alert">
      <?= View::e((string)$week['invalidCount']) ?> pass behöver rättas innan rapporten kan skickas.
    </p>
  <?php else: ?>
    <a class="btn btn-primary btn-block" href="<?= View::e(View::url('report_preview', ['week' => $week['isoWeek']])) ?>">
      Granska tidrapport för vecka <?= View::e((string)$week['weekNumber']) ?>
    </a>
  <?php endif; ?>
<?php endif; ?>
