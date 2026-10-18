<?php
/**
 * @var array $rows @var int $total @var float $sumBalance @var float $sumCredit @var int $page @var int $pages
 * @var string $term @var array $filter @var string $type @var array $unities
 * @var bool $canCreate @var bool $canInfo @var array $old @var bool $openCreate
 */
use App\Core\Csrf;
use App\Core\View;

$query = static fn(int $p): string => url('/clients') . '?' . http_build_query(array_filter([
    's' => $term, 'unity_filter' => $filter, 'type_filter' => $type, 'page' => $p > 1 ? $p : null,
]));
$n = static fn($v): string => number_format((float) $v, 0, ',', ' ');
?>
<div class="row row-cards">
  <div class="col-md col-lg-4 col-xl-3 d-flex">
    <div class="card bg-white flex-fill text-center text-uppercase">
      <div class="card-body"><h5>Nombre de clients</h5><div class="fs-3"><?= e($n($total)) ?></div></div>
    </div>
  </div>
  <div class="col-md col-lg-4 col-xl-3 d-flex">
    <div class="card bg-white flex-fill text-center text-uppercase">
      <div class="card-body"><h5>Total FAGAROU/Remboursements</h5><div class="fs-3"><?= e($n($sumBalance)) ?> <b>FCFA</b></div></div>
    </div>
  </div>
  <div class="col-md col-lg-4 col-xl-3 d-flex">
    <div class="card bg-white flex-fill text-center text-uppercase">
      <div class="card-body"><h5>Total crédit</h5><div class="fs-3"><?= e($n($sumCredit)) ?> <b>FCFA</b></div></div>
    </div>
  </div>
  <div class="col-md col-lg-4 col-xl-3 d-flex">
    <div class="card bg-white flex-fill text-center text-uppercase">
      <div class="card-body"><h5>Solde</h5><div class="fs-3"><?= e($n($sumBalance + $sumCredit)) ?> <b>FCFA</b></div></div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Liste des clients: <span class="text-primary"><?= e($n($total)) ?></span></div>
      </div>
      <div class="card-body">
        <form method="get" action="<?= e(url('/clients')) ?>" class="d-flex justify-content-between flex-wrap gap-2">
          <div class="d-flex gap-2 align-items-center flex-wrap">
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
                <button type="submit" class="btn btn-primary btn-sm mt-2">Appliquer</button>
              </div>
            </div>
            <select name="type_filter" class="form-select w-auto" onchange="this.form.submit()">
              <option value="">Toutes les positions</option>
              <option value="debit" <?= $type === 'debit' ? 'selected' : '' ?>>Débiteurs (doivent de l'argent)</option>
              <option value="credit" <?= $type === 'credit' ? 'selected' : '' ?>>Créditeurs (ont de l'argent)</option>
            </select>
            <a class="link text-sm" href="<?= e(url('/clients')) ?>">Effacer</a>
          </div>
          <input type="search" name="s" value="<?= e($term) ?>" class="form-control w-auto" id="search" placeholder="Rechercher">
        </form>
      </div>
      <div class="table-hover table-responsive">
        <table class="table-hover table table-vcenter table-nowrap">
          <thead>
            <tr>
              <th scope="col"></th>
              <th scope="col">Statut</th>
              <th scope="col">Nº de compte</th>
              <th scope="col">Nom</th>
              <th scope="col">Unité</th>
              <th scope="col" class="text-end">Crédit en FCFA</th>
              <th scope="col" class="text-end">FAGAROU/Remboursement en FCFA</th>
              <th scope="col" class="text-end">Solde du compte en FCFA</th>
              <th scope="col">Email</th>
              <th scope="col">Téléphone</th>
              <th scope="col">Créé le</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $solde = (float) $r['credit'] + (float) $r['balance']; ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url('/clients/' . $r['id'])) ?>">Consulter</a><?php endif; ?></td>
                <td>
                  <?php if ((int) $r['validated'] === 1): ?>
                    <span class="status status-success"><span class="status-dot"></span><span>Validé</span></span>
                  <?php else: ?>
                    <span class="status status-warning"><span class="status-dot"></span><span>En attente</span></span>
                  <?php endif; ?>
                </td>
                <td><?= e($r['id']) ?></td>
                <td><?= e($r['name']) ?></td>
                <td><?= e($r['unity_name'] ?? '--') ?></td>
                <td class="text-end"><?= e($n($r['credit'])) ?></td>
                <td class="text-end"><?= e($n($r['balance'])) ?></td>
                <td class="text-end <?= $solde < -0.5 ? 'text-danger' : '' ?>"><?= e($n($solde)) ?></td>
                <td><?php if (!empty($r['email'])): ?><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?php endif; ?></td>
                <td><?php if (!empty($r['phone'])): ?><a href="tel:<?= e($r['phone']) ?>"><?= e($r['phone']) ?></a><?php endif; ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="12" class="text-center text-secondary">Aucun client trouvé.</td></tr>
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
          <form id="createForm" action="<?= e(url('/clients')) ?>" method="post">
            <?= Csrf::field() ?>
            <?= View::partial('clients/_fields', ['u' => $old, 'unities' => $unities, 'mode' => 'create', 'p' => 'c_', 'readonly' => false]) ?>
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
