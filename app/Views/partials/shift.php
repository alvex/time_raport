<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;
/**
 * ShiftCard från designsystemet. Informationshierarki uppifrån och ned:
 * 1 tid + timmar, 2 kund, 3 avvikelse från bokning, 4 sekundär info
 * (nyckel först, adress, kundnr, kommentar), 5 åtgärder.
 *
 * Expects:
 *   $b         enriched booking (ScheduleService::enrich)
 *   $mapsUrl   directions URL or null
 *   $mapsLabel button text for the directions link
 */
$tr_h = t('common.hours_short');
$tr_hasDiff = !$b['invalid'] && $b['old_hours_disp'] !== null && $b['old_hours_disp'] !== $b['hours'];
// Pass med 0 h utan bokad tid att jämföra med dämpas (hf-shift--muted).
$tr_muted = !$b['invalid'] && !$tr_hasDiff && (int)$b['minutes'] === 0;
$tr_jobUrl = View::url('job', ['resid' => $b['resid']]);
?>
<article class="hf-shift hf-shift--link<?= $tr_muted ? ' hf-shift--muted' : '' ?>">
  <div class="hf-shift__top">
    <p class="hf-shift__time"><?= View::e($b['display_start']) ?>–<?= View::e($b['display_end']) ?></p>
    <p class="hf-shift__hours"><?= View::e(View::hours($b['hours'])) ?> <?= View::e($tr_h) ?></p>
  </div>
  <h3 class="hf-shift__customer"><a href="<?= View::e($tr_jobUrl) ?>"><?= View::e($b['customer']) ?></a></h3>

  <?php if ($b['invalid']): ?>
  <p class="hf-diff" role="alert"><?= View::icon('info') ?><span><strong><?= View::e(t('invalid.title')) ?></strong>
    <?= View::e(t('invalid.reason_negative')) ?> <?= View::e(t('invalid.fix_hint')) ?></span></p>
  <?php elseif ($tr_hasDiff): ?>
  <?php
    // Behåll översättningens grammatik men fetstila värdena: platshållarna
    // ersätts efter HTML-escapningen.
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

  <div class="hf-shift__actions">
    <a class="hf-btn hf-btn--text" href="<?= View::e($tr_jobUrl) ?>"><?= View::e(t($b['invalid'] ? 'invalid.fix_btn' : 'job.details')) ?></a>
    <?php if ($mapsUrl !== null): ?>
    <a class="hf-btn hf-btn--secondary" href="<?= View::e($mapsUrl) ?>" target="_blank" rel="noopener noreferrer">
      <?= View::icon('navigate') ?><?= View::e($mapsLabel) ?>
    </a>
    <?php endif; ?>
  </div>
</article>
