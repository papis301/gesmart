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

    /** @return array{rows: array, total: int} */
    public function search(string $term, int $page, int $perPage): array
    {
        $from = ' FROM `users` u
                  LEFT JOIN `groups` g ON g.`id` = u.`group_id`
                  LEFT JOIN `unities` un ON un.`id` = u.`unity_id`
                 WHERE u.`deleted_at` IS NULL';
        $params = [];
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $from .= ' AND (u.`name` LIKE ? OR u.`login` LIKE ? OR u.`email` LIKE ? OR u.`phone` LIKE ? OR g.`name` LIKE ? OR un.`name` LIKE ?)';
            $params = [$like, $like, $like, $like, $like, $like];
        }
        $total = (int) Database::run('SELECT COUNT(*)' . $from, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT u.`id`, u.`name`, u.`login`, u.`email`, u.`phone`, u.`suspended`, u.`created_at`, u.`updated_at`,
                    g.`name` AS group_name, un.`name` AS unity_name' . $from
            . ' ORDER BY u.`name` ASC, u.`id` ASC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    /** Vérifie l'unicité (les comptes supprimés logiquement comptent : les index sont UNIQUE). */
    public function taken(string $column, string $value, ?int $exceptId = null): bool
    {
        if (!in_array($column, ['login', 'email', 'phone'], true)) {
            throw new \InvalidArgumentException('Colonne non autorisée.');
        }
        $sql = "SELECT COUNT(*) FROM `users` WHERE `$column` = ?";
        $params = [$value];
        if ($exceptId !== null) {
            $sql .= ' AND `id` <> ?';
            $params[] = $exceptId;
        }
        return (int) Database::run($sql, $params)->fetchColumn() > 0;
    }

    /** Dernières actions enregistrées dans le journal pour cet utilisateur. */
    public function recentLogs(int $userId, int $limit = 10): array
    {
        return Database::run(
            'SELECT `desc`, `ip`, `created_at` FROM `logs` WHERE `user_id` = ? ORDER BY `id` DESC LIMIT ' . (int) $limit,
            [$userId]
        )->fetchAll();
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