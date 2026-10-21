<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** État de la trésorerie par unité de production : soldes des banques et des caisses (lecture seule). */
final class CashStateModel
{
    /**
     * @return array{units: array<int, array{name:string, banks:array, registers:array, bank_total:float, register_total:float, total:float}>, bank_total:float, register_total:float, total:float}
     */
    public function state(): array
    {
        $units = [];
        foreach (Database::run('SELECT `id`, `name` FROM `unities` WHERE `deleted_at` IS NULL ORDER BY `id` ASC')->fetchAll() as $u) {
            $units[(int) $u['id']] = ['name' => (string) $u['name'], 'banks' => [], 'registers' => [],
                'bank_total' => 0.0, 'register_total' => 0.0, 'total' => 0.0];
        }

        // Une banque est rattachée à une ou plusieurs unités (table bank_unity_links).
        foreach (Database::run(
            'SELECT l.`unity_id`, b.`name`, b.`balance`
               FROM `bank_unity_links` l JOIN `banks` b ON b.`id` = l.`bank_id`
              WHERE b.`deleted_at` IS NULL ORDER BY b.`created_at` ASC, b.`id` ASC'
        )->fetchAll() as $b) {
            $id = (int) $b['unity_id'];
            if (isset($units[$id])) {
                $units[$id]['banks'][] = ['name' => (string) $b['name'], 'balance' => (float) $b['balance']];
                $units[$id]['bank_total'] += (float) $b['balance'];
            }
        }

        foreach (Database::run(
            'SELECT `unity_id`, `name`, `balance` FROM `cash_registers` WHERE `deleted_at` IS NULL ORDER BY `id` ASC'
        )->fetchAll() as $c) {
            $id = (int) $c['unity_id'];
            if (isset($units[$id])) {
                $units[$id]['registers'][] = ['name' => (string) $c['name'], 'balance' => (float) $c['balance']];
                $units[$id]['register_total'] += (float) $c['balance'];
            }
        }

        $bank = 0.0;
        $reg = 0.0;
        foreach ($units as &$u) {
            $u['total'] = $u['bank_total'] + $u['register_total'];
            $bank += $u['bank_total'];
            $reg += $u['register_total'];
        }
        unset($u);

        return ['units' => array_values($units), 'bank_total' => $bank, 'register_total' => $reg, 'total' => $bank + $reg];
    }
}
