<?php
/**
 * Cases à cocher des droits (partagé par la création et la consultation d'un groupe).
 *
 * @var array  $resources catalogue (RulesCatalog::resources())
 * @var array  $checked   alias => true pour les droits actuellement accordés
 * @var array  $grantable alias => true pour les droits que l'utilisateur peut modifier
 * @var string $idPrefix  préfixe des identifiants HTML
 * @var bool   $readonly  true : tout est désactivé
 */
foreach ($resources as $key => $resource): ?>
  <div class="mt-3"><strong><?= e($resource['title']) ?></strong></div>
  <div class="mb-2"><small><?= e($resource['description']) ?></small></div>
  <?php foreach ($resource['permissions'] as $value => $permission):
      $alias = $permission['rule'][4];
      $inputId = $idPrefix . '_' . $key . '_' . $value;
      $disabled = $readonly || !isset($grantable[$alias]);
  ?>
    <div class="datagrid">
      <div class="datagrid-item">
        <div class="datagrid-content">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="<?= e($inputId) ?>"
                   name="rules[<?= e($key) ?>][]" value="<?= e((string) $value) ?>"
                   <?= isset($checked[$alias]) ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
            <label class="form-check-label" for="<?= e($inputId) ?>"><?= e($permission['label']) ?></label>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endforeach; ?>
