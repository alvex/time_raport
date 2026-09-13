<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Csrf;
use App\Core\View;
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<h1 class="page-title"><?php View::partial('customer', ['b' => $b]); ?></h1>
<p class="page-sub"><?= View::e($b['day_heading']) ?> · <span class="badge <?= View::e(View::badgeClass($b['status'])) ?>"><?= View::e($b['status']) ?></span></p>

<div class="card detail-card">
  <div class="detail-time"><?= View::e($b['display_start']) ?>–<?= View::e($b['display_end']) ?></div>
  <div class="detail-hours"><?= View::e($b['hours']) ?> timmar<?php if ($b['old_hours_disp'] !== null && $b['old_hours_disp'] !== $b['hours']): ?>
    <span class="job-oldhours">(bokat: <?= View::e($b['old_hours_disp']) ?> h)</span><?php endif; ?></div>
</div>

<?php if ($b['display_address'] !== '' || $b['display_city'] !== ''): ?>
<h2 class="section-title">Adress</h2>
<div class="card">
  <?= View::e($b['display_address']) ?><?= $b['display_city'] !== '' ? '<br>' . View::e($b['display_city']) : '' ?>
</div>
<?php endif; ?>

<?php if ($b['comment'] !== ''): ?>
<h2 class="section-title">Kommentar</h2>
<div class="card comment-card"><?= nl2br(View::e($b['comment'])) ?></div>
<?php endif; ?>

<?php if ($b['editable']): ?>
<h2 class="section-title">Justera tider</h2>
<div class="card">
  <form method="post" action="<?= View::e(View::url('job_update')) ?>" class="time-form">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <input type="hidden" name="resid" value="<?= View::e($b['resid']) ?>">
    <div class="time-inputs">
      <label>Start
        <input type="time" name="start_time" step="300" value="<?= View::e($b['display_start']) ?>" required>
      </label>
      <label>Slut
        <input type="time" name="end_time" step="300" value="<?= View::e($b['display_end']) ?>" required>
      </label>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Spara tider</button>
    <p class="form-hint">Ursprungligt bokade timmar sparas och visas i tidrapporten.
      Blev passet avbokat? Sätt samma start- och sluttid så rapporteras 0 timmar.</p>
  </form>
</div>
<?php endif; ?>

<div class="action-row">
  <a class="btn btn-secondary" href="<?= View::e(View::url('week', ['week' => $backWeek])) ?>">&lsaquo; Tillbaka till veckan</a>
</div>
