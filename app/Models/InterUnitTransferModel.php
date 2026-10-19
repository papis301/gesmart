<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Transferts hors place (table `inter_unit_transfers`) : consultation uniquement.
 * `from` et `to` sont des caisses ; l'unité de production d'un transfert est celle de la caisse émettrice.
 * Valider ou annuler un transfert déplace de l'argent entre deux caisses : ce n'est pas repris ici.
 */
final class InterUnitTransferModel extends BaseModel
{
    protected string $table = 'inter_unit_transfers';
    protected bool $autoIncrement = false;
    protected array $fillable = [];

    /** Annulé si aborded_at ; validé si validated_at ; sinon en attente de validation. */
    public const STATUSES = ['pending' => 'En attente de validation', 'validated' => 'Validé', 'aborted' => 'Annulé'];

    private function scope(array $f, array &$params): string
    {
        $sql = ' FROM `inter_unit_transfers` t
                  LEFT JOIN `cash_registers` fr ON fr.`id` = t.`from`
                  LEFT JOIN `cash_registers` tr ON tr.`id` = t.`to`
                  LEFT JOIN `unities` un ON un.`id` = fr.`unity_id`
                  LEFT JOIN `users` u ON u.`id` = t.`user_id`
                  LEFT JOIN `users` tu ON tu.`id` = tr.`user_id`
                 WHERE t.`deleted_at` IS NULL';

        if (($f['term'] ?? '') !== '') {
            $like = '%' . addcslashes((string) $f['term'], '%_\\') . '%';
            $sql .= ' AND (t.`id` LIKE ? OR CAST(t.`total` AS CHAR) LIKE ? OR u.`name` LIKE ? OR fr.`name` LIKE ? OR tr.`name` LIKE ? OR tu.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        if (!empty($f['unity_id'])) {
            $sql .= ' AND fr.`unity_id` = ?';
            $params[] = (int) $f['unity_id'];
        }
        switch ($f['status'] ?? '') {
            case 'aborted':
                $sql .= ' AND t.`aborded_at` IS NOT NULL';
                break;
            case 'validated':
                $sql .= ' AND t.`validated_at` IS NOT NULL AND t.`aborded_at` IS NULL';
                break;
            case 'pending':
                $sql .= ' AND t.`validated_at` IS NULL AND t.`aborded_at` IS NULL';
                break;
        }
        if (!empty($f['from_date'])) {
            $sql .= ' AND t.`created_at` >= ?';
            $params[] = $f['from_date'] . ' 00:00:00';
        }
        if (!empty($f['to_date'])) {
            $sql .= ' AND t.`created_at` <= ?';
            $params[] = $f['to_date'] . ' 23:59:59';
        }
        return $sql;
    }

    /**
     * @return array{rows: array, total: int, sum_amount: float}
     */
    public function search(array $filters, int $page, int $perPage): array
    {
        $params = [];
        $from = $this->scope($filters, $params);

        $count = (int) Database::run('SELECT COUNT(*)' . $from, $params)->fetchColumn();
        $sum = (float) Database::run(
            'SELECT COALESCE(SUM(t.`total`), 0)' . $from . ' AND t.`aborded_at` IS NULL',
            $params
        )->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT t.`id`, t.`total`, t.`created_at`, t.`updated_at`, t.`validated_at`, t.`aborded_at`,
                    un.`name` AS unity_name, fr.`name` AS from_name, tr.`name` AS to_name,
                    u.`name` AS user_name, u.`email` AS user_email, u.`phone` AS user_phone,
                    tu.`name` AS to_user_name, tu.`email` AS to_user_email, tu.`phone` AS to_user_phone'
            . $from . ' ORDER BY t.`created_at` DESC, t.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $count, 'sum_amount' => $sum];
    }

    public function detail(string $id): ?array
    {
        $row = Database::run(
            'SELECT t.*, fr.`name` AS from_name, tr.`name` AS to_name,
                    un.`name` AS unity_name, tun.`name` AS to_unity_name,
                    u.`name` AS user_name, tu.`name` AS to_user_name, v.`name` AS validator_name
               FROM `inter_unit_transfers` t
               LEFT JOIN `cash_registers` fr ON fr.`id` = t.`from`
               LEFT JOIN `cash_registers` tr ON tr.`id` = t.`to`
               LEFT JOIN `unities` un ON un.`id` = fr.`unity_id`
               LEFT JOIN `unities` tun ON tun.`id` = tr.`unity_id`
               LEFT JOIN `users` u ON u.`id` = t.`user_id`
               LEFT JOIN `users` tu ON tu.`id` = tr.`user_id`
               LEFT JOIN `users` v ON v.`id` = t.`validated_by`
              WHERE t.`id` = ? AND t.`deleted_at` IS NULL LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }

    /** 'aborted' | 'validated' | 'pending' */
    public static function status(array $row): string
    {
        if (!empty($row['aborded_at'])) {
            return 'aborted';
        }
        return !empty($row['validated_at']) ? 'validated' : 'pending';
    }
}
