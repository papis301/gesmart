<?php
/**
 * @var array  $u        valeurs (banque ou saisie précédente)
 * @var array  $users    utilisateurs affectables
 * @var string $p        préfixe des identifiants HTML
 * @var bool   $readonly lecture seule
 * @var bool   $creating formulaire de création (champs initiaux)
 */
$val = static fn(string $k): string => (string) ($u[$k] ?? '');
$userLabel = static fn(array $us): string => $us['name'] . ($us['group_name'] ? ' — ' . $us['group_name'] : '') . ($us['unity_name'] ? ' (' . $us['unity_name'] . ')' : '');
$ro = $readonly ? 'readonly' : '';
?>
<div class="mb-3">
  <label class="form-label" for="<?= e($p) ?>admin_user_id">Gestionnaire de banque</label>
  <select required name="admin_user_id" class="form-select" id="<?= e($p) ?>admin_user_id" <?= $readonly ? 'disabled' : '' ?>>
    <option value="">-- Choisir le gestionnaire --</option>
    <?php foreach ($users as $us): ?>
      <option value="<?= (int) $us['id'] ?>" <?= $val('admin_user_id') === (string) $us['id'] ? 'selected' : '' ?>><?= e($userLabel($us)) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="mb-3">
  <label class="form-label" for="<?= e($p) ?>user_id">Agent affecté</label>
  <select required name="user_id" class="form-select" id="<?= e($p) ?>user_id" <?= $readonly ? 'disabled' : '' ?>>
    <option value="">-- Choisir l'agent --</option>
    <?php foreach ($users as $us): ?>
      <option value="<?= (int) $us['id'] ?>" <?= $val('user_id') === (string) $us['id'] ? 'selected' : '' ?>><?= e($userLabel($us)) ?></option>
    <?php endforeach; ?>
  </select>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>name" class="form-label">Nom*</label>
  <input required type="text" class="form-control text-uppercase" name="name" id="<?= e($p) ?>name" maxlength="255"
         placeholder="Nom de la banque" value="<?= e($val('name')) ?>" <?= $ro ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>id" class="form-label">Numéro de compte*</label>
  <?php if ($creating): ?>
    <input required type="text" class="form-control text-uppercase" name="id" id="<?= e($p) ?>id" maxlength="255"
           placeholder="XXXXXXXXXX" value="<?= e($val('id')) ?>">
  <?php else: ?>
    <input type="text" class="form-control" id="<?= e($p) ?>id" value="<?= e($val('id')) ?>" readonly>
  <?php endif; ?>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>email" class="form-label">Email de contact</label>
  <input type="email" class="form-control" name="email" id="<?= e($p) ?>email" maxlength="255"
         placeholder="exemple@exemple.com" value="<?= e($val('email')) ?>" <?= $ro ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>phone" class="form-label">Téléphone de contact</label>
  <input type="tel" class="form-control" name="phone" id="<?= e($p) ?>phone" maxlength="20"
         placeholder="+221XXXXXXXXX" value="<?= e($val('phone')) ?>" <?= $ro ?>>
</div>
<?php if ($creating): ?>
  <div class="mb-3">
    <label for="<?= e($p) ?>balance" class="form-label">Solde initial du compte</label>
    <input type="number" min="0" step="any" class="form-control" name="balance" id="<?= e($p) ?>balance" placeholder="Définir le solde" value="<?= e($val('balance') ?: '0') ?>">
  </div>
  <div class="mb-3">
    <label for="<?= e($p) ?>credit" class="form-label">Crédit initial du compte</label>
    <input type="number" max="0" step="any" class="form-control" name="credit" id="<?= e($p) ?>credit" placeholder="Définir le crédit" value="<?= e($val('credit') ?: '0') ?>">
    <div class="form-hint">Montant négatif ou nul.</div>
  </div>
  <div class="mb-3">
    <label for="<?= e($p) ?>warranty" class="form-label">Montant de garantie initial du compte</label>
    <input type="number" min="0" step="any" class="form-control" name="warranty" id="<?= e($p) ?>warranty" placeholder="Définir le montant garantie" value="<?= e($val('warranty') ?: '0') ?>">
  </div>
<?php endif; ?>
