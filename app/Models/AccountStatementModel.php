<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Relevé de compte client : opérations d'un client sur une période (lecture seule). */
final class AccountStatementModel
{
    /** Clients validés pour la liste de sélection. */
    public function clientOptions(): array
    {
        return Database::run(
            'SELECT c.`id`, c.`name`, un.`name` AS unity_name
               FROM `clients` c LEFT JOIN `unities` un ON un.`id` = c.`unity_id`
              WHERE c.`deleted_at` IS NULL AND c.`validated` = 1
              ORDER BY c.`name` ASC'
        )->fetchAll();
    }

    /**
     * Mouvements du compte, du plus ancien au plus récent.
     * Montant négatif = le client doit (vente à crédit, transfert sortant) ; positif = il a versé.
     *
     * @return array<int, array{at:string, kind:string, ref:string, label:string, amount:float, pending:bool, link:string}>
     */
    public function movements(int $clientId, string $from, string $to): array
    {
        $out = [];

        // Ventes à crédit (les ventes annulées sont ignorées).
        $rows = Database::run(
            'SELECT `id`, `created_at`, `total`, `validated_at` FROM `sales`
              WHERE `client_id` = ? AND `debt` = 1 AND `deleted_at` IS NULL AND `aborded_at` IS NULL
                AND `created_at` BETWEEN ? AND ?',
            [$clientId, $from, $to]
        )->fetchAll();
        foreach ($rows as $r) {
            $out[] = ['at' => $r['created_at'], 'kind' => 'Vente à crédit', 'ref' => (string) $r['id'],
                'label' => '', 'amount' => -(float) $r['total'], 'pending' => empty($r['validated_at']),
                'link' => 'ventes/' . $r['id'], 'alias' => 'info_sale'];
        }

        // Encaissements (reçu - rendu).
        $rows = Database::run(
            'SELECT `id`, `created_at`, `type`, `description`, (`received` - `returned`) AS amount FROM `collections`
              WHERE `client_id` = ? AND `deleted_at` IS NULL AND `aborded_at` IS NULL
                AND `created_at` BETWEEN ? AND ?',
            [$clientId, $from, $to]
        )->fetchAll();
        foreach ($rows as $r) {
            $out[] = ['at' => $r['created_at'], 'kind' => 'Encaissement : ' . CollectionModel::typeLabel($r['type']),
                'ref' => (string) $r['id'], 'label' => (string) $r['description'], 'amount' => (float) $r['amount'],
                'pending' => false, 'link' => 'encaissements/' . $r['id'], 'alias' => 'info_collection'];
        }

        // Transferts compte à compte : `from` est crédité, `to` est débité.
        $rows = Database::run(
            'SELECT t.`id`, t.`created_at`, t.`amount`, t.`from`, t.`to`, t.`validated_at`, fc.`name` AS from_name, tc.`name` AS to_name
               FROM `inter_client_transfers` t
               LEFT JOIN `clients` fc ON fc.`id` = t.`from`
               LEFT JOIN `clients` tc ON tc.`id` = t.`to`
              WHERE (t.`from` = ? OR t.`to` = ?) AND t.`deleted_at` IS NULL AND t.`aborded_at` IS NULL
                AND t.`created_at` BETWEEN ? AND ?',
            [$clientId, $clientId, $from, $to]
        )->fetchAll();
        foreach ($rows as $r) {
            $credited = (int) $r['from'] === $clientId;
            $out[] = ['at' => $r['created_at'],
                'kind' => $credited ? 'Transfert reçu' : 'Transfert émis',
                'ref' => (string) $r['id'],
                'label' => $credited ? 'de ' . ($r['to_name'] ?? $r['to']) : 'vers ' . ($r['from_name'] ?? $r['from']),
                'amount' => $credited ? (float) $r['amount'] : -(float) $r['amount'],
                'pending' => empty($r['validated_at']),
                'link' => 'transferts/compte_a_comptes/' . $r['id'], 'alias' => 'info_inter_client_transfer'];
        }

        usort($out, static fn(array $a, array $b): int => strcmp($a['at'], $b['at']));
        return $out;
    }
}
