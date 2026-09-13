<?php if (!defined('TIMERAPPORT')) { http_response_code(403); exit; }
use App\Core\Translator;
use App\Core\View;
?><!DOCTYPE html>
<html lang="<?= View::e(Translator::locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= View::e($title ?? t('err.default_title')) ?> – HemFresh TimeRapport</title>
<link rel="stylesheet" href="assets/app.css?v=<?= (int)@filemtime(TR_ROOT . '/assets/app.css') ?>">
</head>
<body class="login-page">
<main class="login-card">
  <h1 class="login-brand"><span class="brand-hem">Hem</span><span class="brand-fresh">Fresh</span></h1>
  <p class="login-sub">TimeRapport</p>
  <div class="alert alert-warning">
    <strong><?= View::e($title ?? t('err.generic_title')) ?></strong><br>
    <?= View::e($message ?? t('err.generic_msg')) ?>
  </div>
  <a class="btn btn-primary btn-block" href="index.php"><?= View::e(t('common.home')) ?></a>
</main>
</body>
</html>
