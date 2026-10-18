<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Consultation des ventes (la création et la validation passent par les opérations de caisse). */
final class SaleModel extends BaseModel
{
    protected string $table = 'sales';
    protected bool $autoIncrement = false;
    protected array $fillable = [];

    /** Coupures : colonne => valeur en FCFA. */
    public const DENOMINATIONS = [
        't_10_000' => 10000, 't_5_000' => 5000, 't_2_000' => 2000, 't_1_000' => 1000,
        't_500' => 500, 't_250' => 250, 't_200' => 200, 't_100' => 100,
        't_50' => 50, 't_25' => 25, 't_10' => 10, 't_5' => 5,
    ];

    /**
     * Statut déduit des dates : annulée > en attente > validée (forcée si vente à crédit validée par un responsable).
     * @return array{label:string, class:string}
     */
    public static function status(array $s): array
    {
        if (!empty($s['aborded_at'])) {
            return ['label' => 'Annulée', 'class' => 'danger'];
        }
        if (empty($s['validated_at'])) {
            return ['label' => 'En attente de validation', 'class' => 'warning'];
        }
        if ((int) $s['debt'] === 1) {
            return ['label' => 'Validée forcée', 'class' => 'success'];
        }
        return ['label' => 'Validée', 'class' => 'success'];
    }

    /**
     * @param int[] $unityIds
     * @return array{rows: array, total: int}
     */
    public function search(string $term, array $unityIds, int $page, int $perPage): array
    {
        $from = ' FROM `sales` s
                  LEFT JOIN `clients` c ON c.`id` = s.`client_id`
                  LEFT JOIN `unities` un ON un.`id` = s.`unity_id`
                  LEFT JOIN `cash_registers` cr ON cr.`id` = s.`cash_register_id`
                  LEFT JOIN `users` u ON u.`id` = s.`user_id`
                 WHERE s.`deleted_at` IS NULL';
        $params = [];
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $from .= ' AND (s.`id` LIKE ? OR c.`name` LIKE ? OR CAST(c.`id` AS CHAR) LIKE ? OR c.`phone` LIKE ? OR cr.`name` LIKE ? OR u.`name` LIKE ? OR un.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
        }
        if ($unityIds) {
            $from .= ' AND s.`unity_id` IN (' . implode(',', array_fill(0, count($unityIds), '?')) . ')';
            array_push($params, ...$unityIds);
        }
        $total = (int) Database::run('SELECT COUNT(*)' . $from, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT s.`id`, s.`debt`, s.`savings`, s.`total`, s.`created_at`, s.`updated_at`, s.`validated_at`, s.`aborded_at`,
                    s.`client_id`, c.`name` AS client_name, c.`phone` AS client_phone, c.`email` AS client_email,
                    un.`name` AS unity_name, cr.`name` AS register_name,
                    u.`name` AS cashier_name, u.`email` AS cashier_email, u.`phone` AS cashier_phone'
            . $from . ' ORDER BY s.`created_at` DESC, s.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        // Quantités et montants des lignes, uniquement pour les ventes de la page.
        if ($rows) {
            $ids = array_column($rows, 'id');
            $lines = Database::run(
                'SELECT `sale_id`, SUM(`quantity`) AS qty, SUM(`quantity` * `price`) AS amount, MIN(`price`) AS pmin, MAX(`price`) AS pmax
                   FROM `sale_lines` WHERE `sale_id` IN (' . implode(',', array_fill(0, count($ids), '?')) . ') GROUP BY `sale_id`',
                $ids
            )->fetchAll();
            $by = array_column($lines, null, 'sale_id');
            foreach ($rows as &$r) {
                $l = $by[$r['id']] ?? null;
                $r['qty'] = $l ? (float) $l['qty'] : 0.0;
                $r['amount'] = $l ? (float) $l['amount'] : 0.0;
                $r['pmin'] = $l ? (float) $l['pmin'] : 0.0;
                $r['pmax'] = $l ? (float) $l['pmax'] : 0.0;
            }
            unset($r);
        }

        return ['rows' => $rows, 'total' => $total];
    }

    public function detail(string $id): ?array
    {
        $row = Database::run(
            'SELECT s.*, c.`name` AS client_name, c.`phone` AS client_phone, c.`email` AS client_email,
                    un.`name` AS unity_name, cr.`name` AS register_name,
                    u.`name` AS cashier_name, vb.`name` AS validator_name
               FROM `sales` s
               LEFT JOIN `clients` c ON c.`id` = s.`client_id`
               LEFT JOIN `unities` un ON un.`id` = s.`unity_id`
               LEFT JOIN `cash_registers` cr ON cr.`id` = s.`cash_register_id`
               LEFT JOIN `users` u ON u.`id` = s.`user_id`
               LEFT JOIN `users` vb ON vb.`id` = s.`validated_by`
              WHERE s.`id` = ? AND s.`deleted_at` IS NULL LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }

    public function lines(string $saleId): array
    {
        return Database::run(
            'SELECT `id`, `product_id`, `name`, `price`, `quantity` FROM `sale_lines` WHERE `sale_id` = ? ORDER BY `id` ASC',
            [$saleId]
        )->fetchAll();
    }
}
