<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Csrf;
use App\Core\Translator;
use App\Core\View;
?><!DOCTYPE html>
<html lang="<?= View::e(Translator::locale()) ?>" data-i18n-sending="<?= View::e(t('common.sending')) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= View::e(t('login.title')) ?> – HemFresh TimeRapport</title>
<link rel="stylesheet" href="assets/app.css?v=<?= (int)@filemtime(TR_ROOT . '/assets/app.css') ?>">
</head>
<body class="hf login-page">
<main class="login-card">
  <h1 class="login-brand"><img class="login-logo" src="images/hemfresh_logo.png" alt="HemFresh"></h1>
  <p class="login-sub">TimeRapport</p>

  <?php if (!empty($error)): ?>
  <div class="alert alert-warning"><?= View::e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= View::e(View::url('do_login')) ?>" class="login-form">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <label for="email"><?= View::e(t('login.email')) ?></label>
    <input id="email" name="email" type="email" autocomplete="username" required autofocus>
    <label for="password"><?= View::e(t('login.password')) ?></label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
    <button type="submit" class="hf-btn hf-btn--primary hf-btn--block"><?= View::e(t('login.submit')) ?></button>
  </form>

  <form method="post" action="<?= View::e(View::url('set_lang')) ?>" class="login-langs" aria-label="<?= View::e(t('lang.label')) ?>">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <input type="hidden" name="return" value="?page=login">
    <?php foreach (Translator::LOCALES as $loc): ?>
    <button type="submit" name="locale" value="<?= View::e($loc) ?>"
            class="login-lang<?= $loc === Translator::locale() ? ' is-active' : '' ?>"
            <?= $loc === Translator::locale() ? 'aria-current="true"' : '' ?>>
      <?= View::e(t('lang.' . $loc)) ?>
    </button>
    <?php endforeach; ?>
  </form>
</main>
</body>
</html>
