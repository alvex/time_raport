<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;
?>
<h1 class="page-title">Hej <?= View::e($name) ?></h1>
<p class="page-sub">Vecka <?= View::e((string)$week['weekNumber']) ?> · <?= View::e($week['rangeLabel']) ?></p>

<div class="stat-row">
  <div class="stat-card">
    <div class="stat-value"><?= View::e($week['totalHours']) ?> h</div>
    <div class="stat-label">denna vecka</div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= View::e((string)$week['jobCount']) ?> jobb</div>
    <div class="stat-label">denna vecka</div>
  </div>
  <div class="stat-card">
    <div class="stat-value badge <?= View::e(View::badgeClass($week['weekStatus'])) ?>"><?= View::e($week['weekStatus']) ?></div>
    <div class="stat-label">tidrapport</div>
  </div>
</div>

<h2 class="section-title">Idag</h2>
<?php if ($today === []): ?>
  <div class="card empty-card">Inga jobb idag.</div>
<?php else: ?>
  <?php foreach ($today as $job): ?>
  <a class="card job-card<?= $nextJob !== null && $job['resid'] === $nextJob['resid'] ? ' job-next' : '' ?>"
     href="<?= View::e(View::url('job', ['resid' => $job['resid']])) ?>">
    <div class="job-time"><?= View::e($job['display_start']) ?>–<?= View::e($job['display_end']) ?></div>
    <div class="job-customer"><?php View::partial('customer', ['b' => $job]); ?></div>
    <?php if ($job['display_address'] !== ''): ?>
    <div class="job-address"><?= View::e($job['display_address']) ?><?= $job['display_city'] !== '' ? ', ' . View::e($job['display_city']) : '' ?></div>
    <?php endif; ?>
    <div class="job-hours"><?= View::e($job['hours']) ?> h</div>
  </a>
  <?php endforeach; ?>
<?php endif; ?>

<div class="action-row">
  <a class="btn btn-primary" href="<?= View::e(View::url('week')) ?>">Veckans schema</a>
  <a class="btn btn-secondary" href="<?= View::e(View::url('report_preview')) ?>">Tidrapport</a>
</div>
