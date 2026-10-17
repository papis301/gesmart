<?php
/**
 * @var array $product @var string $unityName @var array $unities @var int $salesLines
 * @var bool $canEdit @var bool $canDelete
 */
use App\Core\Csrf;
use App\Core\View;

$fmt = static fn($n): string => number_format((float) $n, 0, ',', ' ');
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Produit <span class="text-primary"><?= e($product['name']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid mb-3">
          <div class="datagrid-item"><div class="datagrid-title">Unité</div><div class="datagrid-content"><?= e($unityName) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Prix unitaire</div><div class="datagrid-content"><?= e($fmt($product['price'])) ?> FCFA</div></div>
          <div class="datagrid-item"><div class="datagrid-title">Stock</div><div class="datagrid-content"><?= e($fmt($product['stock'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Créé le</div><div class="datagrid-content"><?= e(dt($product['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifié le</div><div class="datagrid-content"><?= e(dt($product['updated_at']) ?: '--') ?></div></div>
        </div>

        <form id="editForm" action="<?= e(url('/produits/modifier')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
          <?= View::partial('products/_fields', ['u' => $product, 'unities' => $unities, 'p' => 'e_', 'readonly' => !$canEdit]) ?>
        </form>
      </div>
      <div class="card-footer d-flex gap-2">
        <a href="<?= e(url('/produits')) ?>" class="btn me-auto">Retour</a>
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
      <div class="card-header"><div class="card-title">Historique</div></div>
      <div class="list-group list-group-flush">
        <div class="list-group-item d-flex justify-content-between">
          <span>Lignes de vente utilisant ce produit</span>
          <span class="badge <?= $salesLines > 0 ? 'bg-blue-lt' : 'bg-secondary-lt' ?>"><?= (int) $salesLines ?></span>
        </div>
      </div>
      <?php if ($salesLines > 0): ?>
        <div class="card-footer text-secondary">
          Ce produit a déjà été vendu : son unité ne peut plus être changée. Les prix des ventes passées ne sont pas modifiés par un changement de prix.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($canDelete): ?>
  <div class="modal modal-sm" id="confirmDelete" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form action="<?= e(url('/produits/supprimer')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
          <div class="modal-body">
            <div class="modal-title">Supprimer ce produit ?</div>
            <div>Le produit « <?= e($product['name']) ?> » ne sera plus proposé. L'historique des ventes est conservé.</div>
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
