<?php
/** @var array $closure @var array $denominations */
use App\Models\ClosureModel as M;

$c = $closure;
$gap = M::gap($c);
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
$count = 0;
$sum = 0;
$lines = [];
foreach ($denominations as $col => $value) {
    $q = (int) ($c[$col] ?? 0);
    $lines[] = [$value, $q];
    $count += $q;
    $sum += $value * $q;
}
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Arrêté de caisse <span class="text-primary"><?= e($c['id']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid">
          <div class="datagrid-item"><div class="datagrid-title">Date</div><div class="datagrid-content"><?= e(dt($c['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Unité de production</div><div class="datagrid-content"><?= e($c['unity_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caisse</div><div class="datagrid-content"><?= e($c['register_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caissier</div><div class="datagrid-content"><?= e($c['cashier_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Auteur de l'arrêté</div><div class="datagrid-content"><?= e($c['author_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Solde informatique</div><div class="datagrid-content"><?= e(fcfa($c['total'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Encaisse physique</div><div class="datagrid-content"><?= e(fcfa($c['total_real'])) ?></div></div>
          <div class="datagrid-item">
            <div class="datagrid-title">Écart encaisse</div>
            <div class="datagrid-content <?= $gap < 0 ? 'text-danger' : ($gap > 0 ? 'text-success' : '') ?>"><?= e(fcfa($gap)) ?></div>
          </div>
          <div class="datagrid-item"><div class="datagrid-title">Créé le</div><div class="datagrid-content"><?= e(dt($c['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($c['updated_at']) ?: '--') ?></div></div>
        </div>
      </div>
      <div class="card-footer"><a href="<?= e(url('/arretes-de-caisses')) ?>" class="btn">Retour</a></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><div class="card-title">Billetage</div></div>
      <?php if ($count === 0): ?>
        <div class="card-body text-secondary">Aucun billetage enregistré pour cet arrêté.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-vcenter table-sm card-table">
            <thead><tr><th>Coupure</th><th class="text-end">Quantité</th><th class="text-end">Montant</th></tr></thead>
            <tbody>
              <?php foreach ($lines as [$value, $q]): if ($q === 0) { continue; } ?>
                <tr><td><?= e($n($value)) ?></td><td class="text-end"><?= (int) $q ?></td><td class="text-end"><?= e($n($value * $q)) ?></td></tr>
              <?php endforeach; ?>
              <tr class="fw-bold"><td>Total</td><td class="text-end"><?= (int) $count ?></td><td class="text-end"><?= e($n($sum)) ?></td></tr>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
