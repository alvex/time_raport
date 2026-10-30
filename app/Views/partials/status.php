<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\View;
/**
 * StatusBadge från designsystemet: pillformad bricka med ikon + text.
 * Färg står aldrig ensam — alltid ikon och text.
 * Expects $status (statusnyckel: draft/submitted/approved/invalid/none).
 */
$tr_statusIcon = [
    'draft'     => 'open',
    'submitted' => 'send',
    'approved'  => 'approved',
    'invalid'   => 'info',
][$status] ?? null;
?><span class="hf-status <?= View::e(View::statusClass($status)) ?>"><?php
if ($tr_statusIcon !== null) { echo View::icon($tr_statusIcon); }
?><?= View::e(t('status.' . $status)) ?></span>
