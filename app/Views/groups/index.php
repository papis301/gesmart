<?php
/**
 * @var array $rows @var int $total @var int $page @var int $pages @var string $term
 * @var bool $canCreate @var bool $canInfo @var array $resources @var array $grantable
 */
use App\Core\Csrf;
use App\Core\View;

$query = static fn(int $p): string => url('/groupes') . '?' . http_build_query(array_filter(['s' => $term, 'page' => $p > 1 ? $p : null]));
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Liste des groupes: <span class="text-primary"><?= (int) $total ?></span></div>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-end">
          <form method="get" action="<?= e(url('/groupes')) ?>">
            <input type="search" name="s" value="<?= e($term) ?>" class="form-control" id="search" placeholder="Rechercher">
          </form>
        </div>
      </div>
      <div class="table-hover table-responsive">
        <table class="table-hover table table-vcenter table-nowrap">
          <thead>
            <tr>
              <th scope="col"></th>
              <th scope="col">Nom</th>
              <th scope="col">Droits</th>
              <th scope="col">Créé le</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $g): ?>
              <tr>
                <td scope="row">
                  <?php if ($canInfo): ?><a href="<?= e(url('/groupes/' . (int) $g['id'])) ?>">Consulter</a><?php endif; ?>
                </td>
                <td><?= e($g['name']) ?></td>
                <td><?= (int) $g['rules_count'] ?></td>
                <td><?= e(dt($g['created_at']) ?: '--') ?></td>
                <td><?= e(dt($g['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="5" class="text-center text-secondary">Aucun groupe trouvé.</td></tr>
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
  <div class="modal modal-md" id="create" tabindex="-1">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Création</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <form id="createForm" action="<?= e(url('/groupes')) ?>" method="post">
            <?= Csrf::field() ?>
            <div class="mb-3">
              <label for="name" class="form-label">Nom</label>
              <input type="text" class="form-control" name="name" id="name" placeholder="Nom du groupe" maxlength="100" required>
            </div>
            <p>Définissez les droits:</p>
            <?= View::partial('groups/_rights', [
                'resources' => $resources,
                'checked'   => [],
                'grantable' => array_fill_keys($grantable, true),
                'idPrefix'  => 'c',
                'readonly'  => false,
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
