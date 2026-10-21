<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Situation globale : activité d'une période et soldes actuels (lecture seule). */
final class GlobalStateModel
{
    /**
     * Une ligne par nature d'opération : nombre, montant validé, montant en attente (les annulations sont exclues).
     *
     * @return array<int, array{label:string, count:int, done:float, pending:float}>
     */
    public function activity(string $from, string $to): array
    {
        $rows = [];

        // $table et $amount sont des constantes de ce fichier : jamais de saisie utilisateur.
        $line = static function (string $label, string $table, string $amount, string $extra = '', bool $validation = true) use (&$rows, $from, $to): void {
            $done = $validation ? '`validated_at` IS NOT NULL' : '1 = 1';
            $r = Database::run(
                "SELECT COUNT(*) AS n,
                        COALESCE(SUM(CASE WHEN $done THEN $amount ELSE 0 END), 0) AS done,
                        COALESCE(SUM(CASE WHEN $done THEN 0 ELSE $amount END), 0) AS pending
                   FROM `$table`
                  WHERE `deleted_at` IS NULL AND `aborded_at` IS NULL AND `created_at` BETWEEN ? AND ? $extra",
                [$from, $to]
            )->fetch();
            $rows[] = ['label' => $label, 'count' => (int) $r['n'], 'done' => (float) $r['done'], 'pending' => (float) $r['pending']];
        };

        $line('Ventes au comptant', 'sales', '`total`', 'AND `debt` = 0');
        $line('Ventes à crédit', 'sales', '`total`', 'AND `debt` = 1');
        foreach (CollectionModel::TYPES as $code => $label) {
            $line('Encaissements : ' . $label, 'collections', '(`received` - `returned`)', "AND `type` = '$code'", false);
        }
        $line('Encaissements : non typés', 'collections', '(`received` - `returned`)', "AND (`type` IS NULL OR `type` = '')", false);
        $line('Décaissements', 'disbursements', '(`returned` - `received`)');
        $line('Versements bancaires', 'payments', '`total`');
        foreach (WithdrawalModel::TYPES as $code => $label) {
            $line('Retraits bancaires : ' . $label, 'withdrawals', '`total`', "AND `type` = '$code'");
        }
        $line('Transferts inter-caisses', 'inter_register_transfers', '`total`');
        $line('Transferts hors place', 'inter_unit_transfers', '`total`');
        $line('Transferts compte à compte', 'inter_client_transfers', '`amount`');

        return array_values(array_filter($rows, static fn(array $r): bool => $r['count'] > 0));
    }

    /** Soldes actuels : caisses, banques, clients. */
    public function balances(): array
    {
        $cash = Database::run('SELECT COUNT(*) AS n, COALESCE(SUM(`balance`), 0) AS total FROM `cash_registers` WHERE `deleted_at` IS NULL')->fetch();
        $bank = Database::run('SELECT COUNT(*) AS n, COALESCE(SUM(`balance`), 0) AS total FROM `banks` WHERE `deleted_at` IS NULL')->fetch();
        // Solde client = crédit (négatif : il doit) + versé ; même règle que la liste des clients.
        $cli = Database::run(
            'SELECT COUNT(*) AS n,
                    COALESCE(SUM(CASE WHEN (COALESCE(`credit`, 0) + COALESCE(`balance`, 0)) < 0 THEN COALESCE(`credit`, 0) + COALESCE(`balance`, 0) END), 0) AS debts,
                    COALESCE(SUM(CASE WHEN (COALESCE(`credit`, 0) + COALESCE(`balance`, 0)) > 0 THEN COALESCE(`credit`, 0) + COALESCE(`balance`, 0) END), 0) AS assets
               FROM `clients` WHERE `deleted_at` IS NULL'
        )->fetch();

        return [
            'cash'    => ['count' => (int) $cash['n'], 'total' => (float) $cash['total']],
            'bank'    => ['count' => (int) $bank['n'], 'total' => (float) $bank['total']],
            'clients' => ['count' => (int) $cli['n'], 'debts' => (float) $cli['debts'], 'assets' => (float) $cli['assets']],
        ];
    }
}
