<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Consultation des transferts compte à compte (`from` = compte à créditer, `to` = compte à débiter, comme sur l'ancienne page). */
final class InterClientTransferModel extends BaseModel
{
    protected string $table = 'inter_client_transfers';
    protected array $fillable = [];

    /** @return array{label:string, class:string} */
    public static function status(array $t): array
    {
        if (!empty($t['aborded_at'])) {
            return ['label' => 'Annulée', 'class' => 'danger'];
        }
        if (empty($t['validated_at'])) {
            return ['label' => 'En attente de validation', 'class' => 'warning'];
        }
        return ['label' => 'Validée', 'class' => 'success'];
    }

    private const FROM = ' FROM `inter_client_transfers` t
                  LEFT JOIN `clients` fc ON fc.`id` = t.`from`
                  LEFT JOIN `clients` tc ON tc.`id` = t.`to`
                 WHERE t.`deleted_at` IS NULL';

    /** Transferts ni validés ni annulés. */
    public function pending(string $term, int $page, int $perPage): array
    {
        return $this->search($term, $page, $perPage, true);
    }

    /** @return array{rows: array, total: int} */
    public function search(string $term, int $page, int $perPage, bool $pendingOnly = false): array
    {
        $where = $pendingOnly ? ' AND t.`validated_at` IS NULL AND t.`aborded_at` IS NULL' : '';
        $params = [];
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $where .= ' AND (CAST(t.`id` AS CHAR) LIKE ? OR CAST(t.`from` AS CHAR) LIKE ? OR CAST(t.`to` AS CHAR) LIKE ? OR fc.`name` LIKE ? OR tc.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $total = (int) Database::run('SELECT COUNT(*)' . self::FROM . $where, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT t.`id`, t.`amount`, t.`from`, t.`to`, t.`validated_at`, t.`aborded_at`, t.`created_at`, t.`updated_at`,
                    fc.`name` AS from_name, tc.`name` AS to_name'
            . self::FROM . $where . ' ORDER BY t.`created_at` DESC, t.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    public function detail(int $id): ?array
    {
        $row = Database::run(
            'SELECT t.*, fc.`name` AS from_name, tc.`name` AS to_name,
                    vb.`name` AS validator_name, ab.`name` AS aborter_name, cb.`name` AS confirmer_name
               FROM `inter_client_transfers` t
               LEFT JOIN `clients` fc ON fc.`id` = t.`from`
               LEFT JOIN `clients` tc ON tc.`id` = t.`to`
               LEFT JOIN `users` vb ON vb.`id` = t.`validated_by`
               LEFT JOIN `users` ab ON ab.`id` = t.`aborded_by`
               LEFT JOIN `users` cb ON cb.`id` = t.`confirmed_by`
              WHERE t.`id` = ? AND t.`deleted_at` IS NULL LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }
}
