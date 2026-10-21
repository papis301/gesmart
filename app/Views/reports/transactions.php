<?php
/** @var array $filters @var string $fromInput @var string $toInput @var array $options @var array $types
 *  @var bool $submitted @var array $rows @var int $total @var int $page @var int $pages */
$base = '/rapports/transactions';
$n = static fn(mixed $v): string => number_format((float) $v, 0, ',', ' ');
$sel = static fn(mixed $v, array $chosen): string => in_array((string) $v, array_map('strval', $chosen), true) ? ' selected' : '';
$params = [
    'from_date' => $fromInput, 'to_date' => $toInput,
    'registers' => $filters['registers'], 'clients' => $filters['clients'],
    'banks' => $filters['banks'], 'transactions' => $filters['types'],
];
$query = static fn(int $p): string => url($base) . '?' . http_build_query($params + ($p > 1 ? ['page' => $p] : []));
$statusLabel = ['validated' => ['Validé', 'success'], 'pending' => ['En attente de validation', 'warning'], 'aborded' => ['Annulé', 'danger']];
$pair = static function (?string $a, ?string $b): string {
    $a = $a !== null ? e($a) : null;
    $b = $b !== null ? e($b) : null;
    if ($a === null && $b === null) {
        return '--';
    }
    return $b === null ? $a : ($a ?? '--') . ' <span class="text-secondary">&rarr;</span> ' . $b;
};
?>
<div class="row row-cards">
  <div class="col-12">
    <div class="card">
      <div class="card-header"><div class="card-title">Générer un rapport</div></div>
      <div class="card-body">
        <form action="<?= e(url($base)) ?>" method="get">
          <div class="row">
            <h3>Période</h3>
            <div class="col-md col-lg-4 col-xl-3 mb-3">
              <label for="from_date" class="form-label">Du</label>
              <input type="datetime-local" value="<?= e($fromInput) ?>" class="form-control" name="from_date" id="from_date" required>
            </div>
            <div class="col-md col-lg-4 col-xl-3 mb-3">
              <label for="to_date" class="form-label">Au</label>
              <input type="datetime-local" value="<?= e($toInput) ?>" class="form-control" name="to_date" id="to_date" required>
            </div>
            <h3>Sujets</h3>
            <p>Laissez vide pour ne pas appliquer de filtre (Ctrl/Cmd + clic pour une sélection multiple).</p>
            <div class="col-md col-lg-4 col-xl-3 mb-3">
              <label for="registers" class="form-label">Caisses</label>
              <select class="form-select" multiple size="6" name="registers[]" id="registers">
                <?php foreach ($options['registers'] as $o): ?>
                  <option value="<?= (int) $o['id'] ?>"<?= $sel($o['id'], $filters['registers']) ?>><?= e($o['name'] . ' - ' . ($o['unity_name'] ?? '--')) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md col-lg-4 col-xl-3 mb-3">
              <label for="clients" class="form-label">Comptes clients</label>
              <select class="form-select" multiple size="6" name="clients[]" id="clients">
                <?php foreach ($options['clients'] as $o): ?>
                  <option value="<?= e($o['id']) ?>"<?= $sel($o['id'], $filters['clients']) ?>><?= e('Nº ' . $o['id'] . ' - ' . ($o['name'] ?? '--')) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md col-lg-4 col-xl-3 mb-3">
              <label for="banks" class="form-label">Banques</label>
              <select class="form-select" multiple size="6" name="banks[]" id="banks">
                <?php foreach ($options['banks'] as $o): ?>
                  <option value="<?= e($o['id']) ?>"<?= $sel($o['id'], $filters['banks']) ?>><?= e($o['name'] ?? $o['id']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md col-lg-4 col-xl-3 mb-3">
              <label for="transactions" class="form-label">Type de transaction</label>
              <select class="form-select" multiple size="6" name="transactions[]" id="transactions">
                <?php foreach ($types as $code => $label): ?>
                  <option value="<?= e($code) ?>"<?= $sel($code, $filters['types']) ?>><?= e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 text-center"><button type="submit" class="btn btn-primary">Générer</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if ($submitted): ?>
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <div class="card-title">Transactions du <?= e(dt($filters['from'])) ?> au <?= e(dt($filters['to'])) ?> : <span class="text-primary"><?= e($n($total)) ?></span></div>
      </div>
      <div class="table-hover table-responsive">
        <table class="table-hover table table-vcenter table-nowrap">
          <thead>
            <tr>
              <th>Type</th><th>Référence</th><th>Date</th><th class="text-end">Montant FCFA</th><th>Statut</th>
              <th>Caisse(s)</th><th>Compte(s) client</th><th>Banque(s)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php [$sl, $sc] = $statusLabel[$r['tstatus']] ?? ['--', 'secondary']; ?>
              <tr>
                <td><?= e($types[$r['ttype']] ?? $r['ttype']) ?></td>
                <td><?= e($r['tid']) ?></td>
                <td><?= e(dt($r['tdate']) ?: '--') ?></td>
                <td class="text-end"><?= e($n($r['tamount'])) ?></td>
                <td><span class="status status-<?= e($sc) ?>"><span class="status-dot"></span> <?= e($sl) ?></span></td>
                <td><?= $pair($r['reg1_name'], $r['reg2_name']) ?></td>
                <td><?= $pair($r['cli1'] !== null ? ($r['cli1_name'] ?? (string) $r['cli1']) : null, $r['cli2'] !== null ? ($r['cli2_name'] ?? (string) $r['cli2']) : null) ?></td>
                <td><?= $pair($r['bank1'] !== null ? ($r['bank1_name'] ?? $r['bank1']) : null, $r['bank2'] !== null ? ($r['bank2_name'] ?? $r['bank2']) : null) ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
              <tr><td colspan="8" class="text-center text-secondary">Aucune transaction pour ces critères.</td></tr>
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
  <?php endif; ?>
</div>
