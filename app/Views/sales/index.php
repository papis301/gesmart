<?php
/**
 * @var array $rows @var int $total @var int $page @var int $pages @var string $term @var array $filter
 * @var array $unities @var bool $canInfo
 */
use App\Models\SaleModel;

$query = static fn(int $p): string => url('/ventes') . '?' . http_build_query(array_filter([
    's' => $term, 'unity_filter' => $filter, 'page' => $p > 1 ? $p : null,
]));
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Liste des ventes: <span class="text-primary"><?= e($n($total)) ?></span></div>
      </div>
      <div class="card-body">
        <form method="get" action="<?= e(url('/ventes')) ?>" class="d-flex justify-content-between flex-wrap gap-2">
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
                <a class="btn btn-sm" href="<?= e(url('/ventes')) ?>">Effacer</a>
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
              <th scope="col">Statut</th>
              <th scope="col">Référence</th>
              <th scope="col">Date</th>
              <th scope="col">Client</th>
              <th scope="col">À crédit?</th>
              <th scope="col" class="text-end">FAGAROU par caisse</th>
              <th scope="col" class="text-end">Nombre de caisses</th>
              <th scope="col" class="text-end">Prix</th>
              <th scope="col" class="text-end">FAGAROU</th>
              <th scope="col" class="text-end">Montant vente</th>
              <th scope="col" class="text-end">Total</th>
              <th scope="col">Unité de production</th>
              <th scope="col">Caisse</th>
              <th scope="col">Caissier</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $st = SaleModel::status($r); ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url('/ventes/' . rawurlencode((string) $r['id']))) ?>">Consulter</a><?php endif; ?></td>
                <td><span class="status status-<?= e($st['class']) ?>"><span class="status-dot"></span> <?= e($st['label']) ?></span></td>
                <td><?= e($r['id']) ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td>
                  <?php if ($r['client_name']): ?>
                    <div><?= e($r['client_name']) ?></div>
                    <div><small>Nº de compte: <?= e($r['client_id']) ?></small></div>
                    <?php if ($r['client_email']): ?><div><small><a href="mailto:<?= e($r['client_email']) ?>"><?= e($r['client_email']) ?></a></small></div><?php endif; ?>
                    <?php if ($r['client_phone']): ?><div><small><a href="tel:<?= e($r['client_phone']) ?>"><?= e($r['client_phone']) ?></a></small></div><?php endif; ?>
                  <?php else: ?>
                    <div>--</div><div><small>Nº de compte: --</small></div>
                  <?php endif; ?>
                </td>
                <td><?= (int) $r['debt'] === 1 ? 'OUI' : 'NON' ?></td>
                <td class="text-end"><?= e($n($r['savings'])) ?></td>
                <td class="text-end"><?= e($n($r['qty'])) ?></td>
                <td class="text-end"><?= $r['pmin'] === $r['pmax'] ? e($n($r['pmin'])) : e($n($r['pmin']) . ' - ' . $n($r['pmax'])) ?></td>
                <td class="text-end"><?= e($n((float) $r['savings'] * $r['qty'])) ?></td>
                <td class="text-end"><?= e($n($r['amount'])) ?></td>
                <td class="text-end"><?= e($n($r['total'])) ?></td>
                <td><?= e($r['unity_name'] ?? '--') ?></td>
                <td><?= e($r['register_name'] ?? '--') ?></td>
                <td>
                  <?php if ($r['cashier_name']): ?>
                    <div><?= e($r['cashier_name']) ?></div>
                    <?php if ($r['cashier_email']): ?><div><small><a href="mailto:<?= e($r['cashier_email']) ?>"><?= e($r['cashier_email']) ?></a></small></div><?php endif; ?>
                    <?php if ($r['cashier_phone']): ?><div><small><a href="tel:<?= e($r['cashier_phone']) ?>"><?= e($r['cashier_phone']) ?></a></small></div><?php endif; ?>
                  <?php else: ?>--<?php endif; ?>
                </td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="16" class="text-center text-secondary">Aucune vente trouvée.</td></tr>
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
