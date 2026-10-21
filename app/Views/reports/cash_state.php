<?php
/** @var array $state @var string $now */
$n = static fn($v): string => number_format((float) $v, 0, ',', ' ');
$sub = static function (array $rows, string $total, string $label) use ($n): void { ?>
  <table class="table table-bordered table-sm mb-0">
    <thead><tr><td class="fw-bold">Nom</td><td class="fw-bold" width="30%">Solde</td></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr><td><?= e($r['name']) ?></td><td class="text-end"><?= e($n($r['balance'])) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td class="bg-light fw-bold"><?= e($label) ?></td><td class="bg-light fw-bold text-end"><?= e($total) ?></td></tr></tfoot>
  </table>
<?php };
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>État de la trésorerie par Unité de production <?= e($now) ?></title>
  <link rel="stylesheet" href="<?= e(asset('tabler.min.css')) ?>">
  <style>@media print { body { font-size: 9px; } }</style>
</head>
<body>
<div class="page-wrapper bg-white">
  <div class="page-header d-print-none">
    <div class="container-xl">
      <div class="row g-2 align-items-center">
        <div class="col">
          <h4 class="mb-0 text-uppercase text-primary"><a href="<?= e(url('/tableau-de-bord')) ?>">Retour au tableau de bord</a></h4>
          <h2 class="page-title">État de la Trésorerie par Unité de Production <span class="text-primary"><?= e($now) ?></span></h2>
        </div>
        <div class="col-auto ms-auto d-print-none">
          <button type="button" class="btn btn-primary" onclick="window.print()">Imprimer</button>
        </div>
      </div>
    </div>
  </div>
  <div class="page-body">
    <div class="container-xl">
      <div class="row align-items-center">
        <div class="col-6">
          <?php if (asset_exists('img/logo.svg')): ?><img class="m-auto" src="<?= e(url('assets/img/logo.svg')) ?>" style="height: 100px;" alt="Logo"><?php endif; ?>
        </div>
        <div class="col-6 text-center">
          <h1 class="mb-0">État de la Trésorerie par Unité de Production <br> <span class="text-primary"><?= e($now) ?></span></h1>
        </div>
        <div class="col-12 mb-2">
          <div class="table-responsive">
            <table class="table table-hover table-bordered table-nowrap table-sm">
              <thead class="text-center">
                <tr>
                  <td class="bg-primary text-white fw-bold">Unité de Production</td>
                  <td class="bg-primary text-white fw-bold">Banques</td>
                  <td class="bg-primary text-white fw-bold">Caisses</td>
                  <td class="bg-primary text-white fw-bold" width="15%">Totaux</td>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($state['units'] as $u): ?>
                  <tr>
                    <td><?= e($u['name']) ?></td>
                    <td class="p-0"><?php $sub($u['banks'], $n($u['bank_total']), 'Total partiel en banque'); ?></td>
                    <td class="p-0"><?php $sub($u['registers'], $n($u['register_total']), 'Total partiel en caisse'); ?></td>
                    <td class="text-end" style="vertical-align: bottom;"><?= e($n($u['total'])) ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr class="bg-primary text-white fw-bold">
                  <td></td>
                  <td class="text-end">Total en banque : <?= e($n($state['bank_total'])) ?></td>
                  <td class="text-end">Total en caisse : <?= e($n($state['register_total'])) ?></td>
                  <td class="text-end"><?= e($n($state['total'])) ?></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="text-secondary small">Montants en FCFA. Une banque rattachée à plusieurs unités est comptée dans chacune.</div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
