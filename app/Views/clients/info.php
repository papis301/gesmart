<?php
/**
 * @var array $client @var float $solde @var array $unities @var array $usage @var bool $hasHistory
 * @var bool $canEdit @var bool $canDelete @var bool $canValidate
 */
use App\Core\Csrf;
use App\Core\View;

$n = static fn($v): string => number_format((float) $v, 0, ',', ' ');
$validated = (int) $client['validated'] === 1;
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Client <span class="text-primary"><?= e($client['name']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid mb-3">
          <div class="datagrid-item"><div class="datagrid-title">Nº de compte</div><div class="datagrid-content"><?= e($client['id']) ?></div></div>
          <div class="datagrid-item">
            <div class="datagrid-title">Statut</div>
            <div class="datagrid-content">
              <?php if ($validated): ?>
                <span class="status status-success"><span class="status-dot"></span><span>Validé<?= !empty($client['validator_name']) ? ' par ' . e($client['validator_name']) : '' ?></span></span>
              <?php else: ?>
                <span class="status status-warning"><span class="status-dot"></span><span>En attente de validation</span></span>
              <?php endif; ?>
            </div>
          </div>
          <div class="datagrid-item"><div class="datagrid-title">Crédit</div><div class="datagrid-content"><?= e($n($client['credit'])) ?> FCFA</div></div>
          <div class="datagrid-item"><div class="datagrid-title">FAGAROU / remboursements</div><div class="datagrid-content"><?= e($n($client['balance'])) ?> FCFA</div></div>
          <div class="datagrid-item"><div class="datagrid-title">Solde du compte</div><div class="datagrid-content <?= $solde < -0.5 ? 'text-danger' : '' ?>"><?= e($n($solde)) ?> FCFA</div></div>
          <div class="datagrid-item"><div class="datagrid-title">Créé le</div><div class="datagrid-content"><?= e(dt($client['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($client['updated_at']) ?: '--') ?></div></div>
        </div>

        <form id="editForm" action="<?= e(url('/clients/modifier')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= e($client['id']) ?>">
          <?= View::partial('clients/_fields', ['u' => $client, 'unities' => $unities, 'mode' => 'edit', 'p' => 'e_', 'readonly' => !$canEdit]) ?>
        </form>
      </div>
      <div class="card-footer d-flex gap-2">
        <a href="<?= e(url('/clients')) ?>" class="btn me-auto">Retour</a>
        <?php if ($canDelete): ?>
          <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmDelete">Supprimer</button>
        <?php endif; ?>
        <?php if (!$validated && $canValidate): ?>
          <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmValidate">Valider</button>
        <?php endif; ?>
        <?php if ($canEdit): ?>
          <button form="editForm" type="submit" class="btn btn-primary">Enregistrer</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><div class="card-title">Historique</div></div>
      <div class="list-group list-group-flush">
        <?php foreach ($usage as $label => $count): ?>
          <div class="list-group-item d-flex justify-content-between">
            <span><?= e($label) ?></span>
            <span class="badge <?= $count > 0 ? 'bg-blue-lt' : 'bg-secondary-lt' ?>"><?= (int) $count ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="card-footer text-secondary">
        Le crédit et le montant FAGAROU/remboursements évoluent avec les opérations : ils ne se modifient pas ici.
        <?php if ($hasHistory): ?>Ce client a déjà des opérations : son unité ne peut plus être changée.<?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if (!$validated && $canValidate): ?>
  <div class="modal modal-sm" id="confirmValidate" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form action="<?= e(url('/clients/valider')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= e($client['id']) ?>">
          <div class="modal-body">
            <div class="modal-title">Valider ce client ?</div>
            <div>Le client « <?= e($client['name']) ?> » sera marqué comme validé.</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-success">Valider</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if ($canDelete): ?>
  <div class="modal modal-sm" id="confirmDelete" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form action="<?= e(url('/clients/supprimer')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= e($client['id']) ?>">
          <div class="modal-body">
            <div class="modal-title">Supprimer ce client ?</div>
            <div>
              Le client « <?= e($client['name']) ?> » sera supprimé. L'historique des opérations est conservé.
              <?php if (abs($solde) >= 0.5): ?>
                <div class="text-danger mt-2">Son solde est de <?= e($n($solde)) ?> FCFA : la suppression sera refusée.</div>
              <?php endif; ?>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-danger">Supprimer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>
