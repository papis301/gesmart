<?php
declare(strict_types=1);

// Vérifie la connexion MySQL et la présence des tables utilisées par la connexion.
// Usage (ligne de commande) : php tools/check_db.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

try {
    $pdo = Database::pdo();
    echo "Connexion MySQL : OK\n";
    foreach (['users', 'groups', 'rules', 'logs', 'unities'] as $table) {
        $n = (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo sprintf("  %-8s %d ligne(s)\n", $table, $n);
    }
    $legacy = (int) $pdo->query("SELECT COUNT(*) FROM `users` WHERE `password` REGEXP '^[a-fA-F0-9]{40}$'")->fetchColumn();
    $total = (int) $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    echo "Mots de passe au format SHA-1 : $legacy sur $total\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'ERREUR : ' . $e->getMessage() . "\n");
    exit(1);
}
