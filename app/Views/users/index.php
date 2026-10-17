<?php
/**
 * @var array $rows @var int $total @var int $page @var int $pages @var string $term
 * @var bool $canCreate @var bool $canInfo @var array $groups @var array $unities @var array $old @var bool $openCreate
 */
use App\Core\Csrf;
use App\Core\View;

$query = static fn(int $p): string => url('/utilisateurs') . '?' . http_build_query(array_filter(['s' => $term, 'page' => $p > 1 ? $p : null]));
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Liste des utilisateurs: <span class="text-primary"><?= (int) $total ?></span></div>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-end">
          <form method="get" action="<?= e(url('/utilisateurs')) ?>">
            <input type="search" name="s" value="<?= e($term) ?>" class="form-control" id="search" placeholder="Rechercher">
          </form>
        </div>
      </div>
      <div class="table-hover table-responsive">
        <table class="table-hover table table-vcenter table-nowrap">
          <thead>
            <tr>
              <th scope="col"></th>
              <th scope="col">Statut du compte</th>
              <th scope="col">Nom</th>
              <th scope="col">Unité</th>
              <th scope="col">Groupe</th>
              <th scope="col">Login</th>
              <th scope="col">Email</th>
              <th scope="col">Téléphone</th>
              <th scope="col">Créé le</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url('/utilisateurs/' . (int) $r['id'])) ?>">Consulter</a><?php endif; ?></td>
                <td>
                  <?php if (!empty($r['suspended'])): ?>
                    <span class="status status-red"><span class="status-dot"></span> SUSPENDU</span>
                  <?php else: ?>
                    <span class="status status-green"><span class="status-dot"></span> ACTIF</span>
                  <?php endif; ?>
                </td>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['unity_name'] ?? '--') ?></td>
                <td><?= e($r['group_name'] ?? '--') ?></td>
                <td><?= e($r['login']) ?></td>
                <td><?php if ($r['email']): ?><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?php endif; ?></td>
                <td><?php if ($r['phone']): ?><a href="tel:<?= e(str_replace(' ', '', $r['phone'])) ?>"><?= e($r['phone']) ?></a><?php endif; ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="10" class="text-center text-secondary">Aucun utilisateur trouvé.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-end align-center gap-1">
        <?php if ($page > 1): ?><a href="<?= e($query($page - 1)) ?>">Précédent</a><?php endif; ?>
        <code><span class="text-primary"><?= (int) $page ?></span> / <?= (int) $pages ?></code>
        <?php if ($page < $pages): ?><a href="<?= e($query($page + 1)) ?>">Suivant</a><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($canCreate): ?>
  <div class="modal" id="create" tabindex="-1" <?= $openCreate ? 'data-auto-open="1"' : '' ?>>
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Création</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <form id="createForm" action="<?= e(url('/utilisateurs')) ?>" method="post">
            <?= Csrf::field() ?>
            <?= View::partial('users/_fields', [
                'u' => $old, 'groups' => $groups, 'unities' => $unities,
                'mode' => 'create', 'p' => 'c_', 'isSelf' => false, 'readonly' => false,
            ]) ?>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn me-auto" data-bs-dismiss="modal">Fermer</button>
          <button form="createForm" type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>
