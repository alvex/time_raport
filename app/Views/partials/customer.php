<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;
/**
 * Customer line: name · <Kundnr-label> <memberid> [key icon].
 * Expects $b (enriched booking: customer, customer_no, has_key).
 * Set $nameOnly = true to render just the name — used where the card
 * shows the customer number and key on a dedicated line instead.
 * The key icon means the customer's physical key is kept at HemFresh
 * and must be brought to the job (cal_login.keys === 1 on the customer
 * row — verified against legacy mycalendar.template.php:381).
 */
$nameOnly = !empty($nameOnly);
?><span class="cust"><span class="cust-name"><?= View::e($b['customer']) ?></span><?php if (!$nameOnly): ?><span class="cust-no"> · <?= View::e(t('common.customer_no')) ?> <?= View::e($b['customer_no']) ?></span><?php endif; ?><?php if (!$nameOnly && $b['has_key']): ?>
<svg class="key-icon" role="img" aria-label="<?= View::e(t('common.key_aria')) ?>" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><title><?= View::e(t('common.key_aria')) ?></title><circle cx="7.5" cy="15.5" r="4.5"/><path d="M10.7 12.3 21 2m-3.5 3.5 3 3M14 9l2.5 2.5"/></svg><?php endif; ?></span>
