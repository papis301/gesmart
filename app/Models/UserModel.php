<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class UserModel extends BaseModel
{
    protected string $table = 'users';
    protected array $fillable = ['group_id', 'name', 'login', 'password', 'email', 'phone', 'last_login_at', 'unity_id', 'suspended'];

    public function findByLogin(string $login): ?array
    {
        $row = Database::run(
            'SELECT * FROM `users` WHERE `login` = ? AND `deleted_at` IS NULL LIMIT 1',
            [$login]
        )->fetch();
        return $row ?: null;
    }

    /** Utilisateur actif (ni supprimé, ni suspendu, avec un groupe) enrichi du nom du groupe et de l'unité. */
    public static function findActive(int $id): ?array
    {
        $row = Database::run(
            'SELECT u.*, g.`name` AS group_name, un.`name` AS unity_name
               FROM `users` u
               LEFT JOIN `groups` g ON g.`id` = u.`group_id` AND g.`deleted_at` IS NULL
               LEFT JOIN `unities` un ON un.`id` = u.`unity_id`
              WHERE u.`id` = ? AND u.`deleted_at` IS NULL
                AND COALESCE(u.`suspended`, 0) = 0 AND u.`group_id` IS NOT NULL
              LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }
}
