<?php
/**
 * @var array $rows @var int $total @var float $sumAmount @var int $page @var int $pages @var array $filters
 * @var array $unities @var bool $pendingOnly @var string $baseUrl @var bool $canInfo
 */
use App\Models\InterUnitTransferModel;

$n = static fn($v): string => number_format((float) $v, 0, ',', ' ');
$query = static fn(int $p): string => url($baseUrl) . '?' . http_build_query(array_filter($filters + ['page' => $p > 1 ? $p : null], static fn($v) => $v !== '' && $v !== 0 && $v !== null && $v !== 'pending'));
$statusClass = ['pending' => 'status-warning', 'validated' => 'status-success', 'aborted' => 'status-danger'];
$person = static function (?string $name, ?string $email, ?string $phone): string {
    if ($name === null || $name === '') {
        return '--';
    }
    $html = '<div>' . e($name) . '</div>';
    if (!empty($email)) {
        $html .= '<div><small><a href="mailto:' . e($email) . '">' . e($email) . '</a></small></div>';
    }
    if (!empty($phone)) {
        $html .= '<div><small><a href="tel:' . e($phone) . '">' . e($phone) . '</a></small></div>';
    }
    return $html;
};
?>
<div class="row row-cards">
  <div class="col-md col-lg-6 d-flex">
    <div class="card bg-white flex-fill text-center text-uppercase">
      <div class="card-body"><h5><?= $pendingOnly ? 'Transferts en attente' : 'Nombre de transferts' ?></h5><div class="fs-3"><?= e($n($total)) ?></div></div>
    </div>
  </div>
  <div class="col-md col-lg-6 d-flex">
    <div class="card bg-white flex-fill text-center text-uppercase">
      <div class="card-body"><h5>Montant transféré</h5><div class="fs-3"><?= e($n($sumAmount)) ?> <b>FCFA</b></div><?php if (!$pendingOnly): ?><div class="text-secondary small">hors transferts annulés</div><?php endif; ?></div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">
          <?= $pendingOnly ? 'Liste des transferts hors place en attente de validation' : 'Liste des transferts hors place' ?>:
          <span class="text-primary"><?= e($n($total)) ?></span>
        </div>
      </div>
      <div class="card-body">
        <form method="get" action="<?= e(url($baseUrl)) ?>" class="row g-2 align-items-end">
          <?php if (!$pendingOnly): ?>
            <div class="col-md-3">
              <label class="form-label">Unité (caisse émettrice)</label>
              <select name="unity_id" class="form-select">
                <option value="">Toutes</option>
                <?php foreach ($unities as $un): ?>
                  <option value="<?= (int) $un['id'] ?>" <?= (int) $filters['unity_id'] === (int) $un['id'] ? 'selected' : '' ?>><?= e($un['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Statut</label>
              <select name="status" class="form-select">
                <option value="">Tous</option>
                <?php foreach (InterUnitTransferModel::STATUSES as $k => $label): ?>
                  <option value="<?= e($k) ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          <?php endif; ?>
          <div class="col-md-2"><label class="form-label">Du</label><input type="date" name="from_date" class="form-control" value="<?= e($filters['from_date']) ?>"></div>
          <div class="col-md-2"><label class="form-label">Au</label><input type="date" name="to_date" class="form-control" value="<?= e($filters['to_date']) ?>"></div>
          <div class="col-md-4"><label class="form-label">Recherche</label><input type="search" name="s" class="form-control" value="<?= e($filters['term']) ?>" placeholder="Référence, montant, caisse, personne…"></div>
          <div class="col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a class="btn" href="<?= e(url($baseUrl)) ?>">Effacer</a>
          </div>
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
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $st = InterUnitTransferModel::status($r); ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url('/transferts/hors_places/' . $r['id'])) ?>">Consulter</a><?php endif; ?></td>
                <td><span class="status <?= e($statusClass[$st]) ?>"><span class="status-dot"></span><?= e(InterUnitTransferModel::STATUSES[$st]) ?></span></td>
                <td><?= e($r['id']) ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td class="text-end"><?= e($n($r['total'])) ?></td>
                <td><?= e($r['unity_name'] ?? '--') ?></td>
                <td>
                  <?php if (!empty($r['from_name'])): ?><div class="text-secondary small"><?= e($r['from_name']) ?></div><?php endif; ?>
                  <?= $person($r['user_name'], $r['user_email'], $r['user_phone']) ?>
                </td>
                <td>
                  <?php if (!empty($r['to_name'])): ?><div class="text-secondary small"><?= e($r['to_name']) ?></div><?php endif; ?>
                  <?= $person($r['to_user_name'], $r['to_user_email'], $r['to_user_phone']) ?>
                </td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="9" class="text-center text-secondary"><?= $pendingOnly ? 'Aucun transfert en attente de validation.' : 'Aucun transfert trouvé.' ?></td></tr>
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
