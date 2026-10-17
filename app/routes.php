<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\GroupController;

/** @var App\Core\Router $router */

// add(méthode, chemin, [Contrôleur, action], exiger_connexion, exiger_droit_rules)
$router->add('GET',  '',                [AuthController::class, 'showLogin'],   false, false);
$router->add('POST', '',                [AuthController::class, 'login'],       false, false);
$router->add('POST', 'se-deconnecter',  [AuthController::class, 'logout'],      true, false);
$router->add('GET',  'tableau-de-bord', [DashboardController::class, 'index'],  true, false);

// Groupes (droits : table rules, alias groups / create_group / info_group / edit_group / delete_group)
$router->add('GET',  'groupes',            [GroupController::class, 'index']);
$router->add('POST', 'groupes',            [GroupController::class, 'create']);
$router->add('GET',  'groupes/(:num)',     [GroupController::class, 'info']);
$router->add('POST', 'groupes/modifier',   [GroupController::class, 'edit']);
$router->add('POST', 'groupes/supprimer',  [GroupController::class, 'delete']);

// Les modules CRUD s'ajouteront ici, avec le 5e paramètre à true pour appliquer la table rules :
// $router->add('GET', 'clients', [ClientController::class, 'index'], true, true);
