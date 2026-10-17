<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class UnityModel extends BaseModel
{
    protected string $table = 'unities';
    protected array $fillable = ['name'];

    /** @return array<int, array{id:string,name:string}> unités actives, par ordre alphabétique */
    public function activeList(): array
    {
        return Database::run('SELECT `id`, `name` FROM `unities` WHERE `deleted_at` IS NULL ORDER BY `name` ASC')->fetchAll();
    }

    public function exists(int $id): bool
    {
        return (int) Database::run('SELECT COUNT(*) FROM `unities` WHERE `id` = ? AND `deleted_at` IS NULL', [$id])->fetchColumn() > 0;
    }

    /** @return array{rows: array, total: int} */
    public function search(string $term, int $page, int $perPage): array
    {
        $where = ' WHERE `deleted_at` IS NULL';
        $params = [];
        if ($term !== '') {
            $where .= ' AND `name` LIKE ?';
            $params[] = '%' . addcslashes($term, '%_\\') . '%';
        }
        $total = (int) Database::run("SELECT COUNT(*) FROM `unities`$where", $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            "SELECT `id`, `name`, `created_at`, `updated_at` FROM `unities`$where ORDER BY `name` ASC, `id` ASC LIMIT "
            . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    /** La table n'a pas d'index unique sur le nom : on évite les doublons côté application. */
    public function nameTaken(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM `unities` WHERE `deleted_at` IS NULL AND `name` = ?';
        $params = [$name];
        if ($exceptId !== null) {
            $sql .= ' AND `id` <> ?';
            $params[] = $exceptId;
        }
        return (int) Database::run($sql, $params)->fetchColumn() > 0;
    }

    /**
     * Éléments actifs rattachés à l'unité. L'historique (ventes, encaissements...) n'est pas compté :
     * il est conservé même si l'unité est supprimée.
     *
     * @return array<string, int> libellé => nombre
     */
    public function usage(int $id): array
    {
        $count = static fn(string $sql): int => (int) Database::run($sql, [$id])->fetchColumn();

        return [
            'Utilisateurs'     => $count('SELECT COUNT(*) FROM `users` WHERE `unity_id` = ? AND `deleted_at` IS NULL'),
            'Caisses'          => $count('SELECT COUNT(*) FROM `cash_registers` WHERE `unity_id` = ? AND `deleted_at` IS NULL'),
            'Clients'          => $count('SELECT COUNT(*) FROM `clients` WHERE `unity_id` = ? AND `deleted_at` IS NULL'),
            'Produits'         => $count('SELECT COUNT(*) FROM `products` WHERE `unity_id` = ? AND `deleted_at` IS NULL'),
            'Comptes bancaires liés' => $count('SELECT COUNT(*) FROM `bank_unity_links` WHERE `unity_id` = ?'),
        ];
    }
}
