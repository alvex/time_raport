<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Csrf;
use App\Core\View;
use App\Services\ScheduleService;

$tr_sched = new ScheduleService();
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<h1 class="page-title"><?= View::e(t('report.title')) ?></h1>
<p class="page-sub"><?= View::e(t('week.title', ['week' => $report['weekNumber']])) ?> · <?= View::e($report['rangeLabel']) ?>
  · <span class="badge <?= View::e(View::badgeClass($report['weekStatus'])) ?>"><?= View::e(t('status.' . $report['weekStatus'])) ?></span></p>

<div class="week-nav week-nav-compact">
  <a class="btn btn-nav" href="<?= View::e(View::url('report_preview', ['week' => $tr_sched->adjacentWeek($report['isoWeek'], -1)])) ?>">&lsaquo; <?= View::e(t('week.prev')) ?></a>
  <a class="btn btn-nav" href="<?= View::e(View::url('report_preview', ['week' => $tr_sched->adjacentWeek($report['isoWeek'], 1)])) ?>"><?= View::e(t('week.next')) ?> &rsaquo;</a>
</div>

<?php if ($report['bookings'] === []): ?>
  <div class="card empty-card"><?= View::e(t('report.none')) ?></div>
<?php else: ?>

<div class="report-table-wrap">
  <table class="report-table">
    <thead>
      <tr><th><?= View::e(t('report.h_date')) ?></th><th><?= View::e(t('report.h_customer')) ?></th><th class="col-time"><?= View::e(t('report.h_time')) ?></th><th class="col-hours"><?= View::e(t('report.h_hours')) ?></th></tr>
    </thead>
    <tbody>
    <?php foreach ($report['bookings'] as $b): ?>
      <tr>
        <td><?= View::e($b['display_date']) ?></td>
        <td>
          <?php View::partial('customer', ['b' => $b]); ?>
          <?php if ($b['display_address'] !== ''): ?>
          <div class="report-address"><?= View::e($b['display_address']) ?><?= $b['display_city'] !== '' ? ', ' . View::e($b['display_city']) : '' ?></div>
          <?php endif; ?>
          <?php if ($b['comment'] !== ''): ?>
          <div class="report-comment"><?= View::e($b['comment']) ?></div>
          <?php endif; ?>
        </td>
        <td class="col-time"><?= View::e($b['display_start']) ?>–<?= View::e($b['display_end']) ?></td>
        <td class="col-hours"><?= View::e($b['hours']) ?><?php
          if ($b['old_hours_disp'] !== null && (int)$b['is_pending'] === 1 && $b['old_hours_disp'] !== $b['hours']): ?>
          <span class="job-oldhours">(<?= View::e($b['old_hours_disp']) ?>)</span><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="3"><?= View::e(t('report.total_label')) ?> · <?= View::e((string)$report['jobCount']) ?> <?= View::e(tp('unit.pass', $report['jobCount'])) ?></td>
          <td class="col-hours"><strong><?= View::e($report['totalHours']) ?> <?= View::e(t('common.hours_short')) ?></strong></td></tr>
    </tfoot>
  </table>
</div>

<?php if ($report['allApproved']): ?>
  <div class="alert alert-notice"><?= View::e(t('report.week_locked')) ?></div>
<?php elseif ($report['canSubmit']): ?>
  <div class="card submit-card">
    <p><?= View::e(t('report.confirm_info')) ?></p>
    <form method="post" action="<?= View::e(View::url('report_submit')) ?>" data-confirm="<?= View::e(t('report.confirm_again')) ?>">
      <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
      <input type="hidden" name="_once" value="<?= View::e($onceToken) ?>">
      <input type="hidden" name="week" value="<?= View::e($report['isoWeek']) ?>">
      <button type="submit" class="btn btn-primary btn-block"><?= View::e(t('report.submit')) ?></button>
    </form>
  </div>
<?php endif; ?>

<?php endif; ?>

<div class="action-row">
  <a class="btn btn-secondary" href="<?= View::e(View::url('week', ['week' => $report['isoWeek']])) ?>">&lsaquo; <?= View::e(t('report.back')) ?></a>
</div>
