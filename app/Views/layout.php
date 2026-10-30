<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Translator;
use App\Core\View;

// Bottennavigering (designsystemets BottomNav): fyra flikar med ikon och
// text inom tummens räckvidd. Ikonerna kommer från View::icon().
// OBS: vyerna återanvänder variabelnamnet $tr_nav för en ikon, och
// navigeringen renderas efter innehållet — därför ett eget namn här.
$tr_menuItems = [
    ['page' => 'dashboard',      'label' => t('nav.today'),   'icon' => 'home',     'match' => ['dashboard']],
    ['page' => 'week',           'label' => t('nav.week'),    'icon' => 'calendar', 'match' => ['week', 'job']],
    ['page' => 'report_preview', 'label' => t('nav.report'),  'icon' => 'report',   'match' => ['report_preview']],
    ['page' => 'history',        'label' => t('nav.history'), 'icon' => 'history',  'match' => ['history']],
];
$tr_currentPage = (string)($_GET['page'] ?? 'dashboard');
$tr_return = '?' . (string)($_SERVER['QUERY_STRING'] ?? '');
?><!DOCTYPE html>
<html lang="<?= View::e(Translator::locale()) ?>" data-i18n-sending="<?= View::e(t('common.sending')) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>HemFresh TimeRapport</title>
<link rel="stylesheet" href="assets/app.css?v=<?= (int)@filemtime(TR_ROOT . '/assets/app.css') ?>">
</head>
<body class="hf">
<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="<?= View::e(View::url('dashboard')) ?>">
      <img class="brand-logo" src="images/hemfresh_logo.png" alt="HemFresh"><span class="brand-app">TimeRapport</span>
    </a>
    <div class="topbar-actions">
      <details class="langmenu">
        <summary aria-label="<?= View::e(t('lang.label')) ?>"><?= View::e(strtoupper(Translator::locale())) ?></summary>
        <form method="post" action="<?= View::e(View::url('set_lang')) ?>" class="langmenu-list">
          <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
          <input type="hidden" name="return" value="<?= View::e($tr_return) ?>">
          <?php foreach (Translator::LOCALES as $tr_loc): ?>
          <button type="submit" name="locale" value="<?= View::e($tr_loc) ?>"
                  class="langmenu-item<?= $tr_loc === Translator::locale() ? ' is-active' : '' ?>"
                  <?= $tr_loc === Translator::locale() ? 'aria-current="true"' : '' ?>>
            <?= View::e(t('lang.' . $tr_loc)) ?>
          </button>
          <?php endforeach; ?>
        </form>
      </details>
      <?php if (Auth::check()): ?>
      <form method="post" action="<?= View::e(View::url('logout')) ?>" class="logout-form">
        <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
        <button type="submit" class="btn-link"><?= View::e(t('nav.logout')) ?></button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="hf-page">
<?php require $tr_content_template; ?>
<footer class="footer">HemFresh TimeRapport</footer>
</main>

<?php if (Auth::check()): ?>
<nav class="hf-nav" aria-label="<?= View::e(t('nav.aria')) ?>">
  <ul>
    <?php foreach ($tr_menuItems as $item): $active = in_array($tr_currentPage, $item['match'], true); ?>
    <li><a href="<?= View::e(View::url($item['page'])) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
      <span class="hf-pill"><?= View::icon($item['icon']) ?></span><?= View::e($item['label']) ?>
    </a></li>
    <?php endforeach; ?>
  </ul>
</nav>
<?php endif; ?>

<script src="assets/app.js"></script>
</body>
</html>
