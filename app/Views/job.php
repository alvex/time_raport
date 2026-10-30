<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Csrf;
use App\Core\View;

$tr_h = t('common.hours_short');
$tr_hasDiff = !$b['invalid'] && $b['old_hours_disp'] !== null && $b['old_hours_disp'] !== $b['hours'];
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<h1 class="hf-page-title"><?= View::e($b['customer']) ?></h1>
<p class="hf-page-sub"><?= View::e($b['day_heading']) ?> <?php View::partial('status', ['status' => $b['status_key']]); ?></p>

<div class="hf-section">
  <article class="hf-shift">
    <div class="hf-shift__top">
      <p class="hf-shift__time"><?= View::e($b['display_start']) ?>–<?= View::e($b['display_end']) ?></p>
      <p class="hf-shift__hours"><?= View::e(View::hours($b['hours'])) ?> <?= View::e($tr_h) ?></p>
    </div>

    <?php if ($b['invalid']): ?>
    <p class="hf-diff" role="alert"><?= View::icon('info') ?><span><strong><?= View::e(t('invalid.title')) ?></strong>
      <?= View::e(t('invalid.reason_negative')) ?> <?= View::e(t('invalid.fix_hint')) ?></span></p>
    <?php elseif ($tr_hasDiff): ?>
    <?php
      $tr_diff = View::e(t('diff.full', ['actual' => "\u{E000}", 'booked' => "\u{E001}"]));
      $tr_diff = str_replace(
          ["\u{E000}", "\u{E001}"],
          ['<strong>' . View::e(View::hours($b['hours']) . ' ' . $tr_h) . '</strong>',
           '<strong>' . View::e(View::hours($b['old_hours_disp']) . ' ' . $tr_h) . '</strong>'],
          $tr_diff
      );
    ?>
    <p class="hf-diff"><?= View::icon('info') ?><span><?= $tr_diff ?></span></p>
    <?php endif; ?>

    <dl class="hf-info">
      <?php if ($b['has_key']): ?>
      <div class="hf-info__row hf-info__row--attn"><dt><?= View::e(t('common.key_aria')) ?></dt><?= View::icon('key') ?><dd><?= View::e(t('key.exists')) ?></dd></div>
      <?php endif; ?>
      <?php if ($b['display_address'] !== ''): ?>
      <div class="hf-info__row"><dt><?= View::e(t('job.address')) ?></dt><?= View::icon('pin') ?><dd><?= View::e($b['display_address']) ?><?= $b['display_city'] !== '' ? ', ' . View::e($b['display_city']) : '' ?></dd></div>
      <?php endif; ?>
      <div class="hf-info__row"><dt><?= View::e(t('common.customer_no')) ?></dt><?= View::icon('hash') ?><dd><?= View::e(t('common.customer_no')) ?> <?= View::e($b['customer_no']) ?></dd></div>
      <?php if ($b['comment'] !== ''): ?>
      <div class="hf-info__row"><dt><?= View::e(t('job.comment')) ?></dt><?= View::icon('comment') ?><dd><span class="hf-info__label"><?= View::e(t('job.comment')) ?>:</span> <?= View::e($b['comment']) ?></dd></div>
      <?php endif; ?>
    </dl>
  </article>
</div>

<?php if (!$b['editable']): ?>
<div class="hf-section">
  <div class="alert alert-warning" style="margin:0">
    <?= View::e(t((int)$b['is_pending'] === 1 ? 'job.locked_submitted' : 'job.locked_approved')) ?>
  </div>
</div>
<?php endif; ?>

<?php if ($b['editable']): ?>
<div class="hf-section">
  <section class="hf-panel" aria-labelledby="tr-adjust-title">
    <h2 class="hf-panel__title" id="tr-adjust-title"><?= View::e(t('job.adjust')) ?></h2>
    <form method="post" action="<?= View::e(View::url('job_update')) ?>">
      <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
      <input type="hidden" name="resid" value="<?= View::e($b['resid']) ?>">
      <?php if (!empty($fromReport)): ?><input type="hidden" name="from" value="report"><?php endif; ?>
      <div class="time-inputs">
        <div class="hf-field">
          <label for="tr-start"><?= View::e(t('job.start')) ?></label>
          <input id="tr-start" type="time" name="start_time" step="300" value="<?= View::e($b['display_start']) ?>" required>
        </div>
        <div class="hf-field">
          <label for="tr-end"><?= View::e(t('job.end')) ?></label>
          <input id="tr-end" type="time" name="end_time" step="300" value="<?= View::e($b['display_end']) ?>" required>
        </div>
      </div>
      <button type="submit" class="hf-btn hf-btn--primary hf-btn--block"><?= View::e(t('job.save')) ?></button>
      <p class="form-hint"><?= View::e(t('job.hint_original')) ?>
        <?= View::e(t('job.hint_cancelled')) ?></p>
    </form>
  </section>
</div>
<?php endif; ?>

<div class="hf-section">
  <?php if (!empty($fromReport)): ?>
  <a class="hf-btn hf-btn--text" href="<?= View::e(View::url('report_preview', ['week' => $backWeek])) ?>"><?= View::icon('chev-left') ?><?= View::e(t('job.back_report')) ?></a>
  <?php else: ?>
  <a class="hf-btn hf-btn--text" href="<?= View::e(View::url('week', ['week' => $backWeek])) ?>"><?= View::icon('chev-left') ?><?= View::e(t('job.back')) ?></a>
  <?php endif; ?>
</div>
