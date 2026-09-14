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
<?php
$tr_key = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="7.5" cy="15.5" r="4.5"/><path d="M10.7 12.3 21 2m-3.5 3.5 3 3M14 9l2.5 2.5"/></svg>';
$tr_nav = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 17c0-4 3-7 8-7h5"/><path d="M13 5.5 17.5 10 13 14.5"/></svg>';
?>
<?php if ($today === []): ?>
  <div class="card empty-card"><?= View::e(t('dash.no_jobs_today')) ?></div>
<?php else: ?>
  <?php foreach ($today as $job): ?>
  <article class="card job-card<?= $nextJob !== null && $job['resid'] === $nextJob['resid'] ? ' job-next' : '' ?>">
    <a class="job-main" href="<?= View::e(View::url('job', ['resid' => $job['resid']])) ?>">
      <div class="job-time"><?= View::e($job['display_start']) ?>–<?= View::e($job['display_end']) ?></div>
      <div class="job-customer"><?php View::partial('customer', ['b' => $job, 'nameOnly' => $job['has_key']]); ?></div>
      <?php if ($job['display_address'] !== ''): ?>
      <div class="job-address"><?= View::e($job['display_address']) ?><?= $job['display_city'] !== '' ? ', ' . View::e($job['display_city']) : '' ?></div>
      <?php endif; ?>
      <?php if ($job['has_key']): ?>
      <div class="key-line"><?= $tr_key ?>
        <span><?= View::e(t('key.exists')) ?> · <?= View::e(t('common.customer_no')) ?>: <?= View::e($job['customer_no']) ?></span>
      </div>
      <?php endif; ?>
      <div class="job-hours"><?= View::e($job['hours']) ?> <?= View::e(t('common.hours_short')) ?></div>
    </a>
    <?php if ($job['maps_url_driving'] !== null): ?>
    <div class="job-actions">
      <a class="btn btn-maps" href="<?= View::e($job['maps_url_driving']) ?>" target="_blank" rel="noopener noreferrer">
        <?= $tr_nav ?> <?= View::e(t('maps.start')) ?>
      </a>
    </div>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>
<?php endif; ?>

<div class="action-row">
  <a class="btn btn-primary" href="<?= View::e(View::url('week')) ?>"><?= View::e(t('dash.week_schedule')) ?></a>
  <a class="btn btn-secondary" href="<?= View::e(View::url('report_preview')) ?>"><?= View::e(t('dash.report_btn')) ?></a>
</div>
