<?php
// Copiez ce fichier en "config.php" puis renseignez vos valeurs.
// config.php ne doit JAMAIS être public ni envoyé par email.
return [
    'app' => [
        'name'      => 'GESMART',
        'debug'     => false,            // true seulement en développement
        'timezone'  => 'Africa/Dakar',
        'base_path' => '',               // '' si le site est à la racine du domaine, sinon '/sous-dossier'
    ],
    'db' => [
        'host' => 'abdoumtgesmart.mysql.db',
        'port' => 3306,
        'name' => 'abdoumtgesmart',
        'user' => 'abdoumtgesmart',
        'pass' => 'VOTRE_MOT_DE_PASSE_MYSQL',
    ],
    'security' => [
        'session_name'       => 'gesmart_sid',
        'idle_timeout'       => 7200,    // déconnexion après 2 h d'inactivité (secondes)
        'max_attempts_login' => 5,       // essais échoués par identifiant ...
        'max_attempts_ip'    => 20,      // ... et par adresse IP
        'lockout_window'     => 900,     // ... sur 15 minutes (secondes)
        'trust_proxy'        => false,   // true seulement derrière un proxy de confiance
        'update_last_login'  => true,    // renseigne users.last_login_at à chaque connexion
        // IMPORTANT : tant que l'ancienne application utilise la même base, laissez false.
        // Sinon les mots de passe convertis ne fonctionneraient plus dans l'ancienne application.
        'rehash_on_login'    => false,
        'csp'                => true,    // en-tête Content-Security-Policy
    ],
    'assets' => [
        'cdn' => true,                   // true : Tabler chargé depuis jsDelivr ; false : fichiers dans public/assets/
    ],
];
