<?php
/** @var array $unity @var array $usage @var bool $inUse @var bool $canEdit @var bool $canDelete */
use App\Core\Csrf;
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Unité de production <span class="text-primary"><?= e($unity['name']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid mb-3">
          <div class="datagrid-item"><div class="datagrid-title">Créé le</div><div class="datagrid-content"><?= e(dt($unity['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($unity['updated_at']) ?: '--') ?></div></div>
        </div>

        <form id="editForm" action="<?= e(url('/unites/modifier')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $unity['id'] ?>">
          <div class="mb-3">
            <label for="name" class="form-label">Nom</label>
            <input type="text" class="form-control" name="name" id="name" maxlength="255"
                   value="<?= e($unity['name']) ?>" <?= $canEdit ? 'required' : 'readonly' ?>>
          </div>
        </form>
      </div>
      <div class="card-footer d-flex gap-2">
        <a href="<?= e(url('/unites')) ?>" class="btn me-auto">Retour</a>
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
    <div class="card">
      <div class="card-header"><div class="card-title">Éléments rattachés</div></div>
      <div class="list-group list-group-flush">
        <?php foreach ($usage as $label => $n): ?>
          <div class="list-group-item d-flex justify-content-between">
            <span><?= e($label) ?></span>
            <span class="badge <?= $n > 0 ? 'bg-blue-lt' : 'bg-secondary-lt' ?>"><?= (int) $n ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($inUse): ?>
        <div class="card-footer text-secondary">Une unité ne peut être supprimée que lorsqu'elle n'a plus aucun élément rattaché.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($canDelete): ?>
  <div class="modal modal-sm" id="confirmDelete" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form action="<?= e(url('/unites/supprimer')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $unity['id'] ?>">
          <div class="modal-body">
            <div class="modal-title">Supprimer cette unité ?</div>
            <div>L'unité « <?= e($unity['name']) ?> » sera supprimée. L'historique (ventes, encaissements...) est conservé.</div>
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
