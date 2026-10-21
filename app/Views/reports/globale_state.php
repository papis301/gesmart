<?php
/** @var string $from @var string $to @var bool $submitted @var array $activity @var array $balances @var string $error */
$n = static fn($v): string => number_format((float) $v, 0, ',', ' ');
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><div class="card-title">Générer un rapport</div></div>
      <div class="card-body">
        <form action="<?= e(url('/rapports/situation-globale')) ?>" method="get">
          <div class="row">
            <div class="col-md col-lg-4 col-xl-3">
              <div class="mb-3">
                <label for="from_date" class="form-label">Du</label>
                <input min="2024-09-01T08:00" required type="datetime-local" value="<?= e($from) ?>" class="form-control" name="from_date" id="from_date" />
              </div>
            </div>
            <div class="col-md col-lg-4 col-xl-3">
              <div class="mb-3">
                <label for="to_date" class="form-label">Au</label>
                <input min="2024-09-01T08:00" required type="datetime-local" value="<?= e($to) ?>" class="form-control" name="to_date" id="to_date" />
              </div>
            </div>
            <div class="col-12 text-center"><button type="submit" class="btn btn-primary">Générer</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if ($error !== ''): ?>
    <div class="col-12"><div class="alert alert-danger" role="alert"><?= e($error) ?></div></div>
  <?php elseif ($submitted): ?>
    <?php $net = $balances['clients']['debts'] + $balances['clients']['assets']; ?>
    <div class="col-md col-lg-3 d-flex">
      <div class="card bg-white flex-fill text-center"><div class="card-body">
        <h5 class="text-uppercase">Caisses</h5>
        <div class="fs-3"><?= e($n($balances['cash']['total'])) ?> <b>FCFA</b></div>
        <div class="text-secondary small"><?= (int) $balances['cash']['count'] ?> caisse(s)</div>
      </div></div>
    </div>
    <div class="col-md col-lg-3 d-flex">
      <div class="card bg-white flex-fill text-center"><div class="card-body">
        <h5 class="text-uppercase">Banques</h5>
        <div class="fs-3"><?= e($n($balances['bank']['total'])) ?> <b>FCFA</b></div>
        <div class="text-secondary small"><?= (int) $balances['bank']['count'] ?> compte(s)</div>
      </div></div>
    </div>
    <div class="col-md col-lg-3 d-flex">
      <div class="card bg-white flex-fill text-center"><div class="card-body">
        <h5 class="text-uppercase">Créances clients</h5>
        <div class="fs-3 text-danger"><?= e($n(-$balances['clients']['debts'])) ?> <b>FCFA</b></div>
        <div class="text-secondary small">Ce que les clients doivent</div>
      </div></div>
    </div>
    <div class="col-md col-lg-3 d-flex">
      <div class="card bg-white flex-fill text-center"><div class="card-body">
        <h5 class="text-uppercase">Avoirs clients</h5>
        <div class="fs-3 text-success"><?= e($n($balances['clients']['assets'])) ?> <b>FCFA</b></div>
        <div class="text-secondary small">Net clients : <?= e($n($net)) ?> FCFA</div>
      </div></div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title">Activité du <?= e(dt(str_replace('T', ' ', $from) . ':00')) ?> au <?= e(dt(str_replace('T', ' ', $to) . ':59')) ?></div>
          <div class="card-actions d-print-none"><button type="button" class="btn btn-sm" onclick="window.print()">Imprimer</button></div>
        </div>
        <div class="table-responsive">
          <table class="table table-vcenter table-nowrap">
            <thead>
              <tr><th>Opération</th><th class="text-end">Nombre</th><th class="text-end">Montant validé FCFA</th><th class="text-end">En attente FCFA</th></tr>
            </thead>
            <tbody>
              <?php foreach ($activity as $r): ?>
                <tr>
                  <td><?= e($r['label']) ?></td>
                  <td class="text-end"><?= e($n($r['count'])) ?></td>
                  <td class="text-end"><?= e($n($r['done'])) ?></td>
                  <td class="text-end"><?= $r['pending'] != 0 ? e($n($r['pending'])) : '' ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$activity): ?>
                <tr><td colspan="4" class="text-center text-secondary">Aucune opération sur cette période.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer text-secondary small">Les opérations annulées sont exclues. Les soldes ci-dessus sont ceux d'aujourd'hui, pas ceux de la fin de la période.</div>
      </div>
    </div>
  <?php endif; ?>
</div>
