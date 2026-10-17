<?php
/** @var string $content @var string|null $title */
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
$user = Auth::user();
$initial = $user ? strtoupper(mb_substr((string) $user['name'], 0, 1)) : '?';
$hasLogo = asset_exists('img/logo.svg');

$userMenu = static function (string $extraClass = '') use ($user, $initial): string {
    ob_start(); ?>
    <div class="nav-item dropdown <?= e($extraClass) ?>">
      <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Menu utilisateur">
        <span class="avatar avatar-sm"><?= e($initial) ?></span>
        <div class="d-none d-xl-block ps-2">
          <div><?= e($user['name'] ?? '') ?></div>
          <div class="mt-1 small text-secondary"><?= e($user['group_name'] ?? '') ?></div>
        </div>
      </a>
      <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
        <span class="dropdown-item disabled">Modifier mon mot de passe <span class="badge bg-secondary-lt ms-1">bientôt</span></span>
        <form action="<?= e(url('/se-deconnecter')) ?>" method="post" class="m-0">
          <?= Csrf::field() ?>
          <button type="submit" class="dropdown-item">Se déconnecter</button>
        </form>
      </div>
    </div>
    <?php return (string) ob_get_clean();
};
?><!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title ?? 'GESMART') ?> - GESMART</title>
  <link rel="stylesheet" href="<?= e(asset('tabler.min.css')) ?>">
  <?php if ($hasLogo): ?><link rel="shortcut icon" href="<?= e(url('assets/img/logo.svg')) ?>" type="image/svg+xml"><?php endif; ?>
  <style>
    .datagrid-content { box-shadow: rgba(0,0,0,.12) 0 1px 3px, rgba(0,0,0,.24) 0 1px 2px; padding: 5px; border-radius: 3px; }
    .nav-link.disabled { opacity: .55; cursor: default; }
  </style>
</head>

<body class="layout-fluid">
  <div class="page">

    <aside id="sideb" class="navbar navbar-vertical navbar-expand-lg navbar-dark bg-dark text-white">
      <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
                aria-controls="sidebar-menu" aria-expanded="false" aria-label="Menu">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-brand navbar-brand-autodark">
          <a href="<?= e(url('/tableau-de-bord')) ?>">GESMART</a>
        </div>
        <div class="navbar-nav flex-row d-lg-none"><?= $userMenu() ?></div>

        <div class="collapse navbar-collapse" id="sidebar-menu">
          <ul class="navbar-nav pt-lg-3">
            <li class="nav-item <?= is_active('tableau-de-bord') ? 'active' : '' ?>">
              <a class="nav-link" href="<?= e(url('/tableau-de-bord')) ?>">
                <span class="nav-link-icon d-md-none d-lg-inline-block"><?= icon('home') ?></span>
                <span class="nav-link-title">Tableau de bord</span>
              </a>
            </li>

            <?php foreach (menu() as $section => $items): ?>
              <li class="nav-item"><div class="h3 px-3 py-0 mb-0 mt-4"><?= e($section) ?></div></li>
              <?php foreach ($items as $item): ?>

                <?php if (isset($item['children'])): ?>
                  <?php $open = false; foreach ($item['children'] as $c) { $open = $open || is_active($c['path']); } ?>
                  <li class="nav-item dropdown <?= $open ? 'active' : '' ?>">
                    <a class="nav-link dropdown-toggle <?= $open ? 'show' : '' ?>" href="#" data-bs-toggle="dropdown"
                       data-bs-auto-close="false" role="button" aria-expanded="<?= $open ? 'true' : 'false' ?>">
                      <span class="nav-link-icon d-md-none d-lg-inline-block"><?= icon($item['icon']) ?></span>
                      <span class="nav-link-title"><?= e($item['label']) ?></span>
                    </a>
                    <div class="dropdown-menu <?= $open ? 'show' : '' ?>">
                      <?php foreach ($item['children'] as $c): ?>
                        <?php if ($c['ready']): ?>
                          <a class="dropdown-item <?= is_active($c['path']) ? 'active' : '' ?>" href="<?= e(url($c['path'])) ?>"><?= e($c['label']) ?></a>
                        <?php else: ?>
                          <span class="dropdown-item disabled"><?= e($c['label']) ?> <span class="badge bg-secondary-lt ms-1">bientôt</span></span>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </div>
                  </li>

                <?php else: ?>
                  <li class="nav-item <?= is_active($item['path']) ? 'active' : '' ?>">
                    <?php if ($item['ready']): ?>
                      <a class="nav-link" href="<?= e(url($item['path'])) ?>">
                    <?php else: ?>
                      <span class="nav-link disabled">
                    <?php endif; ?>
                      <span class="nav-link-icon d-md-none d-lg-inline-block"><?= icon($item['icon']) ?></span>
                      <span class="nav-link-title"><?= e($item['label']) ?><?php if (!$item['ready']): ?> <span class="badge bg-secondary-lt ms-1">bientôt</span><?php endif; ?></span>
                    <?php if ($item['ready']): ?></a><?php else: ?></span><?php endif; ?>
                  </li>
                <?php endif; ?>

              <?php endforeach; ?>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </aside>

    <div class="page-wrapper">
      <header class="navbar navbar-expand-md d-print-none d-none d-lg-flex">
        <div class="container-xl">
          <div class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="<?= e(url('/tableau-de-bord')) ?>">
              <?php if ($hasLogo): ?><img style="height: 50px;" src="<?= e(url('assets/img/logo.svg')) ?>" alt="Logo"><?php endif; ?>
            </a>
            GESMART
          </div>
          <div class="navbar-nav flex-row order-md-last"><?= $userMenu() ?></div>
        </div>
      </header>

      <div class="page-header d-print-none">
        <div class="container-xl">
          <div class="row g-2 align-items-center">
            <div class="col"><h2 class="page-title"><?= e($title ?? '') ?></h2></div>
            <?php if (!empty($actions)): ?><div class="col d-flex justify-content-end"><?= $actions ?></div><?php endif; ?>
          </div>
        </div>
      </div>

      <div class="page-body">
        <div class="container-xl">
          <?php foreach (['success', 'danger'] as $type): $flash = Session::pull($type); ?>
            <?php if ($flash): ?><div class="alert alert-<?= $type ?>" role="alert"><?= e($flash) ?></div><?php endif; ?>
          <?php endforeach; ?>
          <?= $content ?>
        </div>
      </div>

      <footer class="footer footer-transparent d-print-none">
        <div class="container-xl">
          <div class="row text-center align-items-center flex-row-reverse">
            <div class="col-12 col-lg-auto mt-3 mt-lg-0">
              <ul class="list-inline list-inline-dots mb-0">
                <li class="list-inline-item">Copyright &copy; <?= date('Y') ?> All rights reserved - GESMART.</li>
                <li class="list-inline-item"><a href="https://yankeesuprem.com" class="link-secondary">par Yankee</a></li>
              </ul>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </div>
  <script src="<?= e(asset('tabler.min.js')) ?>"></script>
  <script src="<?= e(asset('app.js')) ?>" defer></script>
</body>

</html>
