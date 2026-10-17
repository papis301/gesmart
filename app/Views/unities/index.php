<?php
/**
 * @var array $rows @var int $total @var int $page @var int $pages @var string $term
 * @var bool $canCreate @var bool $canInfo @var array $old @var bool $openCreate
 */
use App\Core\Csrf;

$query = static fn(int $p): string => url('/unites') . '?' . http_build_query(array_filter(['s' => $term, 'page' => $p > 1 ? $p : null]));
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Liste des unités de production: <span class="text-primary"><?= (int) $total ?></span></div>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-end">
          <form method="get" action="<?= e(url('/unites')) ?>">
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
              <th scope="col">Créé le</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url('/unites/' . (int) $r['id'])) ?>">Consulter</a><?php endif; ?></td>
                <td><?= e($r['name']) ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="4" class="text-center text-secondary">Aucune unité de production trouvée.</td></tr>
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
          <form id="createForm" action="<?= e(url('/unites')) ?>" method="post">
            <?= Csrf::field() ?>
            <div class="mb-3">
              <label for="name" class="form-label">Nom</label>
              <input type="text" class="form-control" name="name" id="name" maxlength="255" required
                     placeholder="Nom de l'unité" value="<?= e($old['name'] ?? '') ?>">
            </div>
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
