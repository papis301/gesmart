<?php
/** @var array $payment @var array $status @var array $denominations */
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
$notes = [];
foreach ($denominations as $col => $value) {
    $c = (int) ($payment[$col] ?? 0);
    if ($c !== 0) {
        $notes[] = [$value, $c];
    }
}
$by = static fn(string $date, string $who): string => e(dt($payment[$date] ?? null) ?: '--') . (!empty($payment[$who]) ? ' par ' . e($payment[$who]) : '');
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Versement <span class="text-primary"><?= e($payment['id']) ?></span></div>
        <div class="card-actions"><span class="status status-<?= e($status['class']) ?>"><span class="status-dot"></span> <?= e($status['label']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid">
          <div class="datagrid-item"><div class="datagrid-title">Montant</div><div class="datagrid-content"><?= e(fcfa($payment['total'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Date</div><div class="datagrid-content"><?= e(dt($payment['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Utilisateur</div><div class="datagrid-content"><?= e($payment['user_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Caisse créditée</div><div class="datagrid-content"><?= e($payment['register_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Compte bancaire débité</div><div class="datagrid-content">Nº<?= e($payment['bank_id'] ?? '--') ?> - <?= e($payment['bank_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Compte client crédité</div><div class="datagrid-content"><?= $payment['client_id'] ? e($payment['client_id'] . ' - ' . ($payment['client_name'] ?? '')) : '--' ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Par chèque ?</div><div class="datagrid-content"><?= (int) $payment['by_check'] === 1 ? 'OUI' : 'NON' ?></div></div>
          <?php if ((int) $payment['by_check'] === 1): ?>
            <div class="datagrid-item"><div class="datagrid-title">Banque du chèque</div><div class="datagrid-content"><?= e($payment['check_bank'] ?: '--') ?></div></div>
            <div class="datagrid-item"><div class="datagrid-title">Fichier du chèque</div><div class="datagrid-content"><?= e($payment['check_file'] ?: '--') ?></div></div>
          <?php endif; ?>
          <div class="datagrid-item"><div class="datagrid-title">Pièce d'identité</div><div class="datagrid-content"><?= e($payment['nid'] ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Validé</div><div class="datagrid-content"><?= $by('validated_at', 'validator_name') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Confirmé</div><div class="datagrid-content"><?= $by('confirmed_at', 'confirmer_name') ?></div></div>
          <?php if (!empty($payment['aborded_at'])): ?>
            <div class="datagrid-item"><div class="datagrid-title">Annulé</div><div class="datagrid-content"><?= $by('aborded_at', 'aborter_name') ?></div></div>
          <?php endif; ?>
          <div class="datagrid-item"><div class="datagrid-title">Solde de la caisse après opération</div><div class="datagrid-content"><?= e(fcfa($payment['cash_register_balance'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($payment['updated_at']) ?: '--') ?></div></div>
        </div>
        <?php if (!empty($payment['desc'])): ?>
          <div class="mt-3"><div class="datagrid-title">Description</div><div><?= e($payment['desc']) ?></div></div>
        <?php endif; ?>
        <?php if (!empty($payment['reason'])): ?>
          <div class="mt-3"><div class="datagrid-title">Motif</div><div><?= e($payment['reason']) ?></div></div>
        <?php endif; ?>
      </div>
      <div class="card-footer"><a href="<?= e(url('/versements')) ?>" class="btn">Retour</a></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><div class="card-title">Coupures</div></div>
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
