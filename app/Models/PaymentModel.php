<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Consultation des versements bancaires (table payments). */
final class PaymentModel extends BaseModel
{
    protected string $table = 'payments';
    protected array $fillable = [];

    public const DENOMINATIONS = [
        't_10_000' => 10000, 't_5_000' => 5000, 't_2_000' => 2000, 't_1_000' => 1000,
        't_500' => 500, 't_250' => 250, 't_200' => 200, 't_100' => 100,
        't_50' => 50, 't_25' => 25, 't_10' => 10, 't_5' => 5,
    ];

    /** @return array{label:string, class:string} */
    public static function status(array $p): array
    {
        if (!empty($p['aborded_at'])) {
            return ['label' => 'Annulée', 'class' => 'danger'];
        }
        if (empty($p['validated_at'])) {
            return ['label' => 'En attente de validation', 'class' => 'warning'];
        }
        if (empty($p['confirmed_at'])) {
            return ['label' => 'Validé, en attente de confirmation', 'class' => 'info'];
        }
        return ['label' => 'Validé et confirmé', 'class' => 'success'];
    }

    private const FROM = ' FROM `payments` p
                  LEFT JOIN `users` u ON u.`id` = p.`user_id`
                  LEFT JOIN `cash_registers` cr ON cr.`id` = p.`cash_register_id`
                  LEFT JOIN `banks` b ON b.`id` = p.`bank_id`
                  LEFT JOIN `clients` c ON c.`id` = p.`client_id`
                 WHERE p.`deleted_at` IS NULL';

    /** Versements non annulés, non validés ou validés mais pas encore confirmés. */
    public function pending(string $term, int $page, int $perPage): array
    {
        return $this->search($term, $page, $perPage, true);
    }

    /** @return array{rows: array, total: int} */
    public function search(string $term, int $page, int $perPage, bool $pendingOnly = false): array
    {
        $where = $pendingOnly ? ' AND p.`aborded_at` IS NULL AND (p.`validated_at` IS NULL OR p.`confirmed_at` IS NULL)' : '';
        $params = [];
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $where .= ' AND (CAST(p.`id` AS CHAR) LIKE ? OR p.`desc` LIKE ? OR u.`name` LIKE ? OR cr.`name` LIKE ? OR b.`name` LIKE ? OR b.`id` LIKE ? OR c.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
        }
        $total = (int) Database::run('SELECT COUNT(*)' . self::FROM . $where, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT p.`id`, p.`total`, p.`by_check`, p.`client_id`, p.`bank_id`, p.`validated_at`, p.`aborded_at`, p.`confirmed_at`,
                    p.`created_at`, p.`updated_at`,
                    u.`name` AS user_name, cr.`name` AS register_name, b.`name` AS bank_name, c.`name` AS client_name'
            . self::FROM . $where . ' ORDER BY p.`created_at` DESC, p.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    public function detail(int $id): ?array
    {
        $row = Database::run(
            'SELECT p.*, u.`name` AS user_name, cr.`name` AS register_name, b.`name` AS bank_name, c.`name` AS client_name,
                    vb.`name` AS validator_name, ab.`name` AS aborter_name, cb.`name` AS confirmer_name
               FROM `payments` p
               LEFT JOIN `users` u ON u.`id` = p.`user_id`
               LEFT JOIN `cash_registers` cr ON cr.`id` = p.`cash_register_id`
               LEFT JOIN `banks` b ON b.`id` = p.`bank_id`
               LEFT JOIN `clients` c ON c.`id` = p.`client_id`
               LEFT JOIN `users` vb ON vb.`id` = p.`validated_by`
               LEFT JOIN `users` ab ON ab.`id` = p.`aborded_by`
               LEFT JOIN `users` cb ON cb.`id` = p.`confirmed_by`
              WHERE p.`id` = ? AND p.`deleted_at` IS NULL LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }
}
