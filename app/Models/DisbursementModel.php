<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Décaissements (sorties d'argent d'une caisse) : file de validation et consultation. */
final class DisbursementModel extends BaseModel
{
    protected string $table = 'disbursements';
    protected bool $autoIncrement = false;
    protected array $fillable = [];

    public const DENOMINATIONS = [
        't_10_000' => 10000, 't_5_000' => 5000, 't_2_000' => 2000, 't_1_000' => 1000,
        't_500' => 500, 't_250' => 250, 't_200' => 200, 't_100' => 100,
        't_50' => 50, 't_25' => 25, 't_10' => 10, 't_5' => 5,
    ];

    /** Nature de l'opération (colonne type). */
    public const TYPES = [
        'EXP'  => "Charge d'exploitation",
        'NEXP' => 'Charge hors exploitation',
        'INV'  => 'Investissements',
        'REG'  => 'Régularisation',
    ];

    public static function type(?string $code): string
    {
        return $code !== null && $code !== '' ? (self::TYPES[$code] ?? $code) : '--';
    }

    /** Montant décaissé : somme sortie de la caisse (returned), sinon somme reçue. */
    public static function amount(array $d): float
    {
        return (float) $d['returned'] > 0 ? (float) $d['returned'] : (float) $d['received'];
    }

    /** @return array{label:string, class:string} */
    public static function status(array $d): array
    {
        if (!empty($d['aborded_at'])) {
            return ['label' => 'Annulé', 'class' => 'danger'];
        }
        if (empty($d['validated_at'])) {
            return ['label' => 'En attente de validation', 'class' => 'warning'];
        }
        return ['label' => 'Validé', 'class' => 'success'];
    }

    /** @return array{rows: array, total: int} décaissements ni validés ni annulés */
    public function pending(string $term, int $page, int $perPage): array
    {
        $from = ' FROM `disbursements` d
                  LEFT JOIN `clients` c ON c.`id` = d.`client_id`
                  LEFT JOIN `unities` un ON un.`id` = d.`unity_id`
                  LEFT JOIN `cash_registers` cr ON cr.`id` = d.`cash_register_id`
                  LEFT JOIN `users` u ON u.`id` = d.`user_id`
                 WHERE d.`deleted_at` IS NULL AND d.`validated_at` IS NULL AND d.`aborded_at` IS NULL';
        $params = [];
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $from .= ' AND (d.`id` LIKE ? OR d.`description` LIKE ? OR c.`name` LIKE ? OR cr.`name` LIKE ? OR u.`name` LIKE ? OR un.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        $total = (int) Database::run('SELECT COUNT(*)' . $from, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT d.`id`, d.`type`, d.`description`, d.`received`, d.`returned`, d.`client_id`, d.`created_at`, d.`updated_at`,
                    d.`validated_at`, d.`aborded_at`,
                    c.`name` AS client_name, un.`name` AS unity_name, cr.`name` AS register_name,
                    u.`name` AS cashier_name, u.`email` AS cashier_email, u.`phone` AS cashier_phone'
            . $from . ' ORDER BY d.`created_at` DESC, d.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    public function detail(string $id): ?array
    {
        $row = Database::run(
            'SELECT d.*, c.`name` AS client_name, un.`name` AS unity_name, cr.`name` AS register_name,
                    u.`name` AS cashier_name, vb.`name` AS validator_name
               FROM `disbursements` d
               LEFT JOIN `clients` c ON c.`id` = d.`client_id`
               LEFT JOIN `unities` un ON un.`id` = d.`unity_id`
               LEFT JOIN `cash_registers` cr ON cr.`id` = d.`cash_register_id`
               LEFT JOIN `users` u ON u.`id` = d.`user_id`
               LEFT JOIN `users` vb ON vb.`id` = d.`validated_by`
              WHERE d.`id` = ? AND d.`deleted_at` IS NULL LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }
}
