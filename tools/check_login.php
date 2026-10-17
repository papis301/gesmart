<?php
declare(strict_types=1);

// Diagnostic de connexion (ligne de commande uniquement, ne modifie rien).
// Usage : php tools/check_login.php LOGIN
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Password;

$login = strtoupper(trim((string) ($argv[1] ?? '')));
if ($login === '') {
    exit("Usage : php tools/check_login.php LOGIN\n");
}
$u = Database::run('SELECT `id`,`login`,`name`,`password`,`suspended`,`group_id`,`deleted_at` FROM `users` WHERE `login` = ? LIMIT 1', [$login])->fetch();
if (!$u) {
    exit("Aucun utilisateur avec le login $login.\n");
}
$hash = (string) $u['password'];
$format = Password::isLegacy($hash) ? 'SHA-1 (40 caractères hexadécimaux)'
    : (password_get_info($hash)['algoName'] !== 'unknown' ? 'password_hash (' . password_get_info($hash)['algoName'] . ')' : 'format inconnu (' . strlen($hash) . ' caractères)');

echo "Utilisateur : {$u['name']} (id {$u['id']})\n";
echo 'Suspendu    : ' . (empty($u['suspended']) ? 'non' : 'OUI') . "\n";
echo 'Supprimé    : ' . ($u['deleted_at'] === null ? 'non' : 'OUI') . "\n";
echo 'Groupe      : ' . ($u['group_id'] ?? 'AUCUN') . "\n";
echo "Format du mot de passe : $format\n\n";

echo 'Saisissez le mot de passe à tester (il s\'affichera à l\'écran) : ';
$plain = rtrim((string) fgets(STDIN), "\r\n");
echo Password::verify($plain, $hash)
    ? "=> CORRESPOND : la connexion fonctionnera avec ce mot de passe.\n"
    : "=> NE CORRESPOND PAS. Mauvais mot de passe, ou l'ancienne application hachait autrement (sel, md5...).\n";
