<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Arrêtés de caisse (table closures).
 * `total` = solde informatique, `total_real` = encaisse physique comptée,
 * `user_id` = caissier de la caisse, `by` = auteur de l'arrêté.
 */
final class ClosureModel extends BaseModel
{
    protected string $table = 'closures';
    protected bool $autoIncrement = false;
    protected array $fillable = [];

    public const DENOMINATIONS = [
        't_10_000' => 10000, 't_5_000' => 5000, 't_2_000' => 2000, 't_1_000' => 1000,
        't_500' => 500, 't_250' => 250, 't_200' => 200, 't_100' => 100,
        't_50' => 50, 't_25' => 25, 't_10' => 10, 't_5' => 5,
    ];

    /** Écart d'encaisse : encaisse physique - solde informatique (positif = excédent). */
    public static function gap(array $c): float
    {
        return (float) $c['total_real'] - (float) $c['total'];
    }

    private const FROM = ' FROM `closures` c
                  LEFT JOIN `cash_registers` cr ON cr.`id` = c.`cash_register_id`
                  LEFT JOIN `unities` un ON un.`id` = cr.`unity_id`
                  LEFT JOIN `users` cu ON cu.`id` = c.`user_id`
                  LEFT JOIN `users` au ON au.`id` = c.`by`
                 WHERE c.`deleted_at` IS NULL';

    /** @return array{rows: array, total: int} */
    public function search(string $term, int $page, int $perPage): array
    {
        $where = '';
        $params = [];
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $where = ' AND (c.`id` LIKE ? OR cr.`name` LIKE ? OR un.`name` LIKE ? OR cu.`name` LIKE ? OR au.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $total = (int) Database::run('SELECT COUNT(*)' . self::FROM . $where, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT c.`id`, c.`total`, c.`total_real`, c.`created_at`, c.`updated_at`,
                    cr.`name` AS register_name, un.`name` AS unity_name,
                    cu.`name` AS cashier_name, cu.`email` AS cashier_email, cu.`phone` AS cashier_phone,
                    au.`name` AS author_name, au.`email` AS author_email, au.`phone` AS author_phone'
            . self::FROM . $where . ' ORDER BY c.`created_at` DESC, c.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    public function detail(string $id): ?array
    {
        $row = Database::run(
            'SELECT c.*, cr.`name` AS register_name, un.`name` AS unity_name,
                    cu.`name` AS cashier_name, cu.`email` AS cashier_email, cu.`phone` AS cashier_phone,
                    au.`name` AS author_name, au.`email` AS author_email, au.`phone` AS author_phone'
            . self::FROM . ' AND c.`id` = ? LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }
}
