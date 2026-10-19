<?php
/** @var array $transfer @var array $status */
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
          <div class="datagrid-item"><div class="datagrid-title">Montant</div><div class="datagrid-content"><?= e(fcfa($transfer['amount'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Date</div><div class="datagrid-content"><?= e(dt($transfer['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Compte à créditer</div><div class="datagrid-content"><?= e($transfer['from']) ?> — <?= e($transfer['from_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Compte à débiter</div><div class="datagrid-content"><?= e($transfer['to']) ?> — <?= e($transfer['to_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Solde après transfert</div><div class="datagrid-content"><?= $transfer['balance'] === null ? '--' : e(fcfa($transfer['balance'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Validé le</div><div class="datagrid-content"><?= e(dt($transfer['validated_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Validé par</div><div class="datagrid-content"><?= e($transfer['validator_name'] ?? '--') ?></div></div>
          <?php if (!empty($transfer['aborded_at'])): ?>
            <div class="datagrid-item"><div class="datagrid-title">Annulé le</div><div class="datagrid-content"><?= e(dt($transfer['aborded_at'])) ?></div></div>
            <div class="datagrid-item"><div class="datagrid-title">Annulé par</div><div class="datagrid-content"><?= e($transfer['aborter_name'] ?? '--') ?></div></div>
          <?php endif; ?>
          <?php if (!empty($transfer['confirmed_at'])): ?>
            <div class="datagrid-item"><div class="datagrid-title">Confirmé le</div><div class="datagrid-content"><?= e(dt($transfer['confirmed_at'])) ?></div></div>
            <div class="datagrid-item"><div class="datagrid-title">Confirmé par</div><div class="datagrid-content"><?= e($transfer['confirmer_name'] ?? '--') ?></div></div>
          <?php endif; ?>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($transfer['updated_at']) ?: '--') ?></div></div>
          <?php if (!empty($transfer['reason'])): ?>
            <div class="datagrid-item"><div class="datagrid-title">Motif</div><div class="datagrid-content"><?= e($transfer['reason']) ?></div></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-footer"><a href="<?= e(url('/transferts/compte_a_comptes')) ?>" class="btn">Retour</a></div>
    </div>
  </div>
</div>
