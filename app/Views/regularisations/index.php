<?php
/** @var array $rows @var int $total @var int $page @var int $pages @var string $term @var bool $canInfo */
use App\Models\RegularisationModel as M;

$base = '/regularisations';
$query = static fn(int $p): string => url($base) . '?' . http_build_query(array_filter(['s' => $term, 'page' => $p > 1 ? $p : null]));
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
$d = static function (?string $v): string {
    $v = M::date($v);
    return $v === null ? '--' : date('d/m/Y', strtotime($v));
};
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Liste: <span class="text-primary"><?= e($n($total)) ?></span></div>
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
              <th scope="col">État du crédit</th>
              <th scope="col">Client</th>
              <th scope="col" class="text-end">Solde du compte</th>
              <th scope="col" class="text-end">Plafond crédit accordé</th>
              <th scope="col">Date de début</th>
              <th scope="col">Date d'échéance</th>
              <th scope="col">Auteur</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $st = M::status($r); $cs = M::creditState($r); ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url($base . '/' . (int) $r['id'])) ?>">Consulter</a><?php endif; ?></td>
                <td><span class="status status-<?= e($st['class']) ?>"><span class="status-dot"></span> <?= e($st['label']) ?></span></td>
                <td><span class="status status-<?= e($cs['class']) ?>"><span class="status-dot"></span> <?= e($cs['label']) ?></span></td>
                <td>
                  <div><?= e($r['client_name'] ?? '--') ?></div>
                  <div><small>Nº de compte: <?= e($r['client_id']) ?></small></div>
                  <?php if ($r['client_email']): ?><div><small><a href="mailto:<?= e($r['client_email']) ?>"><?= e($r['client_email']) ?></a></small></div><?php endif; ?>
                  <?php if ($r['client_phone']): ?><div><small><a href="tel:<?= e($r['client_phone']) ?>"><?= e($r['client_phone']) ?></a></small></div><?php endif; ?>
                </td>
                <td class="text-end"><?= e($n($r['account_balance'])) ?></td>
                <td class="text-end"><?= e($n($r['granted_credit'])) ?></td>
                <td><?= e($d($r['created_at'])) ?></td>
                <td><?= e($d($r['due_date'])) ?></td>
                <td>
                  <?php if ($r['author_name']): ?>
                    <div><?= e($r['author_name']) ?></div>
                    <?php if ($r['author_email']): ?><div><small><a href="mailto:<?= e($r['author_email']) ?>"><?= e($r['author_email']) ?></a></small></div><?php endif; ?>
                    <?php if ($r['author_phone']): ?><div><small><a href="tel:<?= e($r['author_phone']) ?>"><?= e($r['author_phone']) ?></a></small></div><?php endif; ?>
                  <?php else: ?>--<?php endif; ?>
                </td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="10" class="text-center text-secondary">Aucune régularisation trouvée.</td></tr>
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
