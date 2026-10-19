<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Consultation des transferts inter-caisse (table inter_register_transfers : `from` = donneur d'ordre, `to` = bénéficiaire). */
final class InterRegisterTransferModel extends BaseModel
{
    protected string $table = 'inter_register_transfers';
    protected bool $autoIncrement = false;
    protected array $fillable = [];

    public const DENOMINATIONS = [
        't_10_000' => 10000, 't_5_000' => 5000, 't_2_000' => 2000, 't_1_000' => 1000,
        't_500' => 500, 't_250' => 250, 't_200' => 200, 't_100' => 100,
        't_50' => 50, 't_25' => 25, 't_10' => 10, 't_5' => 5,
    ];

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

    private const FROM = ' FROM `inter_register_transfers` t
                  LEFT JOIN `cash_registers` fr ON fr.`id` = t.`from`
                  LEFT JOIN `cash_registers` tr ON tr.`id` = t.`to`
                  LEFT JOIN `users` fu ON fu.`id` = fr.`user_id`
                  LEFT JOIN `users` tu ON tu.`id` = tr.`user_id`
                  LEFT JOIN `unities` un ON un.`id` = fr.`unity_id`
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
            $where .= ' AND (t.`id` LIKE ? OR fr.`name` LIKE ? OR tr.`name` LIKE ? OR fu.`name` LIKE ? OR tu.`name` LIKE ? OR un.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        $total = (int) Database::run('SELECT COUNT(*)' . self::FROM . $where, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT t.`id`, t.`total`, t.`validated_at`, t.`aborded_at`, t.`created_at`, t.`updated_at`,
                    un.`name` AS unity_name, fr.`name` AS from_register, fu.`name` AS from_cashier,
                    tr.`name` AS to_register, tu.`name` AS to_cashier'
            . self::FROM . $where . ' ORDER BY t.`created_at` DESC, t.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    public function detail(string $id): ?array
    {
        $row = Database::run(
            'SELECT t.*, un.`name` AS unity_name, tun.`name` AS to_unity_name,
                    fr.`name` AS from_register, fu.`name` AS from_cashier,
                    tr.`name` AS to_register, tu.`name` AS to_cashier,
                    cu.`name` AS creator_name, vb.`name` AS validator_name
               FROM `inter_register_transfers` t
               LEFT JOIN `cash_registers` fr ON fr.`id` = t.`from`
               LEFT JOIN `cash_registers` tr ON tr.`id` = t.`to`
               LEFT JOIN `users` fu ON fu.`id` = fr.`user_id`
               LEFT JOIN `users` tu ON tu.`id` = tr.`user_id`
               LEFT JOIN `unities` un ON un.`id` = fr.`unity_id`
               LEFT JOIN `unities` tun ON tun.`id` = tr.`unity_id`
               LEFT JOIN `users` cu ON cu.`id` = t.`user_id`
               LEFT JOIN `users` vb ON vb.`id` = t.`validated_by`
              WHERE t.`id` = ? AND t.`deleted_at` IS NULL LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }
}
