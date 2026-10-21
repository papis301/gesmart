<?php
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\GroupController;
use App\Controllers\ProductController;
use App\Controllers\UnityController;
use App\Controllers\UserController;
use App\Controllers\CashRegisterController;
use App\Controllers\ClientController;
use App\Controllers\BankController;
use App\Controllers\SaleController;
use App\Controllers\CollectionController;
use App\Controllers\InterRegisterTransferController;
use App\Controllers\InterUnitTransferController;
use App\Controllers\InterClientTransferController;
use App\Controllers\PaymentController;
use App\Controllers\WithdrawalController;
use App\Controllers\DisbursementController;
use App\Controllers\ClosureController;
use App\Controllers\RegularisationController;
use App\Controllers\ReportController;

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

// Utilisateurs (droits : users / create_user / info_user / edit_user / delete_user)
$router->add('GET',  'utilisateurs',            [UserController::class, 'index']);
$router->add('POST', 'utilisateurs',            [UserController::class, 'create']);
$router->add('GET',  'utilisateurs/(:num)',     [UserController::class, 'info']);
$router->add('POST', 'utilisateurs/modifier',   [UserController::class, 'edit']);
$router->add('POST', 'utilisateurs/supprimer',  [UserController::class, 'delete']);

// Unités de production (droits : unities / create_unity / info_unity / edit_unity / delete_unity)
$router->add('GET',  'unites',            [UnityController::class, 'index']);
$router->add('POST', 'unites',            [UnityController::class, 'create']);
$router->add('GET',  'unites/(:num)',     [UnityController::class, 'info']);
$router->add('POST', 'unites/modifier',   [UnityController::class, 'edit']);
$router->add('POST', 'unites/supprimer',  [UnityController::class, 'delete']);

// Produits (droits : products / create_product / info_product / edit_product / delete_product)
$router->add('GET',  'produits',            [ProductController::class, 'index']);
$router->add('POST', 'produits',            [ProductController::class, 'create']);
$router->add('GET',  'produits/(:num)',     [ProductController::class, 'info']);
$router->add('POST', 'produits/modifier',   [ProductController::class, 'edit']);
$router->add('POST', 'produits/supprimer',  [ProductController::class, 'delete']);

$router->add('GET',  'caisses',           [CashRegisterController::class, 'index'],  true, true);
$router->add('POST', 'caisses',           [CashRegisterController::class, 'create'], true, true);
$router->add('GET',  'caisses/(:num)',    [CashRegisterController::class, 'info'],   true, true);
$router->add('POST', 'caisses/modifier',  [CashRegisterController::class, 'edit'],   true, true);
$router->add('POST', 'caisses/supprimer', [CashRegisterController::class, 'delete'], true, true);

$router->add('GET',  'clients',           [ClientController::class, 'index'],           true, true);
$router->add('POST', 'clients',           [ClientController::class, 'create'],          true, true);
$router->add('GET',  'clients/(:num)',    [ClientController::class, 'info'],            true, true);
$router->add('POST', 'clients/modifier',  [ClientController::class, 'edit'],            true, true);
$router->add('POST', 'clients/supprimer', [ClientController::class, 'delete'],          true, true);
$router->add('POST', 'clients/valider',   [ClientController::class, 'validate_client'], true, true);

$router->add('GET',  'banques',                             [BankController::class, 'index'],                   true, true);
$router->add('POST', 'banques',                             [BankController::class, 'create'],                  true, true);
$router->add('POST', 'banques/modifier',                    [BankController::class, 'edit'],                    true, true);
$router->add('POST', 'banques/supprimer',                   [BankController::class, 'delete'],                  true, true);
$router->add('POST', 'banques/creer-lien-banque-unite',     [BankController::class, 'create_bank_unity_link'],  true, true);
$router->add('POST', 'banques/supprimer-lien-banque-unite', [BankController::class, 'delete_bank_unity_link'], true, true);
$router->add('GET',  'banques/(:segment)',                  [BankController::class, 'info'],                    true, true);
// Les modules CRUD s'ajouteront ici, avec le 5e paramètre à true pour appliquer la table rules :
// $router->add('GET', 'clients', [ClientController::class, 'index'], true, true);

$router->add('GET', 'ventes',        [SaleController::class, 'index'], true, true);
$router->add('GET', 'ventes/(:num)', [SaleController::class, 'info'],  true, true);

$router->add('GET', 'encaissements',        [CollectionController::class, 'index'], true, true);
$router->add('GET', 'encaissements/(:num)', [CollectionController::class, 'info'],  true, true);

$router->add('GET', 'transferts/inter_caisses',        [InterRegisterTransferController::class, 'index'], true, true);
$router->add('GET', 'transferts/inter_caisses/(:num)', [InterRegisterTransferController::class, 'info'],  true, true);

$router->add('GET', 'transferts/hors_places',        [InterUnitTransferController::class, 'index'], true, true);
$router->add('GET', 'transferts/hors_places/(:num)', [InterUnitTransferController::class, 'info'],  true, true);

$router->add('GET', 'transferts/compte_a_comptes',        [InterClientTransferController::class, 'index'], true, true);
$router->add('GET', 'transferts/compte_a_comptes/(:num)', [InterClientTransferController::class, 'info'],  true, true);

$router->add('GET', 'versements',        [PaymentController::class, 'index'], true, true);
$router->add('GET', 'versements/(:num)', [PaymentController::class, 'info'],  true, true);

$router->add('GET', 'retraits',        [WithdrawalController::class, 'index'], true, true);
$router->add('GET', 'retraits/(:num)', [WithdrawalController::class, 'info'],  true, true);

$router->add('GET', 'validations/ventes', [SaleController::class, 'list_sale_validations'], true, true);

$router->add('GET', 'validations/decaissements', [DisbursementController::class, 'list_disbursement_validations'], true, true);
$router->add('GET', 'decaissements/(:num)',       [DisbursementController::class, 'info'], true, true);

$router->add('GET', 'validations/transferts/inter_caisses', [InterRegisterTransferController::class, 'list_inter_register_transfer_validations'], true, true);

$router->add('GET', 'validations/transferts/hors_places', [InterUnitTransferController::class, 'list_inter_unit_transfer_validations'], true, true);
$router->add('GET', 'validations/transferts/compte_a_comptes', [InterClientTransferController::class, 'list_inter_client_transfer_validations'], true, true);

$router->add('GET', 'validations/versements', [PaymentController::class, 'list_payments'], true, true);
$router->add('GET', 'validations/retraits', [WithdrawalController::class, 'list_withdrawals'], true, true);

$router->add('GET', 'arretes-de-caisses',        [ClosureController::class, 'index'], true, true);
$router->add('GET', 'arretes-de-caisses/(:num)', [ClosureController::class, 'info'],  true, true);

$router->add('GET', 'regularisations',            [RegularisationController::class, 'index'], true, true);
$router->add('GET', 'regularisations/(:segment)', [RegularisationController::class, 'info'],  true, true);

$router->add('GET', 'rapports/transactions', [ReportController::class, 'transaction'], true, true);