<?php
/** @var array $rows @var int $total @var int $page @var int $pages @var string $term @var bool $canInfo */
use App\Models\WithdrawalModel as M;

$base = '/retraits';
$query = static fn(int $p): string => url($base) . '?' . http_build_query(array_filter(['s' => $term, 'page' => $p > 1 ? $p : null]));
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
$safeUrl = static fn(?string $u): bool => $u !== null && preg_match('#^https?://#i', $u) === 1;
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Liste des retraits: <span class="text-primary"><?= e($n($total)) ?></span></div>
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
              <th scope="col">Nature</th>
              <th scope="col" class="text-end">Montant FCFA</th>
              <th scope="col">Chéque</th>
              <th scope="col">Transactions</th>
              <th scope="col">Créé le</th>
              <th scope="col">Modifié le</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $st = M::status($r); ?>
              <tr>
                <td scope="row"><?php if ($canInfo): ?><a href="<?= e(url($base . '/' . (int) $r['id'])) ?>">Consulter</a><?php endif; ?></td>
                <td><span class="status status-<?= e($st['class']) ?>"><span class="status-dot"></span> <?= e($st['label']) ?></span></td>
                <td><?= e($r['id']) ?></td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td><?= e(M::type($r['type'])) ?></td>
                <td class="text-end"><?= e($n($r['total'])) ?></td>
                <td>
                  <?php if ($safeUrl($r['check_file'])): ?>
                    <a href="<?= e($r['check_file']) ?>" target="_blank" rel="noopener noreferrer">Ouvrir le fichier</a>
                  <?php else: ?>
                    <span class="status status-dark"><span class="status-dot"></span> Pas de fichier joint</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div>Utilisateur: <?= e($r['user_name'] ?? '--') ?></div>
                  <div>Du compte bancaire: Nº<?= e($r['from_bank_id'] ?? '--') ?> - <?= e($r['from_bank_name'] ?? '--') ?></div>
                  <div>Compte bancaire crédité: Nº<?= e($r['to_bank_id'] ?? ' --') ?> - <?= e($r['to_bank_name'] ?? '--') ?></div>
                  <div>Compte client débité: Nº de compte: <?= e($r['client_id'] ?? '--') ?> <?= e($r['client_name'] ?? '--') ?></div>
                </td>
                <td><?= e(dt($r['created_at']) ?: '--') ?></td>
                <td><?= e(dt($r['updated_at']) ?: '--') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="10" class="text-center text-secondary">Aucun retrait trouvé.</td></tr>
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
