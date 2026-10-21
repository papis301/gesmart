<?php
/**
 * @var array $registers @var string $from @var string $to @var int[] $selected @var bool $submitted
 * @var array $groups @var array $byType @var float $grandTotal @var string $error @var bool $canInfo
 */
use App\Models\DisbursementModel;

$n = static fn($v): string => number_format((float) $v, 0, ',', ' ');
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><div class="card-title">Générer un rapport</div></div>
      <div class="card-body">
        <form action="<?= e(url('/rapports/etat-des-charges')) ?>" method="get">
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
                <label for="registers" class="form-label">Caisses</label>
                <select class="form-select" name="registers[]" id="registers" data-client-select multiple required>
                  <?php foreach ($registers as $r): ?>
                    <option value="<?= (int) $r['id'] ?>" data-unity="<?= e($r['unity_name'] ?? '') ?>"
                      data-meta="<?= e('Caissier : ' . ($r['cashier_name'] ?? '--')) ?>"
                      <?= in_array((int) $r['id'], $selected, true) ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                  <?php endforeach; ?>
                </select>
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
    <div class="col-md col-lg-4 d-flex">
      <div class="card bg-white flex-fill text-center"><div class="card-body">
        <h5 class="text-uppercase">Total des sorties</h5>
        <div class="fs-3 text-danger"><?= e($n($grandTotal)) ?> <b>FCFA</b></div>
        <div class="text-secondary small">hors opérations en attente</div>
      </div></div>
    </div>
    <div class="col-md col-lg-8 d-flex">
      <div class="card flex-fill">
        <div class="card-header"><div class="card-title">Par nature</div></div>
        <div class="table-responsive">
          <table class="table table-vcenter table-nowrap mb-0">
            <thead><tr><th>Nature</th><th class="text-end">Nombre</th><th class="text-end">Montant FCFA</th><th class="text-end">%</th></tr></thead>
            <tbody>
              <?php foreach ($byType as $label => $t): ?>
                <tr>
                  <td><?= e($label) ?></td>
                  <td class="text-end"><?= e($n($t['count'])) ?></td>
                  <td class="text-end"><?= e($n($t['total'])) ?></td>
                  <td class="text-end"><?= $grandTotal > 0 ? e(number_format($t['total'] * 100 / $grandTotal, 1, ',', ' ')) : '0' ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$byType): ?><tr><td colspan="4" class="text-center text-secondary">Aucune sortie validée.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php foreach ($groups as $name => $g): ?>
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <div class="card-title"><?= e($name) ?> : <span class="text-danger"><?= e($n($g['total'])) ?> FCFA</span></div>
            <div class="card-actions d-print-none"><button type="button" class="btn btn-sm" onclick="window.print()">Imprimer</button></div>
          </div>
          <div class="table-responsive">
            <table class="table table-vcenter table-nowrap">
              <thead><tr><th>Date</th><th>Référence</th><th>Nature</th><th>Description</th><th>Client</th><th class="text-end">Montant FCFA</th></tr></thead>
              <tbody>
                <?php foreach ($g['rows'] as $d): ?>
                  <tr>
                    <td><?= e(dt($d['created_at'])) ?></td>
                    <td><?php if ($canInfo): ?><a href="<?= e(url('/decaissements/' . $d['id'])) ?>"><?= e($d['id']) ?></a><?php else: ?><?= e($d['id']) ?><?php endif; ?></td>
                    <td><?= e(DisbursementModel::type($d['type'])) ?><?php if ($d['pending']): ?> <span class="badge bg-warning-lt">En attente</span><?php endif; ?></td>
                    <td class="text-wrap"><?= e($d['description']) ?></td>
                    <td><?= e($d['client_name'] ?? '--') ?></td>
                    <td class="text-end"><?= e($n($d['amount'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$groups): ?>
      <div class="col-12"><div class="alert alert-info" role="alert">Aucune sortie de caisse sur cette période.</div></div>
    <?php endif; ?>
  <?php endif; ?>
</div>
