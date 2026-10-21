<?php
/** @var array $rows @var int $total @var int $page @var int $pages @var string $term @var bool $canInfo */
use App\Models\ClosureModel as M;

$base = '/arretes-de-caisses';
$query = static fn(int $p): string => url($base) . '?' . http_build_query(array_filter(['s' => $term, 'page' => $p > 1 ? $p : null]));
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
$person = static function (?string $name, ?string $email, ?string $phone): string {
    if ($name === null) {
        return '--';
    }
    $h = '<div>' . e($name) . '</div>';
    if ($email) {
        $h .= '<div><small><a href="mailto:' . e($email) . '">' . e($email) . '</a></small></div>';
    }
    if ($phone) {
        $h .= '<div><small><a href="tel:' . e($phone) . '">' . e($phone) . '</a></small></div>';
    }
    return $h;
};
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Liste des arrêtés: <span class="text-primary"><?= e($n($total)) ?></span></div>
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
              <th scope="col">Référence</th>
              <th scope="col">Unité de production</th>
              <th scope="col">Caisse</th>
              <th scope="col">Caissier</th>
              <th scope="col" class="text-end">Solde informatique</th>
              <th scope="col" class="text-end">Encaisse physique</th>
              <th scope="col" class="text-end">Écart encaisse</th>
              <th scope="col">Auteur</th>
              <th scope="col">Créé le</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $gap = M::gap($r); ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url($base . '/' . rawurlencode((string) $r['id']))) ?>">Consulter</a><?php endif; ?></td>
                <td><?= e($r['id']) ?></td>
                <td><?= e($r['unity_name'] ?? '--') ?></td>
                <td><?= e($r['register_name'] ?? '--') ?></td>
                <td><?= $person($r['cashier_name'], $r['cashier_email'], $r['cashier_phone']) ?></td>
                <td class="text-end"><?= e($n($r['total'])) ?></td>
                <td class="text-end"><?= e($n($r['total_real'])) ?></td>
                <td class="text-end <?= $gap < 0 ? 'text-danger' : ($gap > 0 ? 'text-success' : '') ?>"><?= e($n($gap)) ?></td>
                <td><?= $person($r['author_name'], $r['author_email'], $r['author_phone']) ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="11" class="text-center text-secondary">Aucun arrêté trouvé.</td></tr>
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
