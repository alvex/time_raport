<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Csrf;
use App\Core\View;
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<h1 class="page-title"><?php View::partial('customer', ['b' => $b]); ?></h1>
<p class="page-sub"><?= View::e($b['day_heading']) ?> · <span class="badge <?= View::e(View::badgeClass($b['status_key'])) ?>"><?= View::e(t('status.' . $b['status_key'])) ?></span></p>

<div class="card detail-card">
  <div class="detail-time"><?= View::e($b['display_start']) ?>–<?= View::e($b['display_end']) ?></div>
  <div class="detail-hours"><?= View::e($b['hours']) ?> <?= View::e(tp('unit.hours', (float)$b['hours'])) ?><?php if ($b['old_hours_disp'] !== null && $b['old_hours_disp'] !== $b['hours']): ?>
    <span class="job-oldhours"><?= View::e(t('common.booked_note', ['h' => $b['old_hours_disp']])) ?></span><?php endif; ?></div>
</div>

<?php if ($b['display_address'] !== '' || $b['display_city'] !== ''): ?>
<h2 class="section-title"><?= View::e(t('job.address')) ?></h2>
<div class="card">
  <?= View::e($b['display_address']) ?><?= $b['display_city'] !== '' ? '<br>' . View::e($b['display_city']) : '' ?>
</div>
<?php endif; ?>

<?php if ($b['comment'] !== ''): ?>
<h2 class="section-title"><?= View::e(t('job.comment')) ?></h2>
<div class="card comment-card"><?= nl2br(View::e($b['comment'])) ?></div>
<?php endif; ?>

<?php if (!$b['editable']): ?>
<div class="alert alert-warning">
  <?= View::e(t((int)$b['is_pending'] === 1 ? 'job.locked_submitted' : 'job.locked_approved')) ?>
</div>
<?php endif; ?>

<?php if ($b['editable']): ?>
<h2 class="section-title"><?= View::e(t('job.adjust')) ?></h2>
<div class="card">
  <form method="post" action="<?= View::e(View::url('job_update')) ?>" class="time-form">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <input type="hidden" name="resid" value="<?= View::e($b['resid']) ?>">
    <div class="time-inputs">
      <label><?= View::e(t('job.start')) ?>
        <input type="time" name="start_time" step="300" value="<?= View::e($b['display_start']) ?>" required>
      </label>
      <label><?= View::e(t('job.end')) ?>
        <input type="time" name="end_time" step="300" value="<?= View::e($b['display_end']) ?>" required>
      </label>
    </div>
    <button type="submit" class="btn btn-primary btn-block"><?= View::e(t('job.save')) ?></button>
    <p class="form-hint"><?= View::e(t('job.hint_original')) ?>
      <?= View::e(t('job.hint_cancelled')) ?></p>
  </form>
</div>
<?php endif; ?>

<div class="action-row">
  <a class="btn btn-secondary" href="<?= View::e(View::url('week', ['week' => $backWeek])) ?>">&lsaquo; <?= View::e(t('job.back')) ?></a>
</div>
