<?php
/**
 * @var array $group @var array $resources @var array $checked @var array $grantable
 * @var int $usersCount @var bool $canEdit @var bool $canDelete
 */
use App\Core\Csrf;
use App\Core\View;
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Groupe <span class="text-primary"><?= e($group['name']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid mb-3">
          <div class="datagrid-item"><div class="datagrid-title">Droits accordés</div><div class="datagrid-content"><?= count($checked) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Utilisateurs</div><div class="datagrid-content"><?= (int) $usersCount ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Créé le</div><div class="datagrid-content"><?= e(dt($group['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($group['updated_at']) ?: '--') ?></div></div>
        </div>

        <form id="editForm" action="<?= e(url('/groupes/modifier')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
          <div class="mb-3">
            <label for="name" class="form-label">Nom</label>
            <input type="text" class="form-control" name="name" id="name" maxlength="100"
                   value="<?= e($group['name']) ?>" <?= $canEdit ? 'required' : 'readonly' ?>>
          </div>
          <p>Droits :</p>
          <?php if ($canEdit): ?>
            <p class="text-secondary"><small>Les cases grisées sont des droits que vous ne possédez pas vous-même : vous ne pouvez ni les accorder ni les retirer.</small></p>
          <?php endif; ?>
          <?= View::partial('groups/_rights', [
              'resources' => $resources,
              'checked'   => $checked,
              'grantable' => $grantable,
              'idPrefix'  => 'e',
              'readonly'  => !$canEdit,
          ]) ?>
        </form>
      </div>
      <div class="card-footer d-flex gap-2">
        <a href="<?= e(url('/groupes')) ?>" class="btn me-auto">Retour</a>
        <?php if ($canDelete): ?>
          <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmDelete">Supprimer</button>
        <?php endif; ?>
        <?php if ($canEdit): ?>
          <button form="editForm" type="submit" class="btn btn-primary">Enregistrer</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($canDelete): ?>
  <div class="modal modal-sm" id="confirmDelete" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form action="<?= e(url('/groupes/supprimer')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $group['id'] ?>">
          <div class="modal-body">
            <div class="modal-title">Supprimer ce groupe ?</div>
            <div>Le groupe « <?= e($group['name']) ?> » sera supprimé. Cette action n'est possible que s'il n'a plus aucun utilisateur.</div>
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
