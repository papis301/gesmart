<?php
/**
 * Champs communs au formulaire de création et de modification d'un utilisateur.
 *
 * @var array  $u        valeurs actuelles (ou saisie précédente)
 * @var array  $groups   groupes attribuables
 * @var array  $unities  unités actives
 * @var string $mode     'create' | 'edit'
 * @var string $p        préfixe des identifiants HTML
 * @var bool   $isSelf   true : on modifie son propre compte (groupe et statut verrouillés)
 * @var bool   $readonly true : lecture seule
 */
$val = static fn(string $k): string => (string) ($u[$k] ?? '');
?>
<div class="mb-3">
  <label class="form-label" for="<?= e($p) ?>group_id">Groupe</label>
  <select required name="group_id" class="form-select" id="<?= e($p) ?>group_id" <?= ($readonly || $isSelf) ? 'disabled' : '' ?>>
    <option value="">-- Choisir un groupe --</option>
    <?php foreach ($groups as $g): ?>
      <option value="<?= (int) $g['id'] ?>" <?= $val('group_id') === (string) $g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($isSelf && !$readonly): ?><div class="form-hint">Vous ne pouvez pas changer votre propre groupe.</div><?php endif; ?>
</div>
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
         placeholder="Nom de l'utilisateur" value="<?= e($val('name')) ?>" <?= $readonly ? 'readonly' : '' ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>login" class="form-label">Login*</label>
  <input required type="text" class="form-control text-uppercase" name="login" id="<?= e($p) ?>login" maxlength="50"
         placeholder="Créez un login unique" autocomplete="off" value="<?= e($val('login')) ?>" <?= $readonly ? 'readonly' : '' ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>email" class="form-label">Email</label>
  <input type="email" class="form-control" name="email" id="<?= e($p) ?>email" maxlength="255"
         placeholder="exemple@exemple.com" value="<?= e($val('email')) ?>" <?= $readonly ? 'readonly' : '' ?>>
</div>
<div class="mb-3">
  <label for="<?= e($p) ?>phone" class="form-label">Téléphone</label>
  <input type="tel" class="form-control" name="phone" id="<?= e($p) ?>phone" maxlength="20"
         placeholder="+221XXXXXXXXX" value="<?= e($val('phone')) ?>" <?= $readonly ? 'readonly' : '' ?>>
</div>
<?php if (!$readonly): ?>
  <div class="mb-3">
    <label for="<?= e($p) ?>password" class="form-label"><?= $mode === 'create' ? 'Mot de passe*' : 'Nouveau mot de passe' ?></label>
    <input <?= $mode === 'create' ? 'required' : '' ?> type="password" class="form-control" name="password" id="<?= e($p) ?>password"
           minlength="8" maxlength="200" autocomplete="new-password"
           placeholder="<?= $mode === 'create' ? 'Créez un mot de passe (8 caractères minimum)' : 'Laisser vide pour ne pas le changer' ?>">
  </div>
<?php endif; ?>
<?php if ($mode === 'edit'): ?>
  <div class="mb-3">
    <label class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="suspended" value="1" id="<?= e($p) ?>suspended"
             <?= $val('suspended') === '1' ? 'checked' : '' ?> <?= ($readonly || $isSelf) ? 'disabled' : '' ?>>
      <span class="form-check-label">Compte suspendu (connexion impossible)</span>
    </label>
    <?php if ($isSelf && !$readonly): ?><div class="form-hint">Vous ne pouvez pas suspendre votre propre compte.</div><?php endif; ?>
  </div>
<?php endif; ?>
