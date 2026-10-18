<?php
/**
 * @var array  $u        valeurs (client ou saisie précédente)
 * @var array  $unities  unités actives
 * @var string $mode     'create' | 'edit'
 * @var string $p        préfixe des identifiants HTML
 * @var bool   $readonly lecture seule
 */
$val = static fn(string $k): string => (string) ($u[$k] ?? '');
$ro = $readonly ? 'readonly' : '';
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
<?php if ($mode === 'create'): ?>
  <div class="mb-3">
    <label for="<?= e($p) ?>balance" class="form-label">FAGAROU / remboursement initial</label>
    <input type="number" min="0" step="any" class="form-control" name="balance" id="<?= e($p) ?>balance" required value="<?= e($val('balance') === '' ? '0' : $val('balance')) ?>">
  </div>
  <div class="mb-3">
    <label for="<?= e($p) ?>credit" class="form-label">Crédit initial</label>
    <input type="number" max="0" step="any" class="form-control" name="credit" id="<?= e($p) ?>credit" required value="<?= e($val('credit') === '' ? '0' : $val('credit')) ?>">
    <div class="form-hint">Saisissez un montant négatif : c'est ce que le client doit déjà.</div>
  </div>
<?php endif; ?>
<div class="mb-3">
  <label for="<?= e($p) ?>ninea" class="form-label">NINEA</label>
  <input type="text" class="form-control" name="ninea" id="<?= e($p) ?>ninea" maxlength="255" placeholder="Doit être unique" value="<?= e($val('ninea')) ?>" <?= $ro ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>nid" class="form-label">Numéro d'identité nationale</label>
  <input type="text" class="form-control" name="nid" id="<?= e($p) ?>nid" maxlength="255" placeholder="À l'arrière de la CIN" value="<?= e($val('nid')) ?>" <?= $ro ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>name" class="form-label">Nom*</label>
  <input required type="text" class="form-control text-uppercase" name="name" id="<?= e($p) ?>name" maxlength="255" placeholder="Nom complet du client" value="<?= e($val('name')) ?>" <?= $ro ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>email" class="form-label">Email</label>
  <input type="email" class="form-control" name="email" id="<?= e($p) ?>email" maxlength="255" placeholder="exemple@exemple.com" value="<?= e($val('email')) ?>" <?= $ro ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>phone" class="form-label">Téléphone</label>
  <input type="tel" class="form-control" name="phone" id="<?= e($p) ?>phone" maxlength="30" placeholder="+221XXXXXXXXX" value="<?= e($val('phone')) ?>" <?= $ro ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>address" class="form-label">Adresse complète</label>
  <textarea class="form-control" name="address" id="<?= e($p) ?>address" rows="3" maxlength="255" placeholder="Villa, Rue, Ville, Région, Pays" <?= $ro ?>><?= e($val('address')) ?></textarea>
</div>
