<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDOException;

final class ClientModel extends BaseModel
{
    protected string $table = 'clients';
    /** L'identifiant (Nº de compte) est fourni par l'application : AAMM + numéro d'ordre sur 4 chiffres. */
    protected bool $autoIncrement = false;
    protected array $fillable = [
        'unity_id', 'name', 'email', 'phone', 'ninea', 'nid', 'address',
        'balance', 'credit', 'validated', 'validated_by',
    ];

    /** FROM/WHERE commun à la liste et aux totaux. */
    private function scope(string $term, array $unityIds, string $type, array &$params): string
    {
        $sql = ' FROM `clients` c LEFT JOIN `unities` un ON un.`id` = c.`unity_id` WHERE c.`deleted_at` IS NULL';
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $sql .= ' AND (c.`name` LIKE ? OR CAST(c.`id` AS CHAR) LIKE ? OR c.`phone` LIKE ? OR c.`email` LIKE ? OR c.`ninea` LIKE ? OR un.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        if ($unityIds) {
            $sql .= ' AND c.`unity_id` IN (' . implode(',', array_fill(0, count($unityIds), '?')) . ')';
            array_push($params, ...$unityIds);
        }
        // Solde = crédit (négatif, ce que le client doit) + FAGAROU/remboursements (ce qu'il a versé).
        if ($type === 'debit') {
            $sql .= ' AND (COALESCE(c.`credit`, 0) + COALESCE(c.`balance`, 0)) < -0.5';
        } elseif ($type === 'credit') {
            $sql .= ' AND (COALESCE(c.`credit`, 0) + COALESCE(c.`balance`, 0)) > 0.5';
        }
        return $sql;
    }

    /**
     * @param int[] $unityIds
     * @param string $type '', 'debit' (clients qui doivent de l'argent) ou 'credit' (clients qui ont de l'argent)
     * @return array{rows: array, total: int, sum_balance: float, sum_credit: float}
     */
    public function search(string $term, array $unityIds, string $type, int $page, int $perPage): array
    {
        $params = [];
        $from = $this->scope($term, $unityIds, $type, $params);

        $totals = Database::run(
            'SELECT COUNT(*) AS n, COALESCE(SUM(c.`balance`), 0) AS b, COALESCE(SUM(c.`credit`), 0) AS cr' . $from,
            $params
        )->fetch();

        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT c.`id`, c.`name`, c.`email`, c.`phone`, c.`credit`, c.`balance`, c.`validated`, c.`created_at`, c.`updated_at`,
                    un.`name` AS unity_name'
            . $from . ' ORDER BY c.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return [
            'rows'        => $rows,
            'total'       => (int) $totals['n'],
            'sum_balance' => (float) $totals['b'],
            'sum_credit'  => (float) $totals['cr'],
        ];
    }

    /** Fiche d'un client avec le nom de l'unité et de la personne qui l'a validé. */
    public function detail(int $id): ?array
    {
        $row = Database::run(
            'SELECT c.*, un.`name` AS unity_name, v.`name` AS validator_name
               FROM `clients` c
               LEFT JOIN `unities` un ON un.`id` = c.`unity_id`
               LEFT JOIN `users` v ON v.`id` = c.`validated_by`
              WHERE c.`id` = ? AND c.`deleted_at` IS NULL LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }

    /** NINEA et NID ont un index UNIQUE : les clients supprimés logiquement comptent aussi. */
    public function taken(string $column, string $value, ?int $exceptId = null): bool
    {
        if (!in_array($column, ['ninea', 'nid'], true)) {
            throw new \InvalidArgumentException('Colonne non autorisée.');
        }
        $sql = "SELECT COUNT(*) FROM `clients` WHERE `$column` = ?";
        $params = [$value];
        if ($exceptId !== null) {
            $sql .= ' AND `id` <> ?';
            $params[] = $exceptId;
        }
        return (int) Database::run($sql, $params)->fetchColumn() > 0;
    }

    /** Prochain Nº de compte : AAMM + 4 chiffres (ex. 26100009). Les clients supprimés gardent leur numéro. */
    private function nextId(): int
    {
        $min = (int) date('ym') * 10000;
        $max = $min + 9999;
        $last = Database::run('SELECT MAX(`id`) FROM `clients` WHERE `id` BETWEEN ? AND ?', [$min, $max])->fetchColumn();
        $next = $last ? (int) $last + 1 : $min + 1;
        if ($next > $max) {
            throw new \RuntimeException('Limite de 9999 clients par mois atteinte.');
        }
        return $next;
    }

    /** Crée le client avec son numéro de compte ; réessaie si deux créations simultanées ont pris le même numéro. */
    public function createClient(array $data): int
    {
        for ($try = 0; $try < 3; $try++) {
            $id = $this->nextId();
            try {
                $this->insert($data + ['id' => $id]);
                return $id;
            } catch (PDOException $e) {
                if ($try < 2 && str_contains($e->getMessage(), 'PRIMARY')) {
                    continue;
                }
                throw $e;
            }
        }
        throw new \RuntimeException('Impossible de générer le numéro de compte.');
    }

    /**
     * Opérations déjà enregistrées pour ce client.
     *
     * @return array<string, int> libellé => nombre
     */
    public function usage(int $id): array
    {
        $count = static fn(string $sql, array $p): int => (int) Database::run($sql, $p)->fetchColumn();

        return [
            'Ventes'                    => $count('SELECT COUNT(*) FROM `sales` WHERE `client_id` = ?', [$id]),
            'Encaissements'             => $count('SELECT COUNT(*) FROM `collections` WHERE `client_id` = ?', [$id]),
            'Décaissements'             => $count('SELECT COUNT(*) FROM `disbursements` WHERE `client_id` = ?', [$id]),
            'Suivis paiements (S.P.I.)' => $count('SELECT COUNT(*) FROM `regularisations` WHERE `client_id` = ?', [$id]),
            'Transferts compte à compte' => $count('SELECT COUNT(*) FROM `inter_client_transfers` WHERE `from` = ? OR `to` = ?', [$id, $id]),
        ];
    }
}
