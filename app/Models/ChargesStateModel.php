<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** État des sorties de caisse : décaissements des caisses choisies sur une période (lecture seule). */
final class ChargesStateModel
{
    /**
     * @param int[] $registerIds
     * @return array<int, array<string, mixed>> décaissements non annulés, du plus ancien au plus récent
     */
    public function disbursements(array $registerIds, string $from, string $to): array
    {
        if (!$registerIds) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($registerIds), '?'));
        $rows = Database::run(
            "SELECT d.`id`, d.`created_at`, d.`type`, d.`description`, d.`received`, d.`returned`, d.`validated_at`,
                    d.`cash_register_id`, cr.`name` AS register_name, c.`name` AS client_name
               FROM `disbursements` d
               LEFT JOIN `cash_registers` cr ON cr.`id` = d.`cash_register_id`
               LEFT JOIN `clients` c ON c.`id` = d.`client_id`
              WHERE d.`cash_register_id` IN ($marks) AND d.`deleted_at` IS NULL AND d.`aborded_at` IS NULL
                AND d.`created_at` BETWEEN ? AND ?
              ORDER BY cr.`name` ASC, d.`created_at` ASC, d.`id` ASC",
            [...$registerIds, $from, $to]
        )->fetchAll();

        foreach ($rows as &$r) {
            $r['amount'] = DisbursementModel::amount($r);
            $r['pending'] = empty($r['validated_at']);
        }
        unset($r);
        return $rows;
    }
}
