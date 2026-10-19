<?php
/**
 * @var array $t @var string $status @var array $denominations
 */
use App\Models\InterUnitTransferModel;

$n = static fn($v): string => number_format((float) $v, 0, ',', ' ');
$statusClass = ['pending' => 'status-warning', 'validated' => 'status-success', 'aborted' => 'status-danger'];
$coupures = [];
foreach ($denominations as $col => $value) {
    $q = (int) ($t[$col] ?? 0);
    if ($q !== 0) {
        $coupures[] = ['value' => $value, 'qty' => $q];
    }
}
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Transfert hors place nº <span class="text-primary"><?= e($t['id']) ?></span></div>
        <div class="card-actions"><span class="status <?= e($statusClass[$status]) ?>"><span class="status-dot"></span><?= e(InterUnitTransferModel::STATUSES[$status]) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid">
          <div class="datagrid-item"><div class="datagrid-title">Date</div><div class="datagrid-content"><?= e(dt($t['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Montant</div><div class="datagrid-content"><?= e($n($t['total'])) ?> FCFA</div></div>
          <div class="datagrid-item"><div class="datagrid-title">Unité de production</div><div class="datagrid-content"><?= e($t['unity_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caisse émettrice</div><div class="datagrid-content"><?= e($t['from_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Donneur d'ordre</div><div class="datagrid-content"><?= e($t['user_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Unité bénéficiaire</div><div class="datagrid-content"><?= e($t['to_unity_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caisse bénéficiaire</div><div class="datagrid-content"><?= e($t['to_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Bénéficiaire</div><div class="datagrid-content"><?= e($t['to_user_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Validé le</div><div class="datagrid-content"><?= e(dt($t['validated_at']) ?: '--') ?><?= !empty($t['validated_at']) && !empty($t['validator_name']) ? ' par ' . e($t['validator_name']) : '' ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Arrêté de caisse</div><div class="datagrid-content"><?= e($t['closure_id'] ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Solde de la caisse émettrice après le transfert</div><div class="datagrid-content"><?= e($n($t['cash_register_balance'])) ?> FCFA</div></div>
          <?php if ($status === 'aborted'): ?>
            <div class="datagrid-item"><div class="datagrid-title">Annulé le</div><div class="datagrid-content"><?= e(dt($t['aborded_at'])) ?></div></div>
            <div class="datagrid-item" style="grid-column: 1 / -1;"><div class="datagrid-title">Motif d'annulation</div><div class="datagrid-content"><?= e($t['reason'] ?: '--') ?></div></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-footer d-flex gap-2">
        <a href="<?= e(url('/transferts/hors_places')) ?>" class="btn">Retour à la liste</a>
        <?php if ($status === 'pending'): ?><a href="<?= e(url('/validations/transferts/hors_places')) ?>" class="btn btn-outline-warning">Transferts en attente</a><?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($coupures): ?>
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header"><div class="card-title">Coupures transférées</div></div>
        <div class="table-responsive">
          <table class="table table-sm table-vcenter card-table">
            <thead><tr><th>Coupure</th><th class="text-end">Quantité</th><th class="text-end">Montant</th></tr></thead>
            <tbody>
              <?php foreach ($coupures as $c): ?>
                <tr><td><?= e($n($c['value'])) ?></td><td class="text-end"><?= (int) $c['qty'] ?></td><td class="text-end"><?= e($n($c['value'] * $c['qty'])) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
