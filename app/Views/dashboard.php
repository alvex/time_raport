<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;
?>
<h1 class="page-title"><?= View::e(t('dash.hello', ['name' => $name])) ?></h1>
<p class="page-sub"><?= View::e(t('week.title', ['week' => $week['weekNumber']])) ?> · <?= View::e($week['rangeLabel']) ?></p>

<div class="stat-row">
  <div class="stat-card">
    <div class="stat-value"><?= View::e($week['totalHours']) ?> <?= View::e(t('common.hours_short')) ?></div>
    <div class="stat-label"><?= View::e(t('dash.this_week')) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= View::e((string)$week['jobCount']) ?> <?= View::e(tp('unit.jobs', $week['jobCount'])) ?></div>
    <div class="stat-label"><?= View::e(t('dash.this_week')) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-value badge <?= View::e(View::badgeClass($week['weekStatus'])) ?>"><?= View::e(t('status.' . $week['weekStatus'])) ?></div>
    <div class="stat-label"><?= View::e(t('dash.report_label')) ?></div>
  </div>
</div>

<h2 class="section-title"><?= View::e(t('dash.today_title')) ?></h2>
<?php if ($today === []): ?>
  <div class="card empty-card"><?= View::e(t('dash.no_jobs_today')) ?></div>
<?php else: ?>
  <?php foreach ($today as $job): ?>
  <a class="card job-card<?= $nextJob !== null && $job['resid'] === $nextJob['resid'] ? ' job-next' : '' ?>"
     href="<?= View::e(View::url('job', ['resid' => $job['resid']])) ?>">
    <div class="job-time"><?= View::e($job['display_start']) ?>–<?= View::e($job['display_end']) ?></div>
    <div class="job-customer"><?php View::partial('customer', ['b' => $job]); ?></div>
    <?php if ($job['display_address'] !== ''): ?>
    <div class="job-address"><?= View::e($job['display_address']) ?><?= $job['display_city'] !== '' ? ', ' . View::e($job['display_city']) : '' ?></div>
    <?php endif; ?>
    <div class="job-hours"><?= View::e($job['hours']) ?> <?= View::e(t('common.hours_short')) ?></div>
  </a>
  <?php endforeach; ?>
<?php endif; ?>

<div class="action-row">
  <a class="btn btn-primary" href="<?= View::e(View::url('week')) ?>"><?= View::e(t('dash.week_schedule')) ?></a>
  <a class="btn btn-secondary" href="<?= View::e(View::url('report_preview')) ?>"><?= View::e(t('dash.report_btn')) ?></a>
</div>
