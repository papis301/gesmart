<?php
/**
 * @var array $user @var string $groupName @var string $unityName @var array $groups @var array $unities
 * @var bool $isSelf @var bool $canEdit @var bool $canDelete @var array $logs
 */
use App\Core\Csrf;
use App\Core\View;
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Utilisateur <span class="text-primary"><?= e($user['login']) ?></span></div>
        <div class="card-actions">
          <?php if (!empty($user['suspended'])): ?>
            <span class="status status-red"><span class="status-dot"></span> SUSPENDU</span>
          <?php else: ?>
            <span class="status status-green"><span class="status-dot"></span> ACTIF</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body">
        <div class="datagrid mb-3">
          <div class="datagrid-item"><div class="datagrid-title">Groupe</div><div class="datagrid-content"><?= e($groupName) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Unité</div><div class="datagrid-content"><?= e($unityName) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Dernière connexion</div><div class="datagrid-content"><?= e(dt($user['last_login_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Créé le</div><div class="datagrid-content"><?= e(dt($user['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($user['updated_at']) ?: '--') ?></div></div>
        </div>

        <form id="editForm" action="<?= e(url('/utilisateurs/modifier')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
          <?= View::partial('users/_fields', [
              'u' => $user, 'groups' => $groups, 'unities' => $unities,
              'mode' => 'edit', 'p' => 'e_', 'isSelf' => $isSelf, 'readonly' => !$canEdit,
          ]) ?>
        </form>
      </div>
      <div class="card-footer d-flex gap-2">
        <a href="<?= e(url('/utilisateurs')) ?>" class="btn me-auto">Retour</a>
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
      <div class="card-header"><div class="card-title">Dernières activités</div></div>
      <div class="table-responsive">
        <table class="table table-vcenter card-table">
          <thead><tr><th>Date</th><th>Action</th><th>Adresse IP</th></tr></thead>
          <tbody>
            <?php foreach ($logs as $l): ?>
              <tr>
                <td class="text-nowrap"><?= e(dt($l['created_at'])) ?></td>
                <td><?= e($l['desc']) ?></td>
                <td class="text-secondary"><?= e($l['ip']) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?><tr><td colspan="3" class="text-center text-secondary">Aucune activité enregistrée.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php if ($canDelete): ?>
  <div class="modal modal-sm" id="confirmDelete" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form action="<?= e(url('/utilisateurs/supprimer')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
          <div class="modal-body">
            <div class="modal-title">Supprimer cet utilisateur ?</div>
            <div>Le compte « <?= e($user['name']) ?> » ne pourra plus se connecter. Son historique est conservé.</div>
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
