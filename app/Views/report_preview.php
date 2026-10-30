<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Csrf;
use App\Core\View;
use App\Services\ScheduleService;
use App\Services\TimeFormatter;

$tr_sched = new ScheduleService();
$tr_h = t('common.hours_short');
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<h1 class="hf-page-title"><?= View::e(t('report.title')) ?></h1>

<div class="hf-section">
  <div class="hf-period">
    <a class="hf-iconbtn" href="<?= View::e(View::url('report_preview', ['week' => $tr_sched->adjacentWeek($report['isoWeek'], -1)])) ?>" aria-label="<?= View::e(t('week.prev')) ?>"><?= View::icon('chev-left') ?></a>
    <div class="hf-period__label" aria-live="polite">
      <p class="hf-period__title"><?= View::e(t('week.title', ['week' => $report['weekNumber']])) ?></p>
      <p class="hf-period__range"><?= View::e($report['rangeLabel']) ?></p>
    </div>
    <a class="hf-iconbtn" href="<?= View::e(View::url('report_preview', ['week' => $tr_sched->adjacentWeek($report['isoWeek'], 1)])) ?>" aria-label="<?= View::e(t('week.next')) ?>"><?= View::icon('chev-right') ?></a>
  </div>
</div>

<?php if ($report['bookings'] === []): ?>
  <div class="hf-section"><div class="hf-empty"><?= View::e(t('report.none')) ?></div></div>
<?php else: ?>

<div class="hf-section">
  <section class="hf-summary" aria-label="<?= View::e(t('summary.aria')) ?>">
    <div class="hf-summary__row">
      <div class="hf-summary__stats">
        <div><div class="hf-stat__value"><?= View::e(View::hours($report['totalHours'])) ?> <?= View::e($tr_h) ?></div><div class="hf-stat__label"><?= View::e(t('dash.this_week')) ?></div></div>
        <div><div class="hf-stat__value"><?= View::e((string)$report['jobCount']) ?></div><div class="hf-stat__label"><?= View::e(tp('unit.pass', $report['jobCount'])) ?></div></div>
      </div>
      <div>
        <div class="hf-stat__label" style="margin-bottom:4px"><?= View::e(t('nav.report')) ?></div>
        <?php View::partial('status', ['status' => $report['weekStatus']]); ?>
      </div>
    </div>
    <div class="hf-summary__actions">
      <?php if ($report['canSubmit']): ?>
      <form method="post" action="<?= View::e(View::url('report_submit')) ?>" data-confirm="<?= View::e(t('report.confirm_again')) ?>">
        <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
        <input type="hidden" name="_once" value="<?= View::e($onceToken) ?>">
        <input type="hidden" name="week" value="<?= View::e($report['isoWeek']) ?>">
        <button type="submit" class="hf-btn hf-btn--primary hf-btn--block"><?= View::icon('send') ?><?= View::e(t('report.submit', ['week' => $report['weekNumber']])) ?></button>
      </form>
      <p class="hf-summary__hint"><?= View::e(t('report.hint')) ?></p>
      <?php elseif ($report['invalidCount'] > 0): ?>
      <button type="button" class="hf-btn hf-btn--primary hf-btn--block" disabled><?= View::e(t('report.submit', ['week' => $report['weekNumber']])) ?></button>
      <p class="report-blocked" role="alert"><?= View::e(tp('report.blocked', $report['invalidCount'])) ?></p>
      <?php elseif ($report['allApproved']): ?>
      <p class="hf-summary__hint"><?= View::e(t('report.week_locked')) ?></p>
      <?php elseif ($report['mixedLocked']): ?>
      <p class="report-blocked" role="alert"><?= View::e(t('report.mixed_locked')) ?></p>
      <?php elseif ($report['submittedLocked']): ?>
      <button type="button" class="hf-btn hf-btn--primary hf-btn--block" disabled><?= View::e(t('report.submitted_btn')) ?></button>
      <p class="hf-summary__hint"><?= View::e(t('report.submitted_locked')) ?></p>
      <?php endif; ?>
    </div>
  </section>
</div>

<section class="hf-section hf-entries hf-entries--responsive">
  <div class="hf-section-head">
    <h2 class="hf-section-title"><?= View::e(t('report.review_title')) ?></h2>
    <span class="hf-section-note hf-num"><?= View::e((string)$report['jobCount']) ?> <?= View::e(tp('unit.pass', $report['jobCount'])) ?></span>
  </div>

  <div class="hf-entries__cards hf-stack">
    <?php foreach ($report['bookings'] as $b): ?>
    <?php $tr_hasDiff = $b['old_hours_disp'] !== null && $b['old_hours_disp'] !== $b['hours']; ?>
    <article class="hf-entry">
      <div class="hf-entry__top">
        <div class="hf-entry__when"><b><?= View::e(TimeFormatter::formatShortDate((int)$b['start_date'])) ?></b> · <?= View::e($b['display_start']) ?>–<?= View::e($b['display_end']) ?></div>
        <div class="hf-entry__hours"><?= View::e(View::hours($b['hours'])) ?> <?= View::e($tr_h) ?></div>
      </div>
      <h3 class="hf-entry__customer"><?= View::e($b['customer']) ?></h3>
      <p class="hf-entry__meta"><?= View::e(t('common.customer_no')) ?> <?= View::e($b['customer_no']) ?><?php if ($b['display_address'] !== ''): ?> · <?= View::e($b['display_address']) ?><?= $b['display_city'] !== '' ? ', ' . View::e($b['display_city']) : '' ?><?php endif; ?></p>
      <?php if ($b['comment'] !== ''): ?>
      <p class="hf-entry__note"><span><?= View::e(t('job.comment')) ?>:</span> <?= View::e($b['comment']) ?></p>
      <?php endif; ?>
      <?php if ($tr_hasDiff): ?>
      <p class="hf-diff"><?= View::icon('info') ?><span><?= View::e(t('diff.booked', ['h' => View::hours($b['old_hours_disp']) . ' ' . $tr_h])) ?></span></p>
      <?php endif; ?>
      <?php if ($b['editable']): ?>
      <div class="hf-entry__foot">
        <a class="hf-btn hf-btn--text" href="<?= View::e(View::url('job', ['resid' => $b['resid'], 'from' => 'report'])) ?>" aria-label="<?= View::e(t('report.adjust')) ?> – <?= View::e($b['customer']) ?>"><?= View::icon('pencil') ?><?= View::e(t('report.adjust')) ?></a>
      </div>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
    <div class="hf-entries__total">
      <span><?= View::e(t('report.total_label')) ?> · <?= View::e((string)$report['jobCount']) ?> <?= View::e(tp('unit.pass', $report['jobCount'])) ?></span>
      <span class="hf-num"><?= View::e(View::hours($report['totalHours'])) ?> <?= View::e($tr_h) ?></span>
    </div>
  </div>

  <div class="hf-table-wrap">
    <table class="hf-table">
      <caption class="hf-visually-hidden"><?= View::e(t('report.table_caption', ['week' => $report['weekNumber']])) ?></caption>
      <thead>
        <tr>
          <th scope="col"><?= View::e(t('report.h_date')) ?></th>
          <th scope="col"><?= View::e(t('report.h_customer')) ?></th>
          <th scope="col" class="hf-num"><?= View::e(t('report.h_time')) ?></th>
          <th scope="col" class="hf-num"><?= View::e(t('report.h_hours')) ?></th>
          <th scope="col"><span class="hf-visually-hidden"><?= View::e(t('report.action_col')) ?></span></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($report['bookings'] as $b): ?>
        <?php $tr_hasDiff = $b['old_hours_disp'] !== null && $b['old_hours_disp'] !== $b['hours']; ?>
        <tr>
          <td class="hf-num"><?= View::e(TimeFormatter::formatShortDate((int)$b['start_date'])) ?></td>
          <td>
            <strong><?= View::e($b['customer']) ?></strong>
            <div class="hf-entry__meta"><?= View::e(t('common.customer_no')) ?> <?= View::e($b['customer_no']) ?><?php if ($b['display_address'] !== ''): ?> · <?= View::e($b['display_address']) ?><?= $b['display_city'] !== '' ? ', ' . View::e($b['display_city']) : '' ?><?php endif; ?></div>
            <?php if ($b['comment'] !== ''): ?>
            <div class="hf-entry__note"><span><?= View::e(t('job.comment')) ?>:</span> <?= View::e($b['comment']) ?></div>
            <?php endif; ?>
          </td>
          <td class="hf-num"><?= View::e($b['display_start']) ?>–<?= View::e($b['display_end']) ?></td>
          <td class="hf-num"><strong><?= View::e(View::hours($b['hours'])) ?> <?= View::e($tr_h) ?></strong>
            <?php if ($tr_hasDiff): ?><div class="hf-entry__meta"><?= View::e(t('diff.booked', ['h' => View::hours($b['old_hours_disp']) . ' ' . $tr_h])) ?></div><?php endif; ?>
          </td>
          <td>
            <?php if ($b['editable']): ?>
            <a class="hf-btn hf-btn--text" href="<?= View::e(View::url('job', ['resid' => $b['resid'], 'from' => 'report'])) ?>" aria-label="<?= View::e(t('report.adjust')) ?> – <?= View::e($b['customer']) ?>"><?= View::icon('pencil') ?><?= View::e(t('report.adjust_short')) ?></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3"><?= View::e(t('report.total_label')) ?> · <?= View::e((string)$report['jobCount']) ?> <?= View::e(tp('unit.pass', $report['jobCount'])) ?></td>
          <td class="hf-num"><?= View::e(View::hours($report['totalHours'])) ?> <?= View::e($tr_h) ?></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</section>

<?php endif; ?>

<div class="hf-section">
  <a class="hf-btn hf-btn--text" href="<?= View::e(View::url('week', ['week' => $report['isoWeek']])) ?>"><?= View::icon('chev-left') ?><?= View::e(t('report.back')) ?></a>
</div>
