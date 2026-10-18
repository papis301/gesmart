<?php
/**
 * @var array  $u        valeurs (caisse ou saisie précédente)
 * @var array  $unities  unités actives
 * @var array  $users    utilisateurs affectables
 * @var string $p        préfixe des identifiants HTML
 * @var bool   $readonly lecture seule
 */
$val = static fn(string $k): string => (string) ($u[$k] ?? '');
$userLabel = static fn(array $us): string => $us['name'] . ($us['group_name'] ? ' — ' . $us['group_name'] : '') . ($us['unity_name'] ? ' (' . $us['unity_name'] . ')' : '');
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
         placeholder="Nom de la caisse" value="<?= e($val('name')) ?>" <?= $readonly ? 'readonly' : '' ?>>
</div>
<div class="mb-3">
  <label class="form-label" for="<?= e($p) ?>user_id">Caissier</label>
  <select required name="user_id" class="form-select" id="<?= e($p) ?>user_id" <?= $readonly ? 'disabled' : '' ?>>
    <option value="">-- Choisir le caissier --</option>
    <?php foreach ($users as $us): ?>
      <option value="<?= (int) $us['id'] ?>" <?= $val('user_id') === (string) $us['id'] ? 'selected' : '' ?>><?= e($userLabel($us)) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if (!$readonly): ?><div class="form-hint">Une personne ne peut tenir qu'une seule caisse.</div><?php endif; ?>
</div>
<div class="mb-3">
  <label class="form-label" for="<?= e($p) ?>admin_user_id">Responsable de la caisse</label>
  <select required name="admin_user_id" class="form-select" id="<?= e($p) ?>admin_user_id" <?= $readonly ? 'disabled' : '' ?>>
    <option value="">-- Choisir le responsable --</option>
    <?php foreach ($users as $us): ?>
      <option value="<?= (int) $us['id'] ?>" <?= $val('admin_user_id') === (string) $us['id'] ? 'selected' : '' ?>><?= e($userLabel($us)) ?></option>
    <?php endforeach; ?>
  </select>
</div>
