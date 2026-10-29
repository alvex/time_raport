<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<h1 class="page-title"><?= View::e(t('hist.title')) ?></h1>

<h2 class="section-title"><?= View::e(t('hist.month_title')) ?></h2>

<div class="weekpicker monthpicker">
  <a class="weekpicker-btn" href="<?= View::e(View::url('history', ['month' => $month['prevMonth']])) ?>" aria-label="<?= View::e(t('hist.month_prev')) ?>">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 6 8.5 12l6 6"/></svg>
  </a>
  <div class="weekpicker-title">
    <h3 class="month-heading"><?= View::e($month['label']) ?></h3>
    <?php if (!$month['isCurrent']): ?>
    <a class="weekpicker-today" href="<?= View::e(View::url('history')) ?>"><?= View::e(t('hist.month_current')) ?></a>
    <?php endif; ?>
  </div>
  <a class="weekpicker-btn" href="<?= View::e(View::url('history', ['month' => $month['nextMonth']])) ?>" aria-label="<?= View::e(t('hist.month_next')) ?>">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.5 6l6 6-6 6"/></svg>
  </a>
</div>

<div class="card month-card">
  <div class="month-row">
    <span class="month-label"><?= View::e(t('hist.month_booked')) ?></span>
    <strong class="month-hours"><?= View::e($month['totalHours']) ?> <?= View::e(tp('unit.hours', $month['totalMinutes'] / 60)) ?></strong>
  </div>
  <?php if ($month['jobCount'] === 0): ?>
  <p class="month-empty"><?= View::e(t('hist.month_empty')) ?></p>
  <?php else: ?>
  <p class="month-note"><?= View::e((string)$month['jobCount']) ?> <?= View::e(tp('unit.pass', $month['jobCount'])) ?> · <?= View::e(t('hist.month_basis')) ?></p>
  <?php endif; ?>
</div>

<form class="month-form" method="get" action="index.php">
  <input type="hidden" name="page" value="history">
  <label><?= View::e(t('hist.month_label')) ?>
    <select name="month_m">
      <?php for ($m = 1; $m <= 12; $m++): ?>
      <option value="<?= $m ?>"<?= $m === $month['monthNum'] ? ' selected' : '' ?>><?= View::e(t("date.month_standalone.$m")) ?></option>
      <?php endfor; ?>
    </select>
  </label>
  <label><?= View::e(t('hist.year_label')) ?>
    <select name="month_y">
      <?php foreach ($month['years'] as $y): ?>
      <option value="<?= (int)$y ?>"<?= $y === $month['yearNum'] ? ' selected' : '' ?>><?= (int)$y ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="btn btn-nav" type="submit"><?= View::e(t('hist.month_show')) ?></button>
</form>

<h2 class="section-title"><?= View::e(t('hist.sub')) ?></h2>

<?php foreach ($weeks as $w): ?>
  <?php if ($w['jobCount'] === 0) continue; ?>
  <a class="card history-card" href="<?= View::e(View::url('report_preview', ['week' => $w['isoWeek']])) ?>">
    <div class="history-week">
      <div class="job-customer"><?= View::e(t('week.title', ['week' => $w['weekNumber']])) ?></div>
      <div class="job-address"><?= View::e($w['rangeLabel']) ?> · <?= View::e((string)$w['jobCount']) ?> <?= View::e(tp('unit.jobs', $w['jobCount'])) ?></div>
    </div>
    <div class="history-meta">
      <div class="history-hours"><?= View::e($w['totalHours']) ?> <?= View::e(t('common.hours_short')) ?></div>
      <span class="badge <?= View::e(View::badgeClass($w['status'])) ?>"><?= View::e(t('status.' . $w['status'])) ?></span>
    </div>
  </a>
<?php endforeach; ?>

<?php if (array_sum(array_column($weeks, 'jobCount')) === 0): ?>
  <div class="card empty-card"><?= View::e(t('hist.none')) ?></div>
<?php endif; ?>
