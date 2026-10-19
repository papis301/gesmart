<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Consultation des encaissements (table `collections`). Aucune écriture : créer ou annuler un encaissement
 * modifie la caisse, ses coupures et le compte du client.
 */
final class CollectionModel extends BaseModel
{
    protected string $table = 'collections';
    protected bool $autoIncrement = false;
    protected array $fillable = [];

    /** Natures d'encaissement (colonne `type`). Les anciens enregistrements n'ont pas de type. */
    public const TYPES = [
        'CRE'     => 'Remboursement de crédit',
        'FAG'     => 'Fagarou',
        'REG_COL' => 'Régularisation',
        'OTH'     => 'Autre',
    ];
    public const STATUSES = ['done' => 'Effectué', 'aborted' => 'Annulé'];

    private function scope(array $f, array &$params): string
    {
        $sql = ' FROM `collections` co
                  LEFT JOIN `unities` un ON un.`id` = co.`unity_id`
                  LEFT JOIN `cash_registers` cr ON cr.`id` = co.`cash_register_id`
                  LEFT JOIN `users` u ON u.`id` = co.`user_id`
                  LEFT JOIN `clients` c ON c.`id` = co.`client_id`
                 WHERE co.`deleted_at` IS NULL';

        if (($f['term'] ?? '') !== '') {
            $like = '%' . addcslashes((string) $f['term'], '%_\\') . '%';
            $sql .= ' AND (co.`id` LIKE ? OR co.`description` LIKE ? OR CAST(co.`received` AS CHAR) LIKE ? OR c.`name` LIKE ? OR co.`sale_id` LIKE ? OR co.`depositary_id` LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        if (!empty($f['unity_id'])) {
            $sql .= ' AND co.`unity_id` = ?';
            $params[] = (int) $f['unity_id'];
        }
        if (!empty($f['cash_register_id'])) {
            $sql .= ' AND co.`cash_register_id` = ?';
            $params[] = (int) $f['cash_register_id'];
        }
        if (($f['type'] ?? '') !== '') {
            $sql .= ' AND co.`type` = ?';
            $params[] = $f['type'];
        }
        if (($f['status'] ?? '') === 'aborted') {
            $sql .= ' AND co.`aborded_at` IS NOT NULL';
        } elseif (($f['status'] ?? '') === 'done') {
            $sql .= ' AND co.`aborded_at` IS NULL';
        }
        if (!empty($f['from'])) {
            $sql .= ' AND co.`created_at` >= ?';
            $params[] = $f['from'] . ' 00:00:00';
        }
        if (!empty($f['to'])) {
            $sql .= ' AND co.`created_at` <= ?';
            $params[] = $f['to'] . ' 23:59:59';
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
        // Le montant encaissé est le reçu moins le rendu ; les annulations sont exclues du total.
        $sum = (float) Database::run(
            'SELECT COALESCE(SUM(co.`received` - co.`returned`), 0)' . $from . ' AND co.`aborded_at` IS NULL',
            $params
        )->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT co.`id`, co.`type`, co.`description`, co.`received`, co.`returned`, co.`created_at`, co.`updated_at`, co.`aborded_at`,
                    co.`client_id`, un.`name` AS unity_name, cr.`name` AS register_name,
                    u.`name` AS user_name, u.`email` AS user_email, u.`phone` AS user_phone,
                    c.`name` AS client_name, c.`email` AS client_email, c.`phone` AS client_phone'
            . $from . ' ORDER BY co.`created_at` DESC, co.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $count, 'sum_amount' => $sum];
    }

    public function detail(string $id): ?array
    {
        $row = Database::run(
            'SELECT co.*, un.`name` AS unity_name, cr.`name` AS register_name, u.`name` AS user_name,
                    c.`name` AS client_name, v.`name` AS validator_name
               FROM `collections` co
               LEFT JOIN `unities` un ON un.`id` = co.`unity_id`
               LEFT JOIN `cash_registers` cr ON cr.`id` = co.`cash_register_id`
               LEFT JOIN `users` u ON u.`id` = co.`user_id`
               LEFT JOIN `clients` c ON c.`id` = co.`client_id`
               LEFT JOIN `users` v ON v.`id` = co.`validated_by`
              WHERE co.`id` = ? AND co.`deleted_at` IS NULL LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }

    /** Caisses (pour le filtre de la liste). */
    public function registers(): array
    {
        return Database::run('SELECT `id`, `name` FROM `cash_registers` WHERE `deleted_at` IS NULL ORDER BY `name` ASC')->fetchAll();
    }

    /** Libellé de la nature : « — » pour les anciens encaissements sans type. */
    public static function typeLabel(?string $type): string
    {
        return self::TYPES[(string) $type] ?? '--';
    }
}
