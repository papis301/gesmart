<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Requêtes des rapports (lecture seule). */
final class ReportModel
{
    /** Types de transactions du rapport : code => [libellé, table]. */
    public const TRANSACTION_TYPES = [
        'sales'                   => 'Ventes',
        'disbursements'           => 'Décaissements',
        'collections'             => 'Encaissements',
        'inter_register_transfers' => 'Transfert inter-caisses',
        'inter_unit_transfers'    => 'Transfert hors place',
        'inter_client_transfers'  => 'Transfert compte à compte',
        'payments'                => 'Versements bancaires',
        'withdrawals'             => 'Retraits bancaires',
    ];

    private const S = 'CONVERT(%s USING utf8mb4) COLLATE utf8mb4_general_ci';

    /** Sélection normalisée de chaque table : type, id, date, montant, statut, caisses, clients, banques. */
    private static function sources(): array
    {
        $s = static fn(string $c): string => sprintf(self::S, $c);
        $nullS = $s('NULL');
        $status = static fn(string $v, string $a): string =>
            "CASE WHEN $a IS NOT NULL THEN 'aborded' WHEN $v IS NULL THEN 'pending' ELSE 'validated' END";
        $cols = static fn(string $type, string $id, string $at, string $amount, string $st, string $r1, string $r2, string $c1, string $c2, string $b1, string $b2, string $table): string =>
            "SELECT '$type' AS ttype, $id AS tid, $at AS tdate, $amount AS tamount, $st AS tstatus,
                    $r1 AS reg1, $r2 AS reg2, $c1 AS cli1, $c2 AS cli2, $b1 AS bank1, $b2 AS bank2
               FROM `$table` WHERE `deleted_at` IS NULL";
        $nr = 'CAST(NULL AS SIGNED)';
        return [
            'sales' => $cols('sales', $s('`id`'), '`created_at`', '`total`', $status('`validated_at`', '`aborded_at`'), '`cash_register_id`', $nr, 'CAST(`client_id` AS SIGNED)', $nr, $nullS, $nullS, 'sales'),
            'disbursements' => $cols('disbursements', $s('`id`'), '`created_at`', 'IF(`returned` > 0, `returned`, `received`)', $status('`validated_at`', '`aborded_at`'), '`cash_register_id`', $nr, 'CAST(`client_id` AS SIGNED)', $nr, $nullS, $nullS, 'disbursements'),
            'collections' => $cols('collections', $s('`id`'), '`created_at`', '`total`', "CASE WHEN `aborded_at` IS NOT NULL THEN 'aborded' ELSE 'validated' END", '`cash_register_id`', $nr, 'CAST(`client_id` AS SIGNED)', $nr, $nullS, $nullS, 'collections'),
            'inter_register_transfers' => $cols('inter_register_transfers', $s('`id`'), '`created_at`', '`total`', $status('`validated_at`', '`aborded_at`'), '`from`', '`to`', $nr, $nr, $nullS, $nullS, 'inter_register_transfers'),
            'inter_unit_transfers' => $cols('inter_unit_transfers', $s('`id`'), '`created_at`', '`total`', $status('`validated_at`', '`aborded_at`'), '`from`', '`to`', $nr, $nr, $nullS, $nullS, 'inter_unit_transfers'),
            'inter_client_transfers' => $cols('inter_client_transfers', $s('CAST(`id` AS CHAR)'), '`created_at`', '`amount`', $status('`validated_at`', '`aborded_at`'), $nr, $nr, 'CAST(`from` AS SIGNED)', 'CAST(`to` AS SIGNED)', $nullS, $nullS, 'inter_client_transfers'),
            'payments' => $cols('payments', $s('CAST(`id` AS CHAR)'), '`created_at`', '`total`', $status('`validated_at`', '`aborded_at`'), '`cash_register_id`', $nr, 'CAST(`client_id` AS SIGNED)', $nr, $s('`bank_id`'), $nullS, 'payments'),
            'withdrawals' => $cols('withdrawals', $s('CAST(`id` AS CHAR)'), '`created_at`', '`total`', $status('`validated_at`', '`aborded_at`'), $nr, $nr, 'CAST(`client_id` AS SIGNED)', $nr, $s('`from_bank_id`'), $s('`to_bank_id`'), 'withdrawals'),
        ];
    }

    /**
     * @param array{from:string,to:string,registers:int[],clients:int[],banks:string[],types:string[]} $f
     * @return array{rows: array, total: int}
     */
    public function transactions(array $f, int $page, int $perPage): array
    {
        $all = self::sources();
        $types = array_values(array_intersect($f['types'], array_keys($all)));
        if (!$types) {
            $types = array_keys($all);
        }
        $union = implode(' UNION ALL ', array_map(static fn(string $t): string => $all[$t], $types));

        $where = ['t.`tdate` >= ?', 't.`tdate` <= ?'];
        $params = [$f['from'], $f['to']];
        $in = static function (array $values): string {
            return implode(',', array_fill(0, count($values), '?'));
        };
        if ($f['registers']) {
            $where[] = '(t.`reg1` IN (' . $in($f['registers']) . ') OR t.`reg2` IN (' . $in($f['registers']) . '))';
            array_push($params, ...$f['registers'], ...$f['registers']);
        }
        if ($f['clients']) {
            $where[] = '(t.`cli1` IN (' . $in($f['clients']) . ') OR t.`cli2` IN (' . $in($f['clients']) . '))';
            array_push($params, ...$f['clients'], ...$f['clients']);
        }
        if ($f['banks']) {
            $where[] = '(t.`bank1` IN (' . $in($f['banks']) . ') OR t.`bank2` IN (' . $in($f['banks']) . '))';
            array_push($params, ...$f['banks'], ...$f['banks']);
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $total = (int) Database::run("SELECT COUNT(*) FROM ($union) t" . $whereSql, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            "SELECT t.`ttype`, t.`tid`, t.`tdate`, t.`tamount`, t.`tstatus`,
                    r1.`name` AS reg1_name, r2.`name` AS reg2_name,
                    c1.`name` AS cli1_name, c2.`name` AS cli2_name, t.`cli1`, t.`cli2`,
                    b1.`name` AS bank1_name, b2.`name` AS bank2_name, t.`bank1`, t.`bank2`
               FROM ($union) t
               LEFT JOIN `cash_registers` r1 ON r1.`id` = t.`reg1`
               LEFT JOIN `cash_registers` r2 ON r2.`id` = t.`reg2`
               LEFT JOIN `clients` c1 ON c1.`id` = t.`cli1`
               LEFT JOIN `clients` c2 ON c2.`id` = t.`cli2`
               LEFT JOIN `banks` b1 ON b1.`id` = t.`bank1`
               LEFT JOIN `banks` b2 ON b2.`id` = t.`bank2`"
            . $whereSql . ' ORDER BY t.`tdate` DESC, t.`tid` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    /** Listes pour les filtres du rapport (aucune donnée sensible). */
    public function filterOptions(): array
    {
        return [
            'registers' => Database::run(
                'SELECT cr.`id`, cr.`name`, un.`name` AS unity_name, u.`name` AS cashier_name
                   FROM `cash_registers` cr
                   LEFT JOIN `unities` un ON un.`id` = cr.`unity_id`
                   LEFT JOIN `users` u ON u.`id` = cr.`user_id`
                  WHERE cr.`deleted_at` IS NULL ORDER BY cr.`name`'
            )->fetchAll(),
            'clients' => Database::run('SELECT `id`, `name` FROM `clients` WHERE `deleted_at` IS NULL ORDER BY `name`')->fetchAll(),
            'banks'   => Database::run('SELECT `id`, `name` FROM `banks` WHERE `deleted_at` IS NULL ORDER BY `name`')->fetchAll(),
        ];
    }
}
