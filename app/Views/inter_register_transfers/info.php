<?php
/** @var array $transfer @var array $status @var array $denominations */
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
$notes = [];
foreach ($denominations as $col => $value) {
    $c = (int) ($transfer[$col] ?? 0);
    if ($c !== 0) {
        $notes[] = [$value, $c];
    }
}
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Transfert <span class="text-primary"><?= e($transfer['id']) ?></span></div>
        <div class="card-actions"><span class="status status-<?= e($status['class']) ?>"><span class="status-dot"></span> <?= e($status['label']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid">
          <div class="datagrid-item"><div class="datagrid-title">Montant</div><div class="datagrid-content"><?= e(fcfa($transfer['total'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Date</div><div class="datagrid-content"><?= e(dt($transfer['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Unité de production</div><div class="datagrid-content"><?= e($transfer['unity_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Initié par</div><div class="datagrid-content"><?= e($transfer['creator_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caisse donneuse d'ordre</div><div class="datagrid-content"><?= e($transfer['from_register'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caissier donneur d'ordre</div><div class="datagrid-content"><?= e($transfer['from_cashier'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caisse bénéficiaire</div><div class="datagrid-content"><?= e($transfer['to_register'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caissier bénéficiaire</div><div class="datagrid-content"><?= e($transfer['to_cashier'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Validé le</div><div class="datagrid-content"><?= e(dt($transfer['validated_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Validé par</div><div class="datagrid-content"><?= e($transfer['validator_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($transfer['updated_at']) ?: '--') ?></div></div>
          <?php if (!empty($transfer['aborded_at'])): ?>
            <div class="datagrid-item"><div class="datagrid-title">Annulé le</div><div class="datagrid-content"><?= e(dt($transfer['aborded_at'])) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($transfer['reason'])): ?>
            <div class="datagrid-item"><div class="datagrid-title">Motif</div><div class="datagrid-content"><?= e($transfer['reason']) ?></div></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-footer"><a href="<?= e(url('/transferts/inter_caisses')) ?>" class="btn">Retour</a></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><div class="card-title">Coupures transférées</div></div>
      <div class="table-responsive">
        <table class="table table-vcenter table-sm card-table">
          <thead><tr><th>Coupure</th><th class="text-end">Quantité</th><th class="text-end">Montant</th></tr></thead>
          <tbody>
            <?php foreach ($notes as [$value, $c]): ?>
              <tr><td><?= e($n($value)) ?></td><td class="text-end"><?= (int) $c ?></td><td class="text-end"><?= e($n($value * $c)) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$notes): ?><tr><td colspan="3" class="text-center text-secondary">Aucune coupure.</td></tr><?php endif; ?>
          </tbody>
          <tfoot><tr><th colspan="2">Total</th><th class="text-end"><?= e(fcfa(array_sum(array_map(static fn($x) => $x[0] * $x[1], $notes)))) ?></th></tr></tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
