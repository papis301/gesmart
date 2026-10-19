<?php
/**
 * @var array $rows @var int $total @var float $sumAmount @var float $sumPending @var int $page @var int $pages
 * @var array $filters @var array $unities @var array $registers @var bool $canInfo
 */
use App\Models\DisbursementModel;

$n = static fn($v): string => number_format((float) $v, 0, ',', ' ');
$query = static fn(int $p): string => url('/decaissements') . '?' . http_build_query(array_filter($filters + ['page' => $p > 1 ? $p : null], static fn($v) => $v !== '' && $v !== 0 && $v !== null));
$statusClass = ['pending' => 'status-warning', 'validated' => 'status-success', 'aborted' => 'status-danger'];
?>
<div class="row row-cards">
  <div class="col-md col-lg-4 d-flex">
    <div class="card bg-white flex-fill text-center text-uppercase">
      <div class="card-body"><h5>Nombre de décaissements</h5><div class="fs-3"><?= e($n($total)) ?></div></div>
    </div>
  </div>
  <div class="col-md col-lg-4 d-flex">
    <div class="card bg-white flex-fill text-center text-uppercase">
      <div class="card-body"><h5>Montant décaissé</h5><div class="fs-3"><?= e($n($sumAmount)) ?> <b>FCFA</b></div><div class="text-secondary small">hors décaissements annulés</div></div>
    </div>
  </div>
  <div class="col-md col-lg-4 d-flex">
    <div class="card bg-white flex-fill text-center text-uppercase">
      <div class="card-body"><h5>Dont en attente de validation</h5><div class="fs-3"><?= e($n($sumPending)) ?> <b>FCFA</b></div></div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header"><div class="card-title">Liste des décaissements: <span class="text-primary"><?= e($n($total)) ?></span></div></div>
      <div class="card-body">
        <form method="get" action="<?= e(url('/decaissements')) ?>" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Unité</label>
            <select name="unity_id" class="form-select">
              <option value="">Toutes</option>
              <?php foreach ($unities as $un): ?>
                <option value="<?= (int) $un['id'] ?>" <?= (int) $filters['unity_id'] === (int) $un['id'] ? 'selected' : '' ?>><?= e($un['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Caisse</label>
            <select name="cash_register_id" class="form-select">
              <option value="">Toutes</option>
              <?php foreach ($registers as $r): ?>
                <option value="<?= (int) $r['id'] ?>" <?= (int) $filters['cash_register_id'] === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Source</label>
            <select name="type" class="form-select">
              <option value="">Toutes</option>
              <?php foreach (DisbursementModel::TYPES as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $filters['type'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Statut</label>
            <select name="status" class="form-select">
              <option value="">Tous</option>
              <?php foreach (DisbursementModel::STATUSES as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2"><label class="form-label">Du</label><input type="date" name="from" class="form-control" value="<?= e($filters['from']) ?>"></div>
          <div class="col-md-2"><label class="form-label">Au</label><input type="date" name="to" class="form-control" value="<?= e($filters['to']) ?>"></div>
          <div class="col-md-5"><label class="form-label">Recherche</label><input type="search" name="s" class="form-control" value="<?= e($filters['term']) ?>" placeholder="Référence, montant, description, retireur…"></div>
          <div class="col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a class="btn" href="<?= e(url('/decaissements')) ?>">Effacer</a>
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
              <th scope="col">Caisse</th>
              <th scope="col">Caissier</th>
              <th scope="col">Source</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $st = DisbursementModel::status($r); ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url('/decaissements/' . $r['id'])) ?>">Consulter</a><?php endif; ?></td>
                <td><span class="status <?= e($statusClass[$st]) ?>"><span class="status-dot"></span><?= e(DisbursementModel::STATUSES[$st]) ?></span></td>
                <td><?= e($r['id']) ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td class="text-end"><?= e($n((float) $r['returned'] - (float) $r['received'])) ?></td>
                <td><?= e($r['unity_name'] ?? '--') ?></td>
                <td><?= e($r['register_name'] ?? '--') ?></td>
                <td>
                  <div><?= e($r['user_name'] ?? '--') ?></div>
                  <?php if (!empty($r['user_email'])): ?><div><small><a href="mailto:<?= e($r['user_email']) ?>"><?= e($r['user_email']) ?></a></small></div><?php endif; ?>
                  <?php if (!empty($r['user_phone'])): ?><div><small><a href="tel:<?= e($r['user_phone']) ?>"><?= e($r['user_phone']) ?></a></small></div><?php endif; ?>
                </td>
                <td><?= e(DisbursementModel::typeLabel($r['type'])) ?></td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="10" class="text-center text-secondary">Aucun décaissement trouvé.</td></tr>
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
