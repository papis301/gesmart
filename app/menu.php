<?php
// Structure du menu (identique à l'application d'origine).
// 'ready' => true quand la page existe ; sinon l'entrée est grisée avec « bientôt ».
return [
    'Administration' => [
        ['label' => 'Groupes',               'path' => 'groupes',      'icon' => 'user-shield'],
        ['label' => 'Utilisateurs',          'path' => 'utilisateurs', 'icon' => 'user'],
        ['label' => 'Unité de production',   'path' => 'unites',       'icon' => 'building-factory-2'],
        ['label' => 'Produits',              'path' => 'produits',     'icon' => 'snowflake'],
        ['label' => 'Caisses',               'path' => 'caisses',      'icon' => 'building-store'],
        ['label' => 'Clients',               'path' => 'clients',      'icon' => 'users-group'],
        ['label' => 'Banques',               'path' => 'banques',      'icon' => 'building-bank'],
    ],
    'Front Office' => [
        ['label' => 'Ventes',         'path' => 'ventes',         'icon' => 'invoice'],
        ['label' => 'Encaissements',  'path' => 'encaissements',  'icon' => 'download'],
        ['label' => 'Décaissements',  'path' => 'decaissements',  'icon' => 'upload'],
        ['label' => 'Transferts', 'icon' => 'arrows-transfer-up', 'children' => [
            ['Inter-caisse',    'transferts/inter_caisses'],
            ['Hors places',     'transferts/hors_places'],
            ['Compte à compte', 'transferts/compte_a_comptes'],
        ]],
        ['label' => 'Op. bancaires', 'icon' => 'cash-banknote', 'children' => [
            ['Versements', 'versements'],
            ['Retraits',   'retraits'],
        ]],
    ],
    'Back Office' => [
        ['label' => 'Validations', 'icon' => 'check', 'children' => [
            ['Ventes',                    'validations/ventes'],
            ['Décaissements',             'validations/decaissements'],
            ['Transferts inter-caisse',   'validations/transferts/inter_caisses'],
            ['Transferts hors place',     'validations/transferts/hors_places'],
            ['Transferts compte à compte','validations/transferts/compte_a_comptes'],
            ['Versements bancaires',      'validations/versements'],
            ['Retraits bancaires',        'validations/retraits'],
        ]],
        ['label' => 'Arrêtés de caisse', 'path' => 'arretes-de-caisses', 'icon' => 'cash-register'],
        ['label' => 'S.P.I.',            'path' => 'regularisations',    'icon' => 'user-check'],
        ['label' => 'Rapports', 'icon' => 'report', 'children' => [
            ['Transactions',                              'rapports/transactions'],
            ['Relevé de compte client',                   'rapports/releves-de-compte'],
            ['Historique de caisse',                      'rapports/historique-de-caisse'],
            ['Relevé de compte bancaire',                 'rapports/historique-de-compte-bancaire'],
            ['Situation globale',                         'rapports/situation-globale'],
            ['Etat des sorties de caisse',                'rapports/etat-des-charges'],
            ['Etat de la trésorerie par Unité de production', 'rapports/etat-de-la-tresorerie-par-point'],
        ]],
    ],
];
