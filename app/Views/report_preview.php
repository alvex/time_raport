<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Csrf;
use App\Core\View;
?>
<?php if (!empty($notice)): ?><div class="alert alert-notice"><?= View::e($notice) ?></div><?php endif; ?>
<?php if (!empty($warning)): ?><div class="alert alert-warning"><?= View::e($warning) ?></div><?php endif; ?>

<h1 class="page-title">Tidrapport</h1>
<p class="page-sub">Vecka <?= View::e((string)$report['weekNumber']) ?> · <?= View::e($report['rangeLabel']) ?>
  · <span class="badge <?= View::e(View::badgeClass($report['weekStatus'])) ?>"><?= View::e($report['weekStatus']) ?></span></p>

<div class="week-nav week-nav-compact">
  <a class="btn btn-nav" href="<?= View::e(View::url('report_preview', ['week' => (new App\Services\ScheduleService())->adjacentWeek($report['isoWeek'], -1)])) ?>">&lsaquo; Föregående</a>
  <a class="btn btn-nav" href="<?= View::e(View::url('report_preview', ['week' => (new App\Services\ScheduleService())->adjacentWeek($report['isoWeek'], 1)])) ?>">Nästa &rsaquo;</a>
</div>

<?php if ($report['bookings'] === []): ?>
  <div class="card empty-card">Inga jobb att rapportera för den här veckan.</div>
<?php else: ?>

<div class="report-table-wrap">
  <table class="report-table">
    <thead>
      <tr><th>Datum</th><th>Kund</th><th class="col-time">Tid</th><th class="col-hours">Timmar</th></tr>
    </thead>
    <tbody>
    <?php foreach ($report['bookings'] as $b): ?>
      <tr>
        <td><?= View::e($b['display_date']) ?></td>
        <td>
          <?= View::e($b['customer']) ?>
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
      <tr><td colspan="3">Totalt · <?= View::e((string)$report['jobCount']) ?> arbetstillfällen</td>
          <td class="col-hours"><strong><?= View::e($report['totalHours']) ?> h</strong></td></tr>
    </tfoot>
  </table>
</div>

<?php if ($report['allApproved']): ?>
  <div class="alert alert-notice">Veckan är godkänd och låst.</div>
<?php elseif ($report['canSubmit']): ?>
  <div class="card submit-card">
    <p>Kontrollera raderna ovan. När du skickar markeras veckans jobb som inskickade
       och rapporten skickas för godkännande.</p>
    <form method="post" action="<?= View::e(View::url('report_submit')) ?>" data-confirm="Är du säker? Tryck igen för att skicka">
      <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
      <input type="hidden" name="_once" value="<?= View::e($onceToken) ?>">
      <input type="hidden" name="week" value="<?= View::e($report['isoWeek']) ?>">
      <button type="submit" class="btn btn-primary btn-block">Skicka för godkännande</button>
    </form>
  </div>
<?php endif; ?>

<?php endif; ?>

<div class="action-row">
  <a class="btn btn-secondary" href="<?= View::e(View::url('week', ['week' => $report['isoWeek']])) ?>">&lsaquo; Tillbaka till schemat</a>
</div>
