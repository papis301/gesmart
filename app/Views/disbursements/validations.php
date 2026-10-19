<?php
/** @var array $rows @var int $total @var int $page @var int $pages @var string $term @var bool $canInfo */
use App\Models\DisbursementModel as M;

$base = '/validations/decaissements';
$query = static fn(int $p): string => url($base) . '?' . http_build_query(array_filter(['s' => $term, 'page' => $p > 1 ? $p : null]));
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Liste des décaissements en attente de validation: <span class="text-primary"><?= e($n($total)) ?></span></div>
      </div>
      <div class="card-body">
        <form method="get" action="<?= e(url($base)) ?>" class="d-flex justify-content-end">
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
              <th scope="col" class="text-end">Montant FCFA</th>
              <th scope="col">Unité de production</th>
              <th scope="col">Caisse</th>
              <th scope="col">Caissier</th>
              <th scope="col">Source</th>
              <th scope="col">Créé le</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $st = M::status($r); ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url('/decaissements/' . rawurlencode((string) $r['id']))) ?>">Consulter</a><?php endif; ?></td>
                <td><span class="status status-<?= e($st['class']) ?>"><span class="status-dot"></span> <?= e($st['label']) ?></span></td>
                <td><?= e($r['id']) ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td class="text-end"><?= e($n(M::amount($r))) ?></td>
                <td><?= e($r['unity_name'] ?? '--') ?></td>
                <td><?= e($r['register_name'] ?? '--') ?></td>
                <td>
                  <?php if ($r['cashier_name']): ?>
                    <div><?= e($r['cashier_name']) ?></div>
                    <?php if ($r['cashier_email']): ?><div><small><a href="mailto:<?= e($r['cashier_email']) ?>"><?= e($r['cashier_email']) ?></a></small></div><?php endif; ?>
                    <?php if ($r['cashier_phone']): ?><div><small><a href="tel:<?= e($r['cashier_phone']) ?>"><?= e($r['cashier_phone']) ?></a></small></div><?php endif; ?>
                  <?php else: ?>--<?php endif; ?>
                </td>
                <td>
                  <div><?= e(M::type($r['type'])) ?></div>
                  <?php if ($r['client_name']): ?><div><small>Client: <?= e($r['client_name']) ?> (Nº <?= e($r['client_id']) ?>)</small></div><?php endif; ?>
                </td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="11" class="text-center text-secondary">Aucun décaissement en attente de validation.</td></tr>
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
