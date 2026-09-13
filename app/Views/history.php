<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<h1 class="page-title">Tidrapporter</h1>
<p class="page-sub">Senaste 12 veckorna</p>

<?php foreach ($weeks as $w): ?>
  <?php if ($w['jobCount'] === 0) continue; ?>
  <a class="card history-card" href="<?= View::e(View::url('report_preview', ['week' => $w['isoWeek']])) ?>">
    <div class="history-week">
      <div class="job-customer">Vecka <?= View::e((string)$w['weekNumber']) ?></div>
      <div class="job-address"><?= View::e($w['rangeLabel']) ?> · <?= View::e((string)$w['jobCount']) ?> jobb</div>
    </div>
    <div class="history-meta">
      <div class="history-hours"><?= View::e($w['totalHours']) ?> h</div>
      <span class="badge <?= View::e(View::badgeClass($w['status'])) ?>"><?= View::e($w['status']) ?></span>
    </div>
  </a>
<?php endforeach; ?>

<?php if (array_sum(array_column($weeks, 'jobCount')) === 0): ?>
  <div class="card empty-card">Inga jobb de senaste 12 veckorna.</div>
<?php endif; ?>
