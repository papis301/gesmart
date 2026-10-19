<?php
/** @var array $disbursement @var array $status @var array $denominations */
use App\Models\DisbursementModel as M;

$d = $disbursement;
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
$notes = static function (string $prefix) use ($d, $denominations): array {
    $out = [];
    foreach ($denominations as $col => $value) {
        $c = (int) ($d[$prefix . $col] ?? 0);
        if ($c !== 0) {
            $out[] = [$value, $c];
        }
    }
    return $out;
};
$hasFile = !empty($d['file']) && preg_match('#^https?://#i', (string) $d['file']) === 1;
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Décaissement <span class="text-primary"><?= e($d['id']) ?></span></div>
        <div class="card-actions"><span class="status status-<?= e($status['class']) ?>"><span class="status-dot"></span> <?= e($status['label']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid">
          <div class="datagrid-item"><div class="datagrid-title">Montant</div><div class="datagrid-content"><?= e(fcfa(M::amount($d))) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Date</div><div class="datagrid-content"><?= e(dt($d['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Nature</div><div class="datagrid-content"><?= e(M::type($d['type'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Unité de production</div><div class="datagrid-content"><?= e($d['unity_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caisse</div><div class="datagrid-content"><?= e($d['register_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caissier</div><div class="datagrid-content"><?= e($d['cashier_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Client</div><div class="datagrid-content"><?= $d['client_id'] ? e($d['client_id'] . ' - ' . ($d['client_name'] ?? '')) : '--' ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Pièce du bénéficiaire</div><div class="datagrid-content"><?= e($d['withdrawer_id'] ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Validé le</div><div class="datagrid-content"><?= e(dt($d['validated_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Validé par</div><div class="datagrid-content"><?= e($d['validator_name'] ?? '--') ?></div></div>
          <?php if (!empty($d['aborded_at'])): ?>
            <div class="datagrid-item"><div class="datagrid-title">Annulé le</div><div class="datagrid-content"><?= e(dt($d['aborded_at'])) ?></div></div>
          <?php endif; ?>
          <div class="datagrid-item"><div class="datagrid-title">Solde caisse après opération</div><div class="datagrid-content"><?= e(fcfa($d['cash_register_balance'])) ?></div></div>
          <?php if ($d['balance'] !== null): ?>
            <div class="datagrid-item"><div class="datagrid-title">Solde client après opération</div><div class="datagrid-content"><?= e(fcfa($d['balance'])) ?></div></div>
          <?php endif; ?>
          <div class="datagrid-item">
            <div class="datagrid-title">Pièce jointe</div>
            <div class="datagrid-content"><?php if ($hasFile): ?><a href="<?= e($d['file']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir le fichier</a><?php else: ?>Aucune<?php endif; ?></div>
          </div>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($d['updated_at']) ?: '--') ?></div></div>
        </div>
        <?php if (!empty($d['description'])): ?>
          <div class="mt-3"><div class="datagrid-title">Description</div><div><?= e($d['description']) ?></div></div>
        <?php endif; ?>
        <?php if (!empty($d['reason'])): ?>
          <div class="mt-3"><div class="datagrid-title">Motif</div><div><?= e($d['reason']) ?></div></div>
        <?php endif; ?>
      </div>
      <div class="card-footer"><a href="<?= e(url('/validations/decaissements')) ?>" class="btn">Retour</a></div>
    </div>
  </div>
  <div class="col-lg-5">
    <?php foreach (['Coupures reçues' => $notes('received_'), 'Coupures sorties de caisse' => $notes('returned_')] as $label => $list): ?>
      <?php if ($list): ?>
        <div class="card mb-3">
          <div class="card-header"><div class="card-title"><?= e($label) ?></div></div>
          <div class="table-responsive">
            <table class="table table-vcenter table-sm card-table">
              <thead><tr><th>Coupure</th><th class="text-end">Quantité</th><th class="text-end">Montant</th></tr></thead>
              <tbody>
                <?php foreach ($list as [$value, $c]): ?>
                  <tr><td><?= e($n($value)) ?></td><td class="text-end"><?= (int) $c ?></td><td class="text-end"><?= e($n($value * $c)) ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>
