<?php
/**
 * @var array $c @var bool $aborted @var array $denominations @var bool $canInfoSale
 */
use App\Models\CollectionModel;

$n = static fn($v): string => number_format((float) $v, 0, ',', ' ');
$coupures = static function (array $row, string $prefix, array $denominations): array {
    $out = [];
    foreach ($denominations as $col => $value) {
        $q = (int) ($row[$prefix . $col] ?? 0);
        if ($q !== 0) {
            $out[] = ['value' => $value, 'qty' => $q];
        }
    }
    return $out;
};
$received = $coupures($c, 'received_', $denominations);
$returned = $coupures($c, 'returned_', $denominations);
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Encaissement nº <span class="text-primary"><?= e($c['id']) ?></span></div>
        <div class="card-actions">
          <?php if ($aborted): ?>
            <span class="status status-danger"><span class="status-dot"></span>Annulé</span>
          <?php else: ?>
            <span class="status status-success"><span class="status-dot"></span>Effectué</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body">
        <div class="datagrid">
          <div class="datagrid-item"><div class="datagrid-title">Nature</div><div class="datagrid-content"><?= e(CollectionModel::typeLabel($c['type'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Date</div><div class="datagrid-content"><?= e(dt($c['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Montant encaissé</div><div class="datagrid-content"><?= e($n((float) $c['received'] - (float) $c['returned'])) ?> FCFA</div></div>
          <div class="datagrid-item"><div class="datagrid-title">Reçu</div><div class="datagrid-content"><?= e($n($c['received'])) ?> FCFA</div></div>
          <div class="datagrid-item"><div class="datagrid-title">Rendu</div><div class="datagrid-content"><?= e($n($c['returned'])) ?> FCFA</div></div>
          <div class="datagrid-item"><div class="datagrid-title">Unité de production</div><div class="datagrid-content"><?= e($c['unity_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caisse</div><div class="datagrid-content"><?= e($c['register_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caissier</div><div class="datagrid-content"><?= e($c['user_name'] ?? '--') ?></div></div>
          <div class="datagrid-item">
            <div class="datagrid-title">Client</div>
            <div class="datagrid-content">
              <?php if (!empty($c['client_id'])): ?>
                <a href="<?= e(url('/clients/' . $c['client_id'])) ?>"><?= e($c['client_name'] ?? $c['client_id']) ?></a> (<?= e($c['client_id']) ?>)
              <?php else: ?>--<?php endif; ?>
            </div>
          </div>
          <div class="datagrid-item">
            <div class="datagrid-title">Vente à crédit concernée</div>
            <div class="datagrid-content">
              <?php if (!empty($c['sale_id'])): ?>
                <?php if ($canInfoSale): ?><a href="<?= e(url('/ventes/' . $c['sale_id'])) ?>"><?= e($c['sale_id']) ?></a><?php else: ?><?= e($c['sale_id']) ?><?php endif; ?>
              <?php else: ?>--<?php endif; ?>
            </div>
          </div>
          <div class="datagrid-item"><div class="datagrid-title">Déposant (pièce d'identité)</div><div class="datagrid-content"><?= e($c['depositary_id'] ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Arrêté de caisse</div><div class="datagrid-content"><?= e($c['closure_id'] ?: '--') ?></div></div>
          <div class="datagrid-item" style="grid-column: 1 / -1;"><div class="datagrid-title">Description</div><div class="datagrid-content"><?= e($c['description'] ?: '--') ?></div></div>
          <?php if ($aborted): ?>
            <div class="datagrid-item"><div class="datagrid-title">Annulé le</div><div class="datagrid-content"><?= e(dt($c['aborded_at'])) ?></div></div>
            <div class="datagrid-item"><div class="datagrid-title">Annulé par</div><div class="datagrid-content"><?= e($c['validator_name'] ?? '--') ?></div></div>
            <div class="datagrid-item" style="grid-column: 1 / -1;"><div class="datagrid-title">Motif d'annulation</div><div class="datagrid-content"><?= e($c['reason'] ?: '--') ?></div></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-footer"><a href="<?= e(url('/encaissements')) ?>" class="btn">Retour à la liste</a></div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><div class="card-title">Soldes après l'encaissement</div></div>
      <div class="card-body">
        <div class="datagrid">
          <div class="datagrid-item"><div class="datagrid-title">Solde du client</div><div class="datagrid-content"><?= $c['balance'] === null ? '--' : e($n($c['balance'])) . ' FCFA' ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Solde de la caisse</div><div class="datagrid-content"><?= e($n($c['cash_register_balance'])) ?> FCFA</div></div>
        </div>
      </div>
    </div>

    <?php if ($received || $returned): ?>
      <div class="card">
        <div class="card-header"><div class="card-title">Coupures</div></div>
        <div class="card-body">
          <?php foreach (['Reçues' => $received, 'Rendues' => $returned] as $label => $list): ?>
            <?php if ($list): ?>
              <div class="fw-bold mb-1"><?= e($label) ?></div>
              <div class="table-responsive mb-3">
                <table class="table table-sm table-vcenter">
                  <tbody>
                    <?php foreach ($list as $co): ?>
                      <tr><td><?= e($n($co['value'])) ?></td><td class="text-end">× <?= (int) $co['qty'] ?></td><td class="text-end"><?= e($n($co['value'] * $co['qty'])) ?></td></tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
