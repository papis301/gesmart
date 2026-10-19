<?php
/** @var array $withdrawal @var array $status */
use App\Models\WithdrawalModel as M;

$w = $withdrawal;
$by = static fn(string $date, string $who): string => e(dt($w[$date] ?? null) ?: '--') . (!empty($w[$who]) ? ' par ' . e($w[$who]) : '');
$hasFile = !empty($w['check_file']) && preg_match('#^https?://#i', (string) $w['check_file']) === 1;
?>
<div class="row row-cards">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Retrait <span class="text-primary"><?= e($w['id']) ?></span></div>
        <div class="card-actions"><span class="status status-<?= e($status['class']) ?>"><span class="status-dot"></span> <?= e($status['label']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid">
          <div class="datagrid-item"><div class="datagrid-title">Montant</div><div class="datagrid-content"><?= e(fcfa($w['total'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Date</div><div class="datagrid-content"><?= e(dt($w['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Nature</div><div class="datagrid-content"><?= e(M::type($w['type'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Utilisateur</div><div class="datagrid-content"><?= e($w['user_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Compte bancaire débité</div><div class="datagrid-content">Nº<?= e($w['from_bank_id'] ?? '--') ?> - <?= e($w['from_bank_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Compte bancaire crédité</div><div class="datagrid-content"><?= $w['to_bank_id'] ? 'Nº' . e($w['to_bank_id']) . ' - ' . e($w['to_bank_name'] ?? '--') : '--' ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Compte client débité</div><div class="datagrid-content"><?= $w['client_id'] ? e($w['client_id'] . ' - ' . ($w['client_name'] ?? '')) : '--' ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Retiré par (identité)</div><div class="datagrid-content"><?= e($w['nid'] ?: '--') ?></div></div>
          <div class="datagrid-item">
            <div class="datagrid-title">Chèque</div>
            <div class="datagrid-content"><?php if ($hasFile): ?><a href="<?= e($w['check_file']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir le fichier</a><?php else: ?>Pas de fichier joint<?php endif; ?></div>
          </div>
          <div class="datagrid-item"><div class="datagrid-title">Validé</div><div class="datagrid-content"><?= $by('validated_at', 'validator_name') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Confirmé</div><div class="datagrid-content"><?= $by('confirmed_at', 'confirmer_name') ?></div></div>
          <?php if (!empty($w['aborded_at'])): ?>
            <div class="datagrid-item"><div class="datagrid-title">Annulé</div><div class="datagrid-content"><?= $by('aborded_at', 'aborter_name') ?></div></div>
          <?php endif; ?>
          <?php if ($w['balance'] !== null): ?>
            <div class="datagrid-item"><div class="datagrid-title">Solde après opération</div><div class="datagrid-content"><?= e(fcfa($w['balance'])) ?></div></div>
          <?php endif; ?>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($w['updated_at']) ?: '--') ?></div></div>
        </div>
        <?php if (!empty($w['desc'])): ?>
          <div class="mt-3"><div class="datagrid-title">Description</div><div><?= e($w['desc']) ?></div></div>
        <?php endif; ?>
        <?php if (!empty($w['reason'])): ?>
          <div class="mt-3"><div class="datagrid-title">Motif</div><div><?= e($w['reason']) ?></div></div>
        <?php endif; ?>
      </div>
      <div class="card-footer"><a href="<?= e(url('/retraits')) ?>" class="btn">Retour</a></div>
    </div>
  </div>
</div>
