<?php
/** @var string|null $error @var string|null $info @var string $oldLogin */
use App\Core\Csrf;
$hasLogo = asset_exists('img/logo.svg');
$hasBg = asset_exists('img/bg.webp');
?><!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Page de connexion</title>
  <link rel="stylesheet" href="<?= e(asset('tabler.min.css')) ?>">
  <?php if ($hasLogo): ?>
  <link rel="shortcut icon" href="<?= e(url('assets/img/logo.svg')) ?>" type="image/svg+xml">
  <?php endif; ?>
  <style>
    #logo {
      height: 150px;
      width: 150px;
    }
  </style>
</head>

<body>
  <div class="row g-0 flex-fill bg-white">
    <div class="col-12 col-lg-6 col-xl-4 border-top-wide border-primary d-flex flex-column justify-content-center">
      <div class="container container-tight my-5 px-lg-5">
        <?php if ($hasLogo): ?>
        <div class="text-center">
          <a href="<?= e(url('/')) ?>" class="navbar-brand navbar-brand-autodark">
            <img src="<?= e(url('assets/img/logo.svg')) ?>" id="logo" alt="Logo">
          </a>
        </div>
        <?php endif; ?>
        <h2 class="h2 text-center mb-4">Connexion GESMART</h2>
        <hr>

        <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($info): ?>
        <div class="alert alert-info" role="alert"><?= e($info) ?></div>
        <?php endif; ?>

        <form action="<?= e(url('/')) ?>" method="post">
          <?= Csrf::field() ?>
          <div class="mb-3">
            <label class="form-label" for="login">Login</label>
            <input id="login" name="login" value="<?= e($oldLogin) ?>" type="text" class="form-control text-uppercase"
                   placeholder="XXXXXX" maxlength="100" autocomplete="username" autofocus required />
          </div>
          <div class="mb-2">
            <label class="form-label" for="password">Mot de passe</label>
            <div class="input-group input-group-flat">
              <input id="password" name="password" value="" type="password" class="form-control"
                     placeholder="Votre mot de passe" maxlength="1024" autocomplete="current-password" required />
              <span class="input-group-text"></span>
            </div>
          </div>
          <div class="form-footer">
            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
          </div>
          <div class="text-center text-muted mt-3">&copy;<?= date('Y') ?> All rights reserved . <a href="https://yankeesuprem.com">Yankee</a></div>
        </form>
      </div>
    </div>
    <div class="col-12 col-lg-6 col-xl-8 d-none d-lg-block">
      <!-- Photo -->
      <div class="bg-cover h-100 min-vh-100"
           style="<?= $hasBg
               ? 'background-image: url(' . e(url('assets/img/bg.webp')) . ')'
               : 'background: linear-gradient(135deg, #206bc4, #0a2540)' ?>"></div>
    </div>
  </div>

  <script src="<?= e(asset('tabler.min.js')) ?>"></script>
</body>

</html>
