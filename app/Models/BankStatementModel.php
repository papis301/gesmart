<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Relevé de compte bancaire : mouvements d'un compte sur une période (lecture seule). */
final class BankStatementModel
{
    /** Comptes bancaires pour la liste de sélection. */
    public function bankOptions(): array
    {
        return Database::run('SELECT `id`, `name`, `balance` FROM `banks` WHERE `deleted_at` IS NULL ORDER BY `name` ASC')->fetchAll();
    }

    /**
     * Mouvements du compte, du plus ancien au plus récent.
     * Montant positif = crédit du compte, négatif = débit.
     *
     * @return array<int, array{at:string, kind:string, ref:string, label:string, amount:float, pending:bool, link:string, alias:string}>
     */
    public function movements(string $bankId, string $from, string $to): array
    {
        $out = [];

        // Versements : la banque est créditée.
        foreach (Database::run(
            'SELECT `id`, `created_at`, `desc`, `total`, `by_check`, `validated_at` FROM `payments`
              WHERE `bank_id` = ? AND `deleted_at` IS NULL AND `aborded_at` IS NULL
                AND `created_at` BETWEEN ? AND ?',
            [$bankId, $from, $to]
        )->fetchAll() as $r) {
            $out[] = ['at' => $r['created_at'], 'kind' => 'Versement' . ((int) $r['by_check'] === 1 ? ' par chèque' : ''),
                'ref' => (string) $r['id'], 'label' => (string) $r['desc'], 'amount' => (float) $r['total'],
                'pending' => empty($r['validated_at']), 'link' => 'versements/' . $r['id'], 'alias' => 'info_payment'];
        }

        // Retraits : débit si le compte est `from_bank_id`, crédit s'il est `to_bank_id` (virement entre banques).
        foreach (Database::run(
            'SELECT `id`, `created_at`, `type`, `desc`, `total`, `from_bank_id`, `to_bank_id`, `validated_at` FROM `withdrawals`
              WHERE (`from_bank_id` = ? OR `to_bank_id` = ?) AND `deleted_at` IS NULL AND `aborded_at` IS NULL
                AND `created_at` BETWEEN ? AND ?',
            [$bankId, $bankId, $from, $to]
        )->fetchAll() as $r) {
            $debit = (string) $r['from_bank_id'] === $bankId;
            $out[] = ['at' => $r['created_at'],
                'kind' => 'Retrait : ' . WithdrawalModel::type($r['type']) . (!$debit ? ' (reçu)' : ''),
                'ref' => (string) $r['id'], 'label' => (string) $r['desc'],
                'amount' => $debit ? -(float) $r['total'] : (float) $r['total'],
                'pending' => empty($r['validated_at']), 'link' => 'retraits/' . $r['id'], 'alias' => 'info_withdrawal'];
        }

        usort($out, static fn(array $a, array $b): int => strcmp($a['at'], $b['at']));
        return $out;
    }
}
