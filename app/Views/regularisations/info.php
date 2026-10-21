<?php
/** @var array $reg @var array $status @var array $state */
use App\Models\RegularisationModel as M;

$r = $reg;
$d = static function (?string $v): string {
    $v = M::date($v);
    return $v === null ? '--' : date('d/m/Y', strtotime($v));
};
?>
<div class="row row-cards">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Régularisation <span class="text-primary"><?= e($r['id']) ?></span></div>
        <div class="card-actions">
          <span class="status status-<?= e($status['class']) ?>"><span class="status-dot"></span> <?= e($status['label']) ?></span>
          <span class="status status-<?= e($state['class']) ?> ms-2"><span class="status-dot"></span> <?= e($state['label']) ?></span>
        </div>
      </div>
      <div class="card-body">
        <div class="datagrid">
          <div class="datagrid-item"><div class="datagrid-title">Client</div><div class="datagrid-content"><?= e($r['client_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Nº de compte</div><div class="datagrid-content"><?= e($r['client_id']) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Téléphone</div><div class="datagrid-content"><?= e($r['client_phone'] ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Solde du compte</div><div class="datagrid-content"><?= e(fcfa($r['account_balance'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Plafond crédit accordé</div><div class="datagrid-content"><?= e(fcfa($r['granted_credit'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Crédit utilisé à la régularisation</div><div class="datagrid-content"><?= e(fcfa($r['credit'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Épargne à la régularisation</div><div class="datagrid-content"><?= e(fcfa($r['savings'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Date de début</div><div class="datagrid-content"><?= e($d($r['created_at'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Date d'échéance</div><div class="datagrid-content"><?= e($d($r['due_date'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Régularisé le</div><div class="datagrid-content"><?= e($d($r['regulated_at'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Auteur</div><div class="datagrid-content"><?= e($r['author_name'] ?? '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($r['updated_at']) ?: '--') ?></div></div>
        </div>
      </div>
      <div class="card-footer"><a href="<?= e(url('/regularisations')) ?>" class="btn">Retour</a></div>
    </div>
  </div>
</div>
