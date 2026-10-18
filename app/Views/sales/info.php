<?php
/** @var array $sale @var array $status @var array $lines @var array $denominations */
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
$savings = (float) $sale['savings'];
$qty = 0.0; $amount = 0.0;
foreach ($lines as $l) {
    $qty += (float) $l['quantity'];
    $amount += (float) $l['quantity'] * (float) $l['price'];
}
$notes = static function (string $prefix) use ($sale, $denominations): array {
    $out = [];
    foreach ($denominations as $col => $value) {
        $c = (int) ($sale[$prefix . $col] ?? 0);
        if ($c !== 0) {
            $out[] = [$value, $c];
        }
    }
    return $out;
};
$received = $notes('received_');
$returned = $notes('returned_');
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header">
        <div class="card-title">Vente <span class="text-primary"><?= e($sale['id']) ?></span></div>
        <div class="card-actions"><span class="status status-<?= e($status['class']) ?>"><span class="status-dot"></span> <?= e($status['label']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid">
          <div class="datagrid-item"><div class="datagrid-title">Date</div><div class="datagrid-content"><?= e(dt($sale['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Unité de production</div><div class="datagrid-content"><?= e($sale['unity_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caisse</div><div class="datagrid-content"><?= e($sale['register_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caissier</div><div class="datagrid-content"><?= e($sale['cashier_name'] ?? '--') ?></div></div>
          <div class="datagrid-item">
            <div class="datagrid-title">Client</div>
            <div class="datagrid-content">
              <?php if ($sale['client_name']): ?>
                <?= e($sale['client_name']) ?> <small class="text-secondary">(Nº <?= e($sale['client_id']) ?>)</small>
              <?php else: ?>--<?php endif; ?>
            </div>
          </div>
          <div class="datagrid-item"><div class="datagrid-title">Téléphone du client</div><div class="datagrid-content"><?= e($sale['client_phone'] ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">À crédit ?</div><div class="datagrid-content"><?= (int) $sale['debt'] === 1 ? 'OUI' : 'NON' ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Véhicule</div><div class="datagrid-content"><?= e($sale['car'] ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Chauffeur</div><div class="datagrid-content"><?= e($sale['driver'] ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Validée le</div><div class="datagrid-content"><?= e(dt($sale['validated_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Validée par</div><div class="datagrid-content"><?= e($sale['validator_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifiée le</div><div class="datagrid-content"><?= e(dt($sale['updated_at']) ?: '--') ?></div></div>
          <?php if (!empty($sale['aborded_at'])): ?>
            <div class="datagrid-item"><div class="datagrid-title">Annulée le</div><div class="datagrid-content"><?= e(dt($sale['aborded_at'])) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($sale['reason'])): ?>
            <div class="datagrid-item"><div class="datagrid-title">Motif</div><div class="datagrid-content"><?= e($sale['reason']) ?></div></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-footer"><a href="<?= e(url('/ventes')) ?>" class="btn">Retour</a></div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Lignes de vente</div></div>
      <div class="table-responsive">
        <table class="table table-vcenter card-table">
          <thead><tr><th>Produit</th><th class="text-end">Prix</th><th class="text-end">Caisses</th><th class="text-end">FAGAROU</th><th class="text-end">Montant</th></tr></thead>
          <tbody>
            <?php foreach ($lines as $l): ?>
              <tr>
                <td><?= e($l['name'] ?? '--') ?></td>
                <td class="text-end"><?= e($n($l['price'])) ?></td>
                <td class="text-end"><?= e($n($l['quantity'])) ?></td>
                <td class="text-end"><?= e($n($savings * (float) $l['quantity'])) ?></td>
                <td class="text-end"><?= e($n((float) $l['quantity'] * (float) $l['price'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$lines): ?><tr><td colspan="5" class="text-center text-secondary">Aucune ligne.</td></tr><?php endif; ?>
          </tbody>
          <tfoot>
            <tr><th colspan="2">Totaux</th><th class="text-end"><?= e($n($qty)) ?></th><th class="text-end"><?= e($n($savings * $qty)) ?></th><th class="text-end"><?= e($n($amount)) ?></th></tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><div class="card-title">Règlement</div></div>
      <div class="card-body">
        <div class="datagrid">
          <div class="datagrid-item"><div class="datagrid-title">Total à payer</div><div class="datagrid-content"><?= e(fcfa($sale['total'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Montant reçu</div><div class="datagrid-content"><?= e(fcfa($sale['received'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Monnaie rendue</div><div class="datagrid-content"><?= e(fcfa($sale['returned'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Solde client après vente</div><div class="datagrid-content"><?= $sale['balance'] === null ? '--' : e(fcfa($sale['balance'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Solde caisse après vente</div><div class="datagrid-content"><?= e(fcfa($sale['cash_register_balance'])) ?></div></div>
        </div>
      </div>
    </div>

    <?php foreach (['Coupures reçues' => $received, 'Coupures rendues' => $returned] as $label => $list): ?>
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
