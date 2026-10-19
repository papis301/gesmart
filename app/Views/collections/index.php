<?php
/**
 * @var array $rows @var int $total @var float $sumAmount @var int $page @var int $pages
 * @var array $filters @var array $unities @var array $registers @var bool $canInfo
 */
use App\Models\CollectionModel;

$n = static fn($v): string => number_format((float) $v, 0, ',', ' ');
$query = static fn(int $p): string => url('/encaissements') . '?' . http_build_query(array_filter($filters + ['page' => $p > 1 ? $p : null], static fn($v) => $v !== '' && $v !== 0 && $v !== null));
?>
<div class="row row-cards">
  <div class="col-md col-lg-6 d-flex">
    <div class="card bg-white flex-fill text-center text-uppercase">
      <div class="card-body"><h5>Nombre d'encaissements</h5><div class="fs-3"><?= e($n($total)) ?></div></div>
    </div>
  </div>
  <div class="col-md col-lg-6 d-flex">
    <div class="card bg-white flex-fill text-center text-uppercase">
      <div class="card-body"><h5>Montant encaissé</h5><div class="fs-3"><?= e($n($sumAmount)) ?> <b>FCFA</b></div><div class="text-secondary small">hors encaissements annulés</div></div>
    </div>
  </div>

  <div class="col-12">
    <div class="card">
      <div class="card-header"><div class="card-title">Liste des encaissements: <span class="text-primary"><?= e($n($total)) ?></span></div></div>
      <div class="card-body">
        <form method="get" action="<?= e(url('/encaissements')) ?>" class="row g-2 align-items-end">
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
            <label class="form-label">Nature</label>
            <select name="type" class="form-select">
              <option value="">Toutes</option>
              <?php foreach (CollectionModel::TYPES as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $filters['type'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Statut</label>
            <select name="status" class="form-select">
              <option value="">Tous</option>
              <?php foreach (CollectionModel::STATUSES as $k => $label): ?>
                <option value="<?= e($k) ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2"><label class="form-label">Du</label><input type="date" name="from" class="form-control" value="<?= e($filters['from']) ?>"></div>
          <div class="col-md-2"><label class="form-label">Au</label><input type="date" name="to" class="form-control" value="<?= e($filters['to']) ?>"></div>
          <div class="col-md-5"><label class="form-label">Recherche</label><input type="search" name="s" class="form-control" value="<?= e($filters['term']) ?>" placeholder="Référence, montant, description, client…"></div>
          <div class="col-md-auto d-flex gap-2">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a class="btn" href="<?= e(url('/encaissements')) ?>">Effacer</a>
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
              <th scope="col">Nature</th>
              <th scope="col">Date</th>
              <th scope="col" class="text-end">Montant FCFA</th>
              <th scope="col">Unité de production</th>
              <th scope="col">Caisse</th>
              <th scope="col">Caissier</th>
              <th scope="col">Client</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url('/encaissements/' . $r['id'])) ?>">Consulter</a><?php endif; ?></td>
                <td>
                  <?php if (!empty($r['aborded_at'])): ?>
                    <span class="status status-danger"><span class="status-dot"></span>Annulé</span>
                  <?php else: ?>
                    <span class="status status-success"><span class="status-dot"></span>Effectué</span>
                  <?php endif; ?>
                </td>
                <td><?= e($r['id']) ?></td>
                <td><?= e(CollectionModel::typeLabel($r['type'])) ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td class="text-end"><?= e($n((float) $r['received'] - (float) $r['returned'])) ?></td>
                <td><?= e($r['unity_name'] ?? '--') ?></td>
                <td><?= e($r['register_name'] ?? '--') ?></td>
                <td>
                  <div><?= e($r['user_name'] ?? '--') ?></div>
                  <?php if (!empty($r['user_email'])): ?><div><small><a href="mailto:<?= e($r['user_email']) ?>"><?= e($r['user_email']) ?></a></small></div><?php endif; ?>
                  <?php if (!empty($r['user_phone'])): ?><div><small><a href="tel:<?= e($r['user_phone']) ?>"><?= e($r['user_phone']) ?></a></small></div><?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($r['client_id'])): ?>
                    <div><?= e($r['client_name'] ?? '--') ?></div>
                    <div><small>Nº de compte: <?= e($r['client_id']) ?></small></div>
                    <?php if (!empty($r['client_email'])): ?><div><small><a href="mailto:<?= e($r['client_email']) ?>"><?= e($r['client_email']) ?></a></small></div><?php endif; ?>
                    <?php if (!empty($r['client_phone'])): ?><div><small><a href="tel:<?= e($r['client_phone']) ?>"><?= e($r['client_phone']) ?></a></small></div><?php endif; ?>
                  <?php else: ?>--<?php endif; ?>
                </td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="11" class="text-center text-secondary">Aucun encaissement trouvé.</td></tr>
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
