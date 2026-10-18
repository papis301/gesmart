<?php
/**
 * @var array $register @var array $unities @var array $users @var array $history @var bool $hasHistory
 * @var bool $empty @var array $denominations @var bool $canEdit @var bool $canDelete
 */
use App\Core\Csrf;
use App\Core\View;

$totalNotes = 0;
foreach ($denominations as $col => $value) {
    $totalNotes += (int) $register[$col] * $value;
}
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Caisse <span class="text-primary"><?= e($register['name']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid mb-3">
          <div class="datagrid-item"><div class="datagrid-title">Solde</div><div class="datagrid-content"><?= e(fcfa($register['balance'])) ?></div></div>
          <div class="datagrid-item">
            <div class="datagrid-title">État</div>
            <div class="datagrid-content">
              <?php if ((int) $register['closed'] === 1): ?>
                <span class="badge bg-secondary-lt">Clôturée</span>
              <?php else: ?>
                <span class="badge bg-green-lt">Ouverte</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="datagrid-item"><div class="datagrid-title">Créée le</div><div class="datagrid-content"><?= e(dt($register['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifiée le</div><div class="datagrid-content"><?= e(dt($register['updated_at']) ?: '--') ?></div></div>
        </div>

        <form id="editForm" action="<?= e(url('/caisses/modifier')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $register['id'] ?>">
          <?= View::partial('cash_registers/_fields', ['u' => $register, 'unities' => $unities, 'users' => $users, 'p' => 'e_', 'readonly' => !$canEdit]) ?>
        </form>
      </div>
      <div class="card-footer d-flex gap-2">
        <a href="<?= e(url('/caisses')) ?>" class="btn me-auto">Retour</a>
        <?php if ($canDelete): ?>
          <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmDelete">Supprimer</button>
        <?php endif; ?>
        <?php if ($canEdit): ?>
          <button form="editForm" type="submit" class="btn btn-primary">Enregistrer</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><div class="card-title">Coupures en caisse</div></div>
      <div class="table-responsive">
        <table class="table table-vcenter table-sm card-table">
          <thead><tr><th>Coupure</th><th class="text-end">Quantité</th><th class="text-end">Montant</th></tr></thead>
          <tbody>
            <?php foreach ($denominations as $col => $value): ?>
              <?php $n = (int) $register[$col]; ?>
              <tr>
                <td><?= e(number_format($value, 0, ',', ' ')) ?></td>
                <td class="text-end <?= $n < 0 ? 'text-danger' : '' ?>"><?= (int) $n ?></td>
                <td class="text-end"><?= e(number_format($n * $value, 0, ',', ' ')) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot><tr><th colspan="2">Total des coupures</th><th class="text-end"><?= e(fcfa($totalNotes)) ?></th></tr></tfoot>
        </table>
      </div>
      <div class="card-footer text-secondary">Le solde et les coupures sont mis à jour par les opérations de caisse : ils ne se modifient pas ici.</div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Historique</div></div>
      <div class="list-group list-group-flush">
        <?php foreach ($history as $label => $n): ?>
          <div class="list-group-item d-flex justify-content-between">
            <span><?= e($label) ?></span>
            <span class="badge <?= $n > 0 ? 'bg-blue-lt' : 'bg-secondary-lt' ?>"><?= (int) $n ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($hasHistory): ?>
        <div class="card-footer text-secondary">Cette caisse a déjà des opérations : son unité ne peut plus être changée.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($canDelete): ?>
  <div class="modal modal-sm" id="confirmDelete" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form action="<?= e(url('/caisses/supprimer')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $register['id'] ?>">
          <div class="modal-body">
            <div class="modal-title">Supprimer cette caisse ?</div>
            <div>
              La caisse « <?= e($register['name']) ?> » sera supprimée. L'historique des opérations est conservé.
              <?php if (!$empty): ?>
                <div class="text-danger mt-2">Elle contient encore <?= e(fcfa($register['balance'])) ?> : la suppression sera refusée.</div>
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
