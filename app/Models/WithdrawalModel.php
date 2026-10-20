<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Consultation des retraits bancaires (table withdrawals). */
final class WithdrawalModel extends BaseModel
{
    protected string $table = 'withdrawals';
    protected array $fillable = [];

    /** Nature de l'opération (colonne type). */
    public const TYPES = [
        'EXP'  => "Charge d'exploitation",
        'NEXP' => 'Charge hors exploitation',
        'INV'  => 'Investissements',
        'REG'  => 'Régularisation',
    ];

    /** @return array{label:string, class:string} */
    public static function status(array $w): array
    {
        if (!empty($w['aborded_at'])) {
            return ['label' => 'Annulé', 'class' => 'danger'];
        }
        if (empty($w['validated_at'])) {
            return ['label' => 'En attente de validation', 'class' => 'warning'];
        }
        if (empty($w['confirmed_at'])) {
            return ['label' => 'Validé, en attente de confirmation', 'class' => 'info'];
        }
        return ['label' => 'Validé et confirmé', 'class' => 'success'];
    }

    public static function type(?string $code): string
    {
        return $code !== null && $code !== '' ? (self::TYPES[$code] ?? $code) : '--';
    }

    private const FROM = ' FROM `withdrawals` w
                  LEFT JOIN `users` u ON u.`id` = w.`user_id`
                  LEFT JOIN `banks` fb ON fb.`id` = w.`from_bank_id`
                  LEFT JOIN `banks` tb ON tb.`id` = w.`to_bank_id`
                  LEFT JOIN `clients` c ON c.`id` = w.`client_id`
                 WHERE w.`deleted_at` IS NULL';

    /** Retraits non annulés, non validés ou validés mais pas encore confirmés. */
    public function pending(string $term, int $page, int $perPage): array
    {
        return $this->search($term, $page, $perPage, true);
    }

    /** @return array{rows: array, total: int} */
    public function search(string $term, int $page, int $perPage, bool $pendingOnly = false): array
    {
        $where = $pendingOnly ? ' AND w.`aborded_at` IS NULL AND (w.`validated_at` IS NULL OR w.`confirmed_at` IS NULL)' : '';
        $params = [];
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $where .= ' AND (CAST(w.`id` AS CHAR) LIKE ? OR w.`desc` LIKE ? OR u.`name` LIKE ? OR fb.`name` LIKE ? OR fb.`id` LIKE ? OR tb.`name` LIKE ? OR c.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
        }
        $total = (int) Database::run('SELECT COUNT(*)' . self::FROM . $where, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT w.`id`, w.`type`, w.`total`, w.`check_file`, w.`from_bank_id`, w.`to_bank_id`, w.`client_id`,
                    w.`validated_at`, w.`aborded_at`, w.`confirmed_at`, w.`created_at`, w.`updated_at`,
                    u.`name` AS user_name, fb.`name` AS from_bank_name, tb.`name` AS to_bank_name, c.`name` AS client_name'
            . self::FROM . $where . ' ORDER BY w.`created_at` DESC, w.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    public function detail(int $id): ?array
    {
        $row = Database::run(
            'SELECT w.*, u.`name` AS user_name, fb.`name` AS from_bank_name, tb.`name` AS to_bank_name, c.`name` AS client_name,
                    vb.`name` AS validator_name, ab.`name` AS aborter_name, cb.`name` AS confirmer_name
               FROM `withdrawals` w
               LEFT JOIN `users` u ON u.`id` = w.`user_id`
               LEFT JOIN `banks` fb ON fb.`id` = w.`from_bank_id`
               LEFT JOIN `banks` tb ON tb.`id` = w.`to_bank_id`
               LEFT JOIN `clients` c ON c.`id` = w.`client_id`
               LEFT JOIN `users` vb ON vb.`id` = w.`validated_by`
               LEFT JOIN `users` ab ON ab.`id` = w.`aborded_by`
               LEFT JOIN `users` cb ON cb.`id` = w.`confirmed_by`
              WHERE w.`id` = ? AND w.`deleted_at` IS NULL LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }
}
