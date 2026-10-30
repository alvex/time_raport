<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;
use App\Services\TimeFormatter;
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<div class="hf-section">
  <div class="hf-period">
    <a class="hf-iconbtn" href="<?= View::e(View::url('week', ['week' => $prevWeek])) ?>" aria-label="<?= View::e(t('week.prev')) ?>"><?= View::icon('chev-left') ?></a>
    <div class="hf-period__label" aria-live="polite">
      <p class="hf-period__title"><?= View::e(t('week.title', ['week' => $week['weekNumber']])) ?></p>
      <p class="hf-period__range"><?= View::e($week['rangeLabel']) ?></p>
      <?php if ($week['isoWeek'] !== $currentWeek): ?>
      <a class="hf-period__today" href="<?= View::e(View::url('week')) ?>"><?= View::e(t('week.to_current')) ?></a>
      <?php endif; ?>
    </div>
    <a class="hf-iconbtn" href="<?= View::e(View::url('week', ['week' => $nextWeek])) ?>" aria-label="<?= View::e(t('week.next')) ?>"><?= View::icon('chev-right') ?></a>
  </div>
  <p class="hf-page-sub hf-num" style="text-align:center;margin-top:8px"><?= View::e((string)$week['jobCount']) ?> <?= View::e(tp('unit.pass', $week['jobCount'])) ?> · <?= View::e(View::hours($week['totalHours'])) ?> <?= View::e(t('common.hours_short')) ?></p>
</div>

<?php foreach ($week['days'] as $day): ?>
  <?php if ($day['bookings'] === []) continue; ?>
  <section class="hf-day<?= $day['isToday'] ? ' hf-day--today' : '' ?>">
    <div class="hf-day__head">
      <h2 class="hf-day__name"><?= View::e($day['isToday'] ? TimeFormatter::formatTodayHeading($day['ts']) : $day['heading']) ?></h2>
      <span class="hf-day__total"><?= count($day['bookings']) ?> <?= View::e(tp('unit.pass', count($day['bookings']))) ?> · <?= View::e(View::hours(TimeFormatter::formatHours(max(0, (int)$day['minutes']) / 60))) ?> <?= View::e(t('common.hours_short')) ?></span>
    </div>
    <div class="hf-stack">
      <?php foreach ($day['bookings'] as $b): ?>
      <?php View::partial('shift', ['b' => $b, 'mapsUrl' => $b['maps_url'], 'mapsLabel' => t('maps.short')]); ?>
      <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>

<?php if ($week['jobCount'] === 0): ?>
  <div class="hf-section"><div class="hf-empty"><?= View::e(t('week.no_jobs')) ?></div></div>
<?php else: ?>
  <div class="hf-section">
    <?php if ($week['invalidCount'] > 0): ?>
    <span class="hf-btn hf-btn--primary hf-btn--block" aria-disabled="true">
      <?= View::e(t('week.review', ['week' => $week['weekNumber']])) ?>
    </span>
    <p class="report-blocked" role="alert">
      <?= View::e(tp('report.blocked', $week['invalidCount'])) ?>
    </p>
    <?php else: ?>
    <a class="hf-btn hf-btn--primary hf-btn--block" href="<?= View::e(View::url('report_preview', ['week' => $week['isoWeek']])) ?>">
      <?= View::e(t('week.review', ['week' => $week['weekNumber']])) ?>
    </a>
    <?php if ($week['draftCount'] > 0 && $week['lockedCount'] > 0): ?>
    <p class="report-blocked" role="alert"><?= View::e(t('report.mixed_locked')) ?></p>
    <?php elseif ($week['draftCount'] === 0 && $week['lockedCount'] > 0): ?>
    <p class="report-info"><?= View::e(t('report.submitted_locked')) ?></p>
    <?php endif; ?>
    <?php endif; ?>
  </div>
<?php endif; ?>
