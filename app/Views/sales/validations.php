<?php
/** @var array $rows @var int $total @var int $page @var int $pages @var string $term @var bool $canInfo */
use App\Models\SaleModel;

$base = '/validations/ventes';
$query = static fn(int $p): string => url($base) . '?' . http_build_query(array_filter(['s' => $term, 'page' => $p > 1 ? $p : null]));
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
$contact = static function (?string $name, ?string $email, ?string $phone, ?string $account = null): string {
    if ($name === null || $name === '') {
        return '--';
    }
    $h = '<div>' . e($name) . '</div>';
    if ($account !== null) {
        $h .= '<div><small>Nº de compte: ' . e($account) . '</small></div>';
    }
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
        <div class="card-title">Liste des ventes en attente de validation: <span class="text-primary"><?= e($n($total)) ?></span></div>
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
              <th scope="col" class="text-end">Montant HT en FCFA</th>
              <th scope="col" class="text-end">Montant TTC en FCFA</th>
              <th scope="col">Unité de production</th>
              <th scope="col">Caisse</th>
              <th scope="col">Caissier</th>
              <th scope="col">Client</th>
              <th scope="col">À crédit?</th>
              <th scope="col">Créé le</th>
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
                <td class="text-end"><?= e($n($r['total'])) ?></td>
                <td class="text-end"><?= e($n((float) $r['total'] * (1 + (float) $r['taxes'] / 100))) ?></td>
                <td><?= e($r['unity_name'] ?? '--') ?></td>
                <td><?= e($r['register_name'] ?? '--') ?></td>
                <td><?= $contact($r['cashier_name'], $r['cashier_email'], $r['cashier_phone']) ?></td>
                <td><?= $contact($r['client_name'], $r['client_email'], $r['client_phone'], (string) $r['client_id']) ?></td>
                <td><?= (int) $r['debt'] === 1 ? 'OUI' : 'NON' ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="13" class="text-center text-secondary">Aucune vente en attente de validation.</td></tr>
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
