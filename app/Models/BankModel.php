<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class BankModel extends BaseModel
{
    protected string $table = 'banks';
    /** Le Nº de compte (id) est saisi par l'utilisateur. */
    protected bool $autoIncrement = false;
    /** Le solde, le crédit et la garantie ne changent ensuite que par les opérations bancaires. */
    protected array $fillable = ['name', 'email', 'phone', 'balance', 'credit', 'warranty', 'user_id', 'admin_user_id'];

    /** Tables d'historique qui référencent un compte : libellé => [table, colonnes]. */
    private const HISTORY = [
        'Versements' => ['payments', ['bank_id']],
        'Retraits / transferts' => ['withdrawals', ['from_bank_id', 'to_bank_id']],
    ];

    /** @return array{rows: array, total: int} */
    public function search(string $term, array $unityIds, int $page, int $perPage): array
    {
        $from = ' FROM `banks` b
                  LEFT JOIN `users` au ON au.`id` = b.`admin_user_id`
                  LEFT JOIN `users` cu ON cu.`id` = b.`user_id`
                 WHERE b.`deleted_at` IS NULL';
        $params = [];
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $from .= ' AND (b.`id` LIKE ? OR b.`name` LIKE ? OR au.`name` LIKE ? OR cu.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if ($unityIds) {
            $from .= ' AND b.`id` IN (SELECT `bank_id` FROM `bank_unity_links` WHERE `unity_id` IN ('
                . implode(',', array_fill(0, count($unityIds), '?')) . '))';
            array_push($params, ...$unityIds);
        }
        $total = (int) Database::run('SELECT COUNT(*)' . $from, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT b.`id`, b.`name`, b.`email`, b.`phone`, b.`balance`, b.`credit`, b.`warranty`, b.`created_at`, b.`updated_at`,
                    au.`name` AS manager_name, au.`email` AS manager_email, au.`phone` AS manager_phone,
                    cu.`name` AS agent_name, cu.`email` AS agent_email, cu.`phone` AS agent_phone,
                    (SELECT SUM(bc.`overdraft`) FROM `bank_credits` bc
                      WHERE bc.`bank_id` = b.`id` AND bc.`is_overdraft` = 1 AND bc.`deleted_at` IS NULL) AS authorization_amount'
            . $from . ' ORDER BY b.`created_at` DESC, b.`id` ASC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    public function detail(string $id): ?array
    {
        $row = Database::run(
            'SELECT b.*, au.`name` AS manager_name, cu.`name` AS agent_name
               FROM `banks` b
               LEFT JOIN `users` au ON au.`id` = b.`admin_user_id`
               LEFT JOIN `users` cu ON cu.`id` = b.`user_id`
              WHERE b.`id` = ? AND b.`deleted_at` IS NULL LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }

    /** Le Nº de compte est la clé primaire : on teste aussi les comptes supprimés. */
    public function idTaken(string $id): bool
    {
        return (int) Database::run('SELECT COUNT(*) FROM `banks` WHERE `id` = ?', [$id])->fetchColumn() > 0;
    }

    public function nameTaken(string $name, ?string $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM `banks` WHERE `deleted_at` IS NULL AND `name` = ?';
        $params = [$name];
        if ($exceptId !== null) {
            $sql .= ' AND `id` <> ?';
            $params[] = $exceptId;
        }
        return (int) Database::run($sql, $params)->fetchColumn() > 0;
    }

    /** Utilisateurs actifs affectables ; $keep : valeurs déjà enregistrées, conservées même si suspendues. */
    public function assignableUsers(array $keep = []): array
    {
        $keep = array_values(array_filter(array_map('intval', $keep)));
        $sql = 'SELECT u.`id`, u.`name`, g.`name` AS group_name, un.`name` AS unity_name
                  FROM `users` u
                  LEFT JOIN `groups` g ON g.`id` = u.`group_id`
                  LEFT JOIN `unities` un ON un.`id` = u.`unity_id`
                 WHERE u.`deleted_at` IS NULL AND (COALESCE(u.`suspended`, 0) = 0';
        $params = [];
        if ($keep) {
            $sql .= ' OR u.`id` IN (' . implode(',', array_fill(0, count($keep), '?')) . ')';
            $params = $keep;
        }
        $sql .= ') ORDER BY u.`name` ASC, u.`id` ASC';
        return Database::run($sql, $params)->fetchAll();
    }

    public function userIsActive(int $userId): bool
    {
        return (int) Database::run(
            'SELECT COUNT(*) FROM `users` WHERE `id` = ? AND `deleted_at` IS NULL AND COALESCE(`suspended`, 0) = 0',
            [$userId]
        )->fetchColumn() > 0;
    }

    /** @return array<int, array{id:int,unity_id:int,unity_name:string}> */
    public function unityLinks(string $bankId): array
    {
        return Database::run(
            'SELECT l.`id`, l.`unity_id`, un.`name` AS unity_name
               FROM `bank_unity_links` l
               JOIN `unities` un ON un.`id` = l.`unity_id` AND un.`deleted_at` IS NULL
              WHERE l.`bank_id` = ? ORDER BY un.`name` ASC',
            [$bankId]
        )->fetchAll();
    }

    public function linkExists(string $bankId, int $unityId): bool
    {
        return (int) Database::run(
            'SELECT COUNT(*) FROM `bank_unity_links` WHERE `bank_id` = ? AND `unity_id` = ?',
            [$bankId, $unityId]
        )->fetchColumn() > 0;
    }

    public function addLink(string $bankId, int $unityId): void
    {
        Database::run('INSERT INTO `bank_unity_links` (`bank_id`, `unity_id`) VALUES (?, ?)', [$bankId, $unityId]);
    }

    public function removeLink(string $bankId, int $linkId): int
    {
        return Database::run('DELETE FROM `bank_unity_links` WHERE `id` = ? AND `bank_id` = ?', [$linkId, $bankId])->rowCount();
    }

    /** @return array<int, array> lignes de crédit / découvert, plus récentes d'abord */
    public function credits(string $bankId): array
    {
        return Database::run(
            'SELECT `id`, `credit`, `warranty`, `overdraft`, `is_overdraft`, `created_at`
               FROM `bank_credits` WHERE `bank_id` = ? AND `deleted_at` IS NULL ORDER BY `created_at` DESC, `id` DESC',
            [$bankId]
        )->fetchAll();
    }

    /** @return array<string, int> libellé => nombre d'opérations sur le compte */
    public function history(string $bankId): array
    {
        $out = [];
        foreach (self::HISTORY as $label => [$table, $cols]) {
            $where = implode(' OR ', array_map(static fn($c) => "`$c` = ?", $cols));
            $out[$label] = (int) Database::run(
                "SELECT COUNT(*) FROM `$table` WHERE $where",
                array_fill(0, count($cols), $bankId)
            )->fetchColumn();
        }
        return $out;
    }
}
