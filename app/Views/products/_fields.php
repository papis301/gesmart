<?php
/**
 * @var array  $u        valeurs (produit ou saisie précédente)
 * @var array  $unities  unités actives
 * @var string $p        préfixe des identifiants HTML
 * @var bool   $readonly lecture seule
 */
$val = static fn(string $k): string => (string) ($u[$k] ?? '');
?>
<div class="mb-3">
  <label class="form-label" for="<?= e($p) ?>unity_id">Unité de production</label>
  <select required name="unity_id" class="form-select" id="<?= e($p) ?>unity_id" <?= $readonly ? 'disabled' : '' ?>>
    <option value="">-- Choisir une unité --</option>
    <?php foreach ($unities as $un): ?>
      <option value="<?= (int) $un['id'] ?>" <?= $val('unity_id') === (string) $un['id'] ? 'selected' : '' ?>><?= e($un['name']) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>name" class="form-label">Nom*</label>
  <input required type="text" class="form-control" name="name" id="<?= e($p) ?>name" maxlength="255"
         placeholder="Nom du produit" value="<?= e($val('name')) ?>" <?= $readonly ? 'readonly' : '' ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>price" class="form-label">Prix unitaire en FCFA</label>
  <input required min="0" step="any" type="number" class="form-control" name="price" id="<?= e($p) ?>price"
         placeholder="Prix" value="<?= e($val('price') === '' ? '' : (string) (float) $val('price')) ?>" <?= $readonly ? 'readonly' : '' ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>stock" class="form-label">Stock</label>
  <input required min="0" step="1" type="number" class="form-control" name="stock" id="<?= e($p) ?>stock"
         placeholder="Stock" value="<?= e($val('stock')) ?>" <?= $readonly ? 'readonly' : '' ?>>
</div>
