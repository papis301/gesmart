<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Relevé de caisse : mouvements d'espèces d'une caisse sur une période (lecture seule). */
final class RegisterStatementModel
{
    /** Caisses pour la liste de sélection (nom, unité, caissier, solde). */
    public function registerOptions(): array
    {
        return Database::run(
            'SELECT cr.`id`, cr.`name`, cr.`balance`, un.`name` AS unity_name, u.`name` AS cashier_name
               FROM `cash_registers` cr
               LEFT JOIN `unities` un ON un.`id` = cr.`unity_id`
               LEFT JOIN `users` u ON u.`id` = cr.`user_id`
              WHERE cr.`deleted_at` IS NULL ORDER BY cr.`name` ASC'
        )->fetchAll();
    }

    /**
     * Mouvements de la caisse, du plus ancien au plus récent.
     * Montant positif = entrée d'espèces, négatif = sortie.
     *
     * @return array<int, array{at:string, kind:string, ref:string, label:string, amount:float, pending:bool, link:string, alias:string}>
     */
    public function movements(int $registerId, string $from, string $to): array
    {
        $out = [];
        $add = static function (array $r, string $kind, string $label, float $amount, string $link, string $alias) use (&$out): void {
            $out[] = ['at' => $r['created_at'], 'kind' => $kind, 'ref' => (string) $r['id'], 'label' => $label,
                'amount' => $amount, 'pending' => array_key_exists('validated_at', $r) && empty($r['validated_at']),
                'link' => $link . $r['id'], 'alias' => $alias];
        };

        // Ventes au comptant (une vente à crédit n'encaisse rien).
        foreach (Database::run(
            'SELECT `id`, `created_at`, `total`, `validated_at` FROM `sales`
              WHERE `cash_register_id` = ? AND `debt` = 0 AND `deleted_at` IS NULL AND `aborded_at` IS NULL
                AND `created_at` BETWEEN ? AND ?',
            [$registerId, $from, $to]
        )->fetchAll() as $r) {
            $add($r, 'Vente', '', (float) $r['total'], 'ventes/', 'info_sale');
        }

        // Encaissements : reçu - rendu.
        foreach (Database::run(
            'SELECT `id`, `created_at`, `type`, `description`, (`received` - `returned`) AS amount FROM `collections`
              WHERE `cash_register_id` = ? AND `deleted_at` IS NULL AND `aborded_at` IS NULL
                AND `created_at` BETWEEN ? AND ?',
            [$registerId, $from, $to]
        )->fetchAll() as $r) {
            $add($r, 'Encaissement : ' . CollectionModel::typeLabel($r['type']), (string) $r['description'],
                (float) $r['amount'], 'encaissements/', 'info_collection');
        }

        // Décaissements : rendu - reçu (sortie nette).
        foreach (Database::run(
            'SELECT `id`, `created_at`, `description`, (`returned` - `received`) AS amount, `validated_at` FROM `disbursements`
              WHERE `cash_register_id` = ? AND `deleted_at` IS NULL AND `aborded_at` IS NULL
                AND `created_at` BETWEEN ? AND ?',
            [$registerId, $from, $to]
        )->fetchAll() as $r) {
            $add($r, 'Décaissement', (string) $r['description'], -(float) $r['amount'], 'decaissements/', 'info_disbursement');
        }

        // Versements bancaires : l'argent quitte la caisse.
        foreach (Database::run(
            'SELECT `id`, `created_at`, `desc`, `total`, `validated_at` FROM `payments`
              WHERE `cash_register_id` = ? AND `deleted_at` IS NULL AND `aborded_at` IS NULL
                AND `created_at` BETWEEN ? AND ?',
            [$registerId, $from, $to]
        )->fetchAll() as $r) {
            $add($r, 'Versement bancaire', (string) $r['desc'], -(float) $r['total'], 'versements/', 'info_payment');
        }

        // Transferts inter-caisses et hors place : `from` donne, `to` reçoit.
        foreach ([
            ['inter_register_transfers', 'Transfert inter-caisse', 'transferts/inter_caisses/', 'info_inter_register_transfer'],
            ['inter_unit_transfers', 'Transfert hors place', 'transferts/hors_places/', 'info_inter_unit_tranfer'],
        ] as [$table, $kind, $link, $alias]) {
            foreach (Database::run(
                "SELECT `id`, `created_at`, `total`, `from`, `to`, `validated_at` FROM `$table`
                  WHERE (`from` = ? OR `to` = ?) AND `deleted_at` IS NULL AND `aborded_at` IS NULL
                    AND `created_at` BETWEEN ? AND ?",
                [$registerId, $registerId, $from, $to]
            )->fetchAll() as $r) {
                $gives = (int) $r['from'] === $registerId;
                $add($r, $kind . ($gives ? ' émis' : ' reçu'), '', $gives ? -(float) $r['total'] : (float) $r['total'], $link, $alias);
            }
        }

        usort($out, static fn(array $a, array $b): int => strcmp($a['at'], $b['at']));
        return $out;
    }
}
