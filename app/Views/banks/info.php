<?php
/**
 * @var array $bank @var array $users @var array $credits @var array $links @var array $freeUnities
 * @var array $history @var bool $empty @var bool $canEdit @var bool $canDelete @var bool $canLink @var bool $canUnlink
 */
use App\Core\Csrf;
use App\Core\View;

$bid = (string) $bank['id'];
?>
<div class="row row-cards">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Banque <span class="text-primary"><?= e($bank['name']) ?></span></div>
      </div>
      <div class="card-body">
        <div class="datagrid mb-3">
          <div class="datagrid-item"><div class="datagrid-title">Solde</div><div class="datagrid-content <?= (float) $bank['balance'] < 0 ? 'text-danger' : '' ?>"><?= e(fcfa($bank['balance'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Crédit</div><div class="datagrid-content"><?= e(fcfa($bank['credit'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Garantie</div><div class="datagrid-content"><?= e(fcfa($bank['warranty'])) ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Créée le</div><div class="datagrid-content"><?= e(dt($bank['created_at']) ?: '--') ?></div></div>
          <div class="datagrid-item"><div class="datagrid-title">Modifiée le</div><div class="datagrid-content"><?= e(dt($bank['updated_at']) ?: '--') ?></div></div>
        </div>

        <form id="editForm" action="<?= e(url('/banques/modifier')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= e($bid) ?>">
          <?= View::partial('banks/_fields', ['u' => $bank, 'users' => $users, 'p' => 'e_', 'readonly' => !$canEdit, 'creating' => false]) ?>
        </form>
      </div>
      <div class="card-footer d-flex gap-2">
        <a href="<?= e(url('/banques')) ?>" class="btn me-auto">Retour</a>
        <?php if ($canDelete): ?>
          <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirmDelete">Supprimer</button>
        <?php endif; ?>
        <?php if ($canEdit): ?>
          <button form="editForm" type="submit" class="btn btn-primary">Enregistrer</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><div class="card-title">Unités de production liées</div></div>
      <div class="list-group list-group-flush">
        <?php foreach ($links as $l): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= e($l['unity_name']) ?></span>
            <?php if ($canUnlink): ?>
              <form action="<?= e(url('/banques/supprimer-lien-banque-unite')) ?>" method="post" class="m-0" onsubmit="return confirm('Supprimer cette relation ?');">
                <?= Csrf::field() ?>
                <input type="hidden" name="bank_id" value="<?= e($bid) ?>">
                <input type="hidden" name="link_id" value="<?= (int) $l['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Retirer</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
        <?php if (!$links): ?><div class="list-group-item text-secondary">Aucune unité liée.</div><?php endif; ?>
      </div>
      <?php if ($canLink && $freeUnities): ?>
        <div class="card-footer">
          <form action="<?= e(url('/banques/creer-lien-banque-unite')) ?>" method="post" class="d-flex gap-2">
            <?= Csrf::field() ?>
            <input type="hidden" name="bank_id" value="<?= e($bid) ?>">
            <select required name="unity_id" class="form-select" aria-label="Unité de production">
              <option value="">-- Lier une unité --</option>
              <?php foreach ($freeUnities as $un): ?>
                <option value="<?= (int) $un['id'] ?>"><?= e($un['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Lier</button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <div class="card mb-3">
      <div class="card-header"><div class="card-title">Crédits et découverts</div></div>
      <div class="table-responsive">
        <table class="table table-vcenter table-sm card-table">
          <thead><tr><th>Date</th><th>Type</th><th class="text-end">Crédit</th><th class="text-end">Garantie</th><th class="text-end">Autorisation</th></tr></thead>
          <tbody>
            <?php foreach ($credits as $c): ?>
              <tr>
                <td><?= e(date('d/m/Y', strtotime((string) $c['created_at']) ?: 0)) ?></td>
                <td><span class="badge <?= (int) $c['is_overdraft'] === 1 ? 'bg-orange-lt' : 'bg-blue-lt' ?>"><?= (int) $c['is_overdraft'] === 1 ? 'Découvert' : 'Crédit' ?></span></td>
                <td class="text-end"><?= e(number_format((float) $c['credit'], 0, ',', ' ')) ?></td>
                <td class="text-end"><?= e(number_format((float) $c['warranty'], 0, ',', ' ')) ?></td>
                <td class="text-end"><?= (float) $c['overdraft'] > 0 ? e(number_format((float) $c['overdraft'], 0, ',', ' ')) : '---' ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$credits): ?><tr><td colspan="5" class="text-center text-secondary">Aucun crédit enregistré.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Historique</div></div>
      <div class="list-group list-group-flush">
        <?php foreach ($history as $label => $n): ?>
          <div class="list-group-item d-flex justify-content-between">
            <span><?= e($label) ?></span>
            <span class="badge <?= $n > 0 ? 'bg-blue-lt' : 'bg-secondary-lt' ?>"><?= (int) $n ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="card-footer text-secondary">Le solde, le crédit et la garantie sont mis à jour par les opérations bancaires : ils ne se modifient pas ici.</div>
    </div>
  </div>
</div>

<?php if ($canDelete): ?>
  <div class="modal modal-sm" id="confirmDelete" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <form action="<?= e(url('/banques/supprimer')) ?>" method="post">
          <?= Csrf::field() ?>
          <input type="hidden" name="id" value="<?= e($bid) ?>">
          <div class="modal-body">
            <div class="modal-title">Supprimer cette banque ?</div>
            <div>
              Le compte « <?= e($bank['name']) ?> » sera supprimé. L'historique des opérations est conservé.
              <?php if (!$empty): ?>
                <div class="text-danger mt-2">Le solde est de <?= e(fcfa($bank['balance'])) ?> : la suppression sera refusée.</div>
              <?php endif; ?>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-danger">Supprimer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>
