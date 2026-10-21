<?php
/**
 * @var array $registers @var string $from @var string $to @var int $registerId @var ?array $register
 * @var array $movements @var string $error @var array $can
 */
$n = static fn($v): string => number_format((float) $v, 0, ',', ' ');
$in = 0.0;
$out = 0.0;
foreach ($movements as $m) {
    if ($m['pending']) { continue; } // les opérations en attente ne sont pas comptées dans les totaux
    if ($m['amount'] >= 0) { $in += $m['amount']; } else { $out += $m['amount']; }
}
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><div class="card-title">Générer un rapport</div></div>
      <div class="card-body">
        <form action="<?= e(url('/rapports/historique-de-caisse')) ?>" method="get">
          <div class="row">
            <div class="col-md col-lg-4 col-xl-3">
              <div class="mb-3">
                <label for="from_date" class="form-label">Du</label>
                <input type="datetime-local" value="<?= e($from) ?>" class="form-control" name="from_date" id="from_date" required />
              </div>
            </div>
            <div class="col-md col-lg-4 col-xl-3">
              <div class="mb-3">
                <label for="to_date" class="form-label">Au</label>
                <input type="datetime-local" value="<?= e($to) ?>" class="form-control" name="to_date" id="to_date" required />
              </div>
            </div>
            <div class="col-md col-lg-4 col-xl-3">
              <div class="mb-3">
                <label for="register" class="form-label">Caisse</label>
                <select class="form-select" name="register" id="register" data-client-select required>
                  <option value="">Rechercher une caisse…</option>
                  <?php foreach ($registers as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" data-unity="<?= e($r['unity_name'] ?? '') ?>"
                      data-meta="<?= e('Caissier : ' . ($r['cashier_name'] ?? '--') . ' · Solde : ' . $n($r['balance']) . ' FCFA') ?>"
                      <?= $registerId === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-12 text-center">
              <button type="submit" class="btn btn-primary">Générer</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if ($error !== ''): ?>
    <div class="col-12"><div class="alert alert-danger" role="alert"><?= e($error) ?></div></div>
  <?php elseif ($register !== null): ?>
    <div class="col-md col-lg-3 d-flex">
      <div class="card bg-white flex-fill text-center">
        <div class="card-body">
          <h5 class="text-uppercase">Caisse</h5>
          <div class="fs-3"><?= e($register['name']) ?></div>
          <div class="text-secondary"><?= e($register['unity_name'] ?? '--') ?> &middot; <?= e($register['cashier_name'] ?? '--') ?></div>
        </div>
      </div>
    </div>
    <div class="col-md col-lg-3 d-flex">
      <div class="card bg-white flex-fill text-center">
        <div class="card-body">
          <h5 class="text-uppercase">Solde actuel</h5>
          <div class="fs-3"><?= e($n($register['balance'])) ?> <b>FCFA</b></div>
        </div>
      </div>
    </div>
    <div class="col-md col-lg-3 d-flex">
      <div class="card bg-white flex-fill text-center">
        <div class="card-body">
          <h5 class="text-uppercase">Entrées</h5>
          <div class="fs-3 text-success"><?= e($n($in)) ?> <b>FCFA</b></div>
        </div>
      </div>
    </div>
    <div class="col-md col-lg-3 d-flex">
      <div class="card bg-white flex-fill text-center">
        <div class="card-body">
          <h5 class="text-uppercase">Sorties</h5>
          <div class="fs-3 text-danger"><?= e($n(-$out)) ?> <b>FCFA</b></div>
          <div class="text-secondary small">Net : <?= e($n($in + $out)) ?> FCFA</div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title">Mouvements : <span class="text-primary"><?= count($movements) ?></span></div>
          <div class="card-actions d-print-none"><button type="button" class="btn btn-sm" onclick="window.print()">Imprimer</button></div>
        </div>
        <div class="table-responsive">
          <table class="table table-vcenter table-nowrap">
            <thead>
              <tr>
                <th>Date</th><th>Opération</th><th>Référence</th><th>Détail</th>
                <th class="text-end">Sortie FCFA</th><th class="text-end">Entrée FCFA</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($movements as $m): ?>
                <tr>
                  <td><?= e(dt($m['at'])) ?></td>
                  <td><?= e($m['kind']) ?><?php if ($m['pending']): ?> <span class="badge bg-warning-lt">En attente</span><?php endif; ?></td>
                  <td><?php if (!empty($can[$m['alias']])): ?><a href="<?= e(url($m['link'])) ?>"><?= e($m['ref']) ?></a><?php else: ?><?= e($m['ref']) ?><?php endif; ?></td>
                  <td class="text-wrap"><?= e($m['label']) ?></td>
                  <td class="text-end"><?= $m['amount'] < 0 ? e($n(-$m['amount'])) : '' ?></td>
                  <td class="text-end"><?= $m['amount'] >= 0 ? e($n($m['amount'])) : '' ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$movements): ?>
                <tr><td colspan="6" class="text-center text-secondary">Aucune opération sur cette période.</td></tr>
              <?php endif; ?>
            </tbody>
            <?php if ($movements): ?>
              <tfoot>
                <tr><th colspan="4" class="text-end">Totaux (hors opérations en attente)</th>
                  <th class="text-end"><?= e($n(-$out)) ?></th><th class="text-end"><?= e($n($in)) ?></th></tr>
              </tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
