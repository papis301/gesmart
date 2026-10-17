<?php
// Configuration prête pour un essai en LOCAL (XAMPP / Laragon / WAMP).
// Copiez ce fichier en "config.php". Ne le déployez pas en production.
return [
    'app' => [
        'name'      => 'GESMART',
        'debug'     => true,
        'timezone'  => 'Africa/Dakar',
        'base_path' => '',               // avec "php -S" ; sous Apache : '/gesmart-php/public'
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'abdoumtgesmart',      // le nom de la base que vous créez en local
        'user' => 'root',
        'pass' => '',                    // vide par défaut sous XAMPP
    ],
    'security' => [
        'session_name'       => 'gesmart_sid',
        'idle_timeout'       => 7200,
        'max_attempts_login' => 5,
        'max_attempts_ip'    => 20,
        'lockout_window'     => 900,
        'trust_proxy'        => false,
        'update_last_login'  => true,
        'rehash_on_login'    => false,
        'csp'                => false,   // désactivé en local pour éviter tout blocage
    ],
    'assets' => ['cdn' => true],
];
