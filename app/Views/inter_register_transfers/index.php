<?php
/** @var array $rows @var int $total @var int $page @var int $pages @var string $term @var bool $canInfo */
use App\Models\InterRegisterTransferModel as M;

$base = '/transferts/inter_caisses';
$query = static fn(int $p): string => url($base) . '?' . http_build_query(array_filter(['s' => $term, 'page' => $p > 1 ? $p : null]));
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
$party = static function (?string $register, ?string $cashier): string {
    if ($register === null && $cashier === null) {
        return '--';
    }
    return '<div>Caisse: ' . e($register ?? '--') . '</div><div><small>Caissier: ' . e($cashier ?? '--') . '</small></div>';
};
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Liste des transferts: <span class="text-primary"><?= e($n($total)) ?></span></div>
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
              <th scope="col">Donneur d'ordre</th>
              <th scope="col">Bénéficiaire</th>
              <th scope="col">Créé le</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $st = M::status($r); ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url($base . '/' . rawurlencode((string) $r['id']))) ?>">Consulter</a><?php endif; ?></td>
                <td><span class="status status-<?= e($st['class']) ?>"><span class="status-dot"></span> <?= e($st['label']) ?></span></td>
                <td><?= e($r['id']) ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td class="text-end"><?= e($n($r['total'])) ?></td>
                <td><?= e($r['unity_name'] ?? '--') ?></td>
                <td><?= $party($r['from_register'], $r['from_cashier']) ?></td>
                <td><?= $party($r['to_register'], $r['to_cashier']) ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="10" class="text-center text-secondary">Aucun transfert trouvé.</td></tr>
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
