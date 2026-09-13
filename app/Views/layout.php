<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
?><!DOCTYPE html>
<html lang="sv">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>HemFresh TimeRapport</title>
<link rel="stylesheet" href="assets/app.css">
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
<nav class="mainnav">
  <a href="<?= View::e(View::url('dashboard')) ?>">Idag</a>
  <a href="<?= View::e(View::url('week')) ?>">Vecka</a>
  <a href="<?= View::e(View::url('report_preview')) ?>">Tidrapport</a>
  <a href="<?= View::e(View::url('history')) ?>">Historik</a>
</nav>
<?php endif; ?>

<main class="content">
<?php require $tr_content_template; ?>
</main>

<footer class="footer">HemFresh TimeRapport</footer>
<script src="assets/app.js"></script>
</body>
</html>
