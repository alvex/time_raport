<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;
use App\Services\TimeFormatter;

$tr_todayMinutes = 0;
foreach ($today as $tr_j) { $tr_todayMinutes += max(0, (int)$tr_j['minutes']); }
$tr_todayHours = TimeFormatter::formatHours($tr_todayMinutes / 60);
// Rutinen är att skicka veckans tidrapport på fredagen. Påminn från och
// med fredag (t.o.m. söndag om den glömts) så länge veckan har pass som
// fortfarande är utkast; när rapporten är inskickad försvinner påminnelsen.
$tr_remind = (int)date('N') >= 5 && $week['weekStatus'] === 'draft' && $week['jobCount'] > 0;
?>
<h1 class="hf-page-title"><?= View::e(t('dash.hello', ['name' => $name])) ?></h1>
<p class="hf-page-sub"><?= View::e(t('week.title', ['week' => $week['weekNumber']])) ?> · <?= View::e($week['rangeLabel']) ?></p>

<?php if ($tr_remind): ?>
<div class="hf-section">
  <div class="hf-reminder" role="alert">
    <?= View::icon('bell') ?>
    <p class="hf-reminder__text"><?= View::e(t('remind.friday')) ?></p>
    <div class="hf-reminder__actions">
      <a class="hf-btn hf-btn--danger" href="<?= View::e(View::url('report_preview')) ?>"><?= View::icon('send') ?><?= View::e(t('remind.cta')) ?></a>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="hf-section">
  <section class="hf-summary" aria-label="<?= View::e(t('summary.aria')) ?>">
    <div class="hf-summary__row">
      <div class="hf-summary__stats">
        <div><div class="hf-stat__value"><?= View::e(View::hours($week['totalHours'])) ?> <?= View::e(t('common.hours_short')) ?></div><div class="hf-stat__label"><?= View::e(t('dash.this_week')) ?></div></div>
        <div><div class="hf-stat__value"><?= View::e((string)$week['jobCount']) ?></div><div class="hf-stat__label"><?= View::e(tp('unit.pass', $week['jobCount'])) ?></div></div>
      </div>
      <div>
        <div class="hf-stat__label" style="margin-bottom:4px"><?= View::e(t('nav.report')) ?></div>
        <?php View::partial('status', ['status' => $week['weekStatus']]); ?>
      </div>
    </div>
  </section>
</div>

<section class="hf-day hf-day--today">
  <div class="hf-day__head">
    <h2 class="hf-day__name"><?= View::e(TimeFormatter::formatTodayHeading(time())) ?></h2>
    <span class="hf-day__total"><?= count($today) ?> <?= View::e(tp('unit.pass', count($today))) ?> · <?= View::e(View::hours($tr_todayHours)) ?> <?= View::e(t('common.hours_short')) ?></span>
  </div>
  <?php if ($today === []): ?>
  <div class="hf-empty"><?= View::e(t('dash.no_jobs_today')) ?></div>
  <?php else: ?>
  <div class="hf-stack">
    <?php foreach ($today as $job): ?>
    <?php View::partial('shift', ['b' => $job, 'mapsUrl' => $job['maps_url_driving'], 'mapsLabel' => t('maps.start')]); ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<div class="hf-section">
  <a class="hf-btn hf-btn--text hf-btn--block" href="<?= View::e(View::url('week')) ?>"><?= View::icon('calendar') ?><?= View::e(t('dash.week_schedule')) ?></a>
</div>
