<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;

// Local inline SVG icons (16px, stroke = currentColor) — no external deps.
$tr_icons = [
    'home'     => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h5v-6h4v6h5V9.5"/></svg>',
    'calendar' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>',
    'report'   => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>',
    'history'  => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 12a8.5 8.5 0 1 0 2.5-6L3.5 8.5"/><path d="M3.5 4v4.5H8"/><path d="M12 8v4.5l3 2"/></svg>',
];
$tr_nav = [
    ['page' => 'dashboard',      'label' => 'Idag',       'icon' => 'home',     'match' => ['dashboard']],
    ['page' => 'week',           'label' => 'Vecka',      'icon' => 'calendar', 'match' => ['week', 'job']],
    ['page' => 'report_preview', 'label' => 'Tidrapport', 'icon' => 'report',   'match' => ['report_preview']],
    ['page' => 'history',        'label' => 'Historik',   'icon' => 'history',  'match' => ['history']],
];
$tr_currentPage = (string)($_GET['page'] ?? 'dashboard');
?><!DOCTYPE html>
<html lang="sv">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>HemFresh TimeRapport</title>
<link rel="stylesheet" href="assets/app.css?v=<?= (int)@filemtime(TR_ROOT . '/assets/app.css') ?>">
</head>
<body>
<header class="topbar">
  <a class="brand" href="<?= View::e(View::url('dashboard')) ?>">
    <span class="brand-hem">Hem</span><span class="brand-fresh">Fresh</span>
    <span class="brand-app">TimeRapport</span>
  </a>
  <?php if (Auth::check()): ?>
  <form method="post" action="<?= View::e(View::url('logout')) ?>" class="logout-form">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <button type="submit" class="btn-link">Logga ut</button>
  </form>
  <?php endif; ?>
</header>

<?php if (Auth::check()): ?>
<nav class="pillnav" aria-label="Huvudmeny">
  <?php foreach ($tr_nav as $item): $active = in_array($tr_currentPage, $item['match'], true); ?>
  <a class="pillnav-item<?= $active ? ' is-active' : '' ?>"
     href="<?= View::e(View::url($item['page'])) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
    <?= $tr_icons[$item['icon']] ?><span><?= View::e($item['label']) ?></span>
  </a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>

<main class="content">
<?php require $tr_content_template; ?>
</main>

<footer class="footer">HemFresh TimeRapport</footer>
<script src="assets/app.js"></script>
</body>
</html>
