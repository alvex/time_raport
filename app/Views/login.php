<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Csrf;
use App\Core\View;
?><!DOCTYPE html>
<html lang="sv">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Logga in – HemFresh TimeRapport</title>
<link rel="stylesheet" href="assets/app.css">
</head>
<body class="login-page">
<main class="login-card">
  <h1 class="login-brand"><span class="brand-hem">Hem</span><span class="brand-fresh">Fresh</span></h1>
  <p class="login-sub">TimeRapport</p>

  <?php if (!empty($error)): ?>
  <div class="alert alert-warning"><?= View::e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= View::e(View::url('do_login')) ?>" class="login-form">
    <input type="hidden" name="_token" value="<?= View::e(Csrf::token()) ?>">
    <label for="email">E-post</label>
    <input id="email" name="email" type="email" autocomplete="username" required autofocus>
    <label for="password">Lösenord</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
    <button type="submit" class="btn btn-primary btn-block">Logga in</button>
  </form>
</main>
</body>
</html>
