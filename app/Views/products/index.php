<?php
/**
 * @var array $rows @var int $total @var int $page @var int $pages @var string $term @var array $filter
 * @var array $unities @var bool $canCreate @var bool $canInfo @var array $old @var bool $openCreate
 */
use App\Core\Csrf;
use App\Core\View;

$query = static fn(int $p): string => url('/produits') . '?' . http_build_query(array_filter([
    's' => $term, 'unity_filter' => $filter, 'page' => $p > 1 ? $p : null,
]));
$fmt = static fn($n): string => number_format((float) $n, 0, ',', ' ');
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Liste des produits: <span class="text-primary"><?= (int) $total ?></span></div>
      </div>
      <div class="card-body">
        <form method="get" action="<?= e(url('/produits')) ?>" class="d-flex justify-content-between flex-wrap gap-2">
          <div class="dropdown">
            <button type="button" class="btn dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
              Filtrer par unité<?= $filter ? ' (' . count($filter) . ')' : '' ?>
            </button>
            <div class="dropdown-menu p-3" style="min-width: 280px;">
              <?php foreach ($unities as $un): ?>
                <label class="form-check mb-1">
                  <input class="form-check-input" type="checkbox" name="unity_filter[]" value="<?= (int) $un['id'] ?>"
                         <?= in_array((int) $un['id'], $filter, true) ? 'checked' : '' ?>>
                  <span class="form-check-label"><?= e($un['name']) ?></span>
                </label>
              <?php endforeach; ?>
              <div class="d-flex gap-2 mt-2">
                <button type="submit" class="btn btn-primary btn-sm">Appliquer</button>
                <a class="btn btn-sm" href="<?= e(url('/produits')) ?>">Effacer</a>
              </div>
            </div>
          </div>
          <input type="search" name="s" value="<?= e($term) ?>" class="form-control w-auto" id="search" placeholder="Rechercher">
        </form>
      </div>
      <div class="table-hover table-responsive">
        <table class="table-hover table table-vcenter table-nowrap">
          <thead>
            <tr>
              <th scope="col"></th>
              <th scope="col">Nom</th>
              <th scope="col">Unité de production</th>
              <th scope="col" class="text-end">Prix unitaire en FCFA</th>
              <th scope="col" class="text-end">Stock</th>
              <th scope="col">Créé le</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url('/produits/' . (int) $r['id'])) ?>">Consulter</a><?php endif; ?></td>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['unity_name'] ?? '--') ?></td>
                <td class="text-end"><?= e($fmt($r['price'])) ?></td>
                <td class="text-end"><?= e($fmt($r['stock'])) ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="7" class="text-center text-secondary">Aucun produit trouvé.</td></tr>
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
          <form id="createForm" action="<?= e(url('/produits')) ?>" method="post">
            <?= Csrf::field() ?>
            <?= View::partial('products/_fields', ['u' => $old, 'unities' => $unities, 'p' => 'c_', 'readonly' => false]) ?>
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
