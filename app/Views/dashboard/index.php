<?php
/** @var array $user @var array $pending */
$blocks = [
    ['stat.svg',       'Afficher les données financières'],
    ['sales.svg',      'Afficher les données de ventes'],
    ['commercial.svg', 'Afficher les données commerciales'],
    ['charges.svg',    'Afficher la structure des charges'],
];
?>
<div class="row row-cards">

  <?php foreach ($blocks as [$img, $label]): ?>
    <div class="col-12">
      <div class="card">
        <div class="card-body text-center d-flex flex-column">
          <?php if (asset_exists('img/' . $img)): ?>
            <img src="<?= e(url('assets/img/' . $img)) ?>" style="max-width: 400px; height:auto" class="mb-3 mx-auto" alt="">
          <?php endif; ?>
          <div><button type="button" class="btn btn-primary" disabled><?= e($label) ?></button>
            <span class="badge bg-secondary-lt ms-2">bientôt</span></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if ($pending): ?>
    <h2>Opérations en attentes</h2>
    <?php foreach ($pending as $name => $block): ?>
      <div class="col-md col-lg-4 col-xl-3 mb-3 d-flex">
        <div class="card flex-fill">
          <div class="card-body">
            <div class="card-title"><?= e($name) ?></div>
            <?php if ($block['rows']): ?>
              <ul style="max-height: 300px; overflow-x:scroll">
                <?php foreach ($block['rows'] as $r): ?>
                  <li><?= e(dt($r['created_at'])) ?> - <span class="text-dark"><?= e($r['label']) ?>
                    (Montant: <b><?= e(fcfa($r['amount'])) ?></b>)</span></li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-secondary mb-0">Aucune opération en attente.</p>
            <?php endif; ?>
          </div>
          <div class="card-footer"><b>Total:</b> <?= e(fcfa($block['total'])) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

</div>
