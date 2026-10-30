<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;

$tr_h = t('common.hours_short');
$tr_daysInMonth = (int)date('t', mktime(0, 0, 0, $month['monthNum'], 1, $month['yearNum']));
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<h1 class="hf-page-title"><?= View::e(t('hist.title')) ?></h1>

<div class="hf-section">
  <section class="hf-panel" aria-labelledby="tr-month-title">
    <h2 class="hf-panel__title" id="tr-month-title"><?= View::e(t('hist.month_title')) ?></h2>
    <div class="hf-period">
      <a class="hf-iconbtn" href="<?= View::e(View::url('history', ['month' => $month['prevMonth']])) ?>" aria-label="<?= View::e(t('hist.month_prev')) ?>"><?= View::icon('chev-left') ?></a>
      <div class="hf-period__label">
        <p class="hf-period__title"><?= View::e($month['label']) ?></p>
        <p class="hf-period__range">1–<?= $tr_daysInMonth ?> <?= View::e(t('date.month.' . $month['monthNum'])) ?></p>
        <?php if (!$month['isCurrent']): ?>
        <a class="hf-period__today" href="<?= View::e(View::url('history')) ?>"><?= View::e(t('hist.month_current')) ?></a>
        <?php endif; ?>
      </div>
      <a class="hf-iconbtn" href="<?= View::e(View::url('history', ['month' => $month['nextMonth']])) ?>" aria-label="<?= View::e(t('hist.month_next')) ?>"><?= View::icon('chev-right') ?></a>
    </div>

    <details class="hf-details">
      <summary class="hf-btn hf-btn--text" style="padding-left:0"><?= View::e(t('hist.month_pick')) ?></summary>
      <form method="get" action="index.php">
        <input type="hidden" name="page" value="history">
        <div class="hf-pickers">
          <div class="hf-field">
            <label for="tr-month-m"><?= View::e(t('hist.month_label')) ?></label>
            <select id="tr-month-m" class="hf-select" name="month_m">
              <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>"<?= $m === $month['monthNum'] ? ' selected' : '' ?>><?= View::e(t("date.month_standalone.$m")) ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="hf-field">
            <label for="tr-month-y"><?= View::e(t('hist.year_label')) ?></label>
            <select id="tr-month-y" class="hf-select" name="month_y">
              <?php foreach ($month['years'] as $y): ?>
              <option value="<?= (int)$y ?>"<?= $y === $month['yearNum'] ? ' selected' : '' ?>><?= (int)$y ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button class="hf-btn hf-btn--secondary hf-btn--block" type="submit"><?= View::e(t('hist.month_show')) ?></button>
      </form>
    </details>

    <div class="hf-result" aria-live="polite">
      <p class="hf-result__label"><?= View::e(t('hist.month_booked')) ?><br><small class="hf-num"><?= View::e((string)$month['jobCount']) ?> <?= View::e(tp('unit.pass', $month['jobCount'])) ?></small></p>
      <p class="hf-result__value"><?= View::e(View::hours($month['totalHours'])) ?> <?= View::e($tr_h) ?></p>
      <p class="hf-result__note"><?= $month['jobCount'] === 0 ? View::e(t('hist.month_empty')) : View::e(t('hist.month_basis')) ?></p>
    </div>
  </section>
</div>

<section class="hf-section hf-section--major" aria-labelledby="tr-weeks-title" aria-live="polite">
  <div class="hf-section-head"><h2 class="hf-section-title" id="tr-weeks-title"><?= View::e(t('hist.weeks_in', ['month' => t('date.month.' . $month['monthNum']) . ' ' . $month['yearNum']])) ?></h2></div>

  <ul class="hf-weeklist">
    <?php foreach ($weeks as $w): ?>
    <li>
      <?php if ($w['jobCount'] > 0): ?>
      <a class="hf-weekrow" href="<?= View::e(View::url('report_preview', ['week' => $w['isoWeek']])) ?>">
        <span class="hf-weekrow__wk"><?= View::e(t('week.title', ['week' => $w['weekNumber']])) ?></span>
        <span class="hf-weekrow__rng"><?= View::e($w['rangeLabel']) ?> · <?= View::e((string)$w['jobCount']) ?> <?= View::e(tp('unit.pass', $w['jobCount'])) ?></span>
        <span class="hf-weekrow__hrs"><?= View::e(View::hours($w['totalHours'])) ?> <?= View::e($tr_h) ?></span>
        <span class="hf-weekrow__st"><?php View::partial('status', ['status' => $w['status']]); ?></span>
        <span class="hf-weekrow__chev"><?= View::icon('chev-right') ?></span>
      </a>
      <?php else: ?>
      <div class="hf-weekrow hf-weekrow--empty">
        <span class="hf-weekrow__wk"><?= View::e(t('week.title', ['week' => $w['weekNumber']])) ?></span>
        <span class="hf-weekrow__rng"><?= View::e($w['rangeLabel']) ?> · <?= View::e(t('hist.week_empty')) ?></span>
        <span class="hf-weekrow__hrs">0 <?= View::e($tr_h) ?></span>
      </div>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
