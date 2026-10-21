<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Permission;
use App\Core\View;
use App\Models\AccountStatementModel;
use App\Models\ClientModel;
use App\Models\BankModel;
use App\Models\BankStatementModel;
use App\Models\CashRegisterModel;
use App\Models\CashStateModel;
use App\Models\ChargesStateModel;
use App\Models\DisbursementModel;
use App\Models\GlobalStateModel;
use App\Models\RegisterStatementModel;
use App\Models\ReportModel;

final class ReportController
{
    private const PER_PAGE = 100;

    private ReportModel $reports;

    public function __construct()
    {
        $this->reports = new ReportModel();
    }

    /** @return string[] */
    private static function strings(string $key): array
    {
        $v = $_GET[$key] ?? [];
        return is_array($v) ? array_values(array_filter(array_map(static fn($x) => trim((string) $x), $v), static fn(string $x): bool => $x !== '')) : [];
    }

    /** « 2026-10-01T08:00 » (datetime-local) ou « 2026-10-01 08:00:00 » => « Y-m-d H:i:s », sinon null. */
    private static function dateTime(string $v): ?string
    {
        try {
            return (new \DateTimeImmutable(str_replace('T', ' ', $v)))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    /** Rapport sur les transactions (droit transaction_report). */
    public function transaction(): void
    {
        $from = (string) ($_GET['from_date'] ?? date('Y-m-01') . 'T08:00');
        $to = (string) ($_GET['to_date'] ?? date('Y-m-d') . 'T17:00');
        $submitted = isset($_GET['from_date']) || isset($_GET['to_date']);

        $filters = [
            'from'      => self::dateTime($from) ?? date('Y-m-01 08:00:00'),
            'to'        => self::dateTime($to) ?? date('Y-m-d 17:00:00'),
            'registers' => array_map('intval', array_filter(self::strings('registers'), 'ctype_digit')),
            'clients'   => array_map('intval', array_filter(self::strings('clients'), 'ctype_digit')),
            'banks'     => self::strings('banks'),
            'types'     => array_values(array_intersect(self::strings('transactions'), array_keys(ReportModel::TRANSACTION_TYPES))),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $submitted ? $this->reports->transactions($filters, $page, self::PER_PAGE) : null;

        View::render('reports/transactions', [
            'title'     => 'Rapports sur les transactions',
            'actions'   => '',
            'filters'   => $filters,
            'fromInput' => str_replace(' ', 'T', substr($filters['from'], 0, 16)),
            'toInput'   => str_replace(' ', 'T', substr($filters['to'], 0, 16)),
            'options'   => $this->reports->filterOptions(),
            'types'     => ReportModel::TRANSACTION_TYPES,
            'submitted' => $submitted,
            'rows'      => $result['rows'] ?? [],
            'total'     => $result['total'] ?? 0,
            'page'      => $page,
            'pages'     => $result ? max(1, (int) ceil($result['total'] / self::PER_PAGE)) : 1,
        ]);
    }

    /** Relevé de compte client (droit account_statement_report). */
    public function account_statement(): void
    {
        $model = new AccountStatementModel();
        $from = self::dateTime((string) ($_GET['from_date'] ?? '')) ?? date('Y-m-01') . ' 08:00:00';
        $to = self::dateTime((string) ($_GET['to_date'] ?? '')) ?? date('Y-m-d') . ' 17:00:00';
        if (str_ends_with($to, ':00')) {
            $to = substr($to, 0, -2) . '59'; // la minute choisie est incluse en entier
        }

        $clientId = ctype_digit((string) ($_GET['client'] ?? '')) ? (int) $_GET['client'] : 0;
        $client = null;
        $movements = [];
        $error = '';

        if ($clientId > 0) {
            $client = (new ClientModel())->detail($clientId);
            if ($client === null) {
                $error = 'Compte client introuvable.';
            } elseif ($from > $to) {
                $error = 'La date de début doit précéder la date de fin.';
            } else {
                $movements = $model->movements($clientId, $from, $to);
            }
        }

        $gid = (int) Auth::user()['group_id'];
        $can = [];
        foreach (['info_sale', 'info_collection', 'info_inter_client_transfer'] as $alias) {
            $can[$alias] = Permission::hasAlias($gid, $alias);
        }

        View::render('reports/account_statement', [
            'title'     => 'Relevé de compte',
            'clients'   => $model->clientOptions(),
            'from'      => str_replace(' ', 'T', substr($from, 0, 16)),
            'to'        => str_replace(' ', 'T', substr($to, 0, 16)),
            'clientId'  => $clientId,
            'client'    => $client,
            'movements' => $movements,
            'error'     => $error,
            'can'       => $can,
            'head'      => '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">',
            'foot'      => '<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>'
                         . '<script src="' . e(asset('client-select.js')) . '" defer></script>',
        ]);
    }

    /** Relevé de caisse (droit cash_register_history_report). */
    public function cash_register_history(): void
    {
        $model = new RegisterStatementModel();
        $from = self::dateTime((string) ($_GET['from_date'] ?? '')) ?? date('Y-m-01') . ' 08:00:00';
        $to = self::dateTime((string) ($_GET['to_date'] ?? '')) ?? date('Y-m-d') . ' 17:00:00';
        if (str_ends_with($to, ':00')) {
            $to = substr($to, 0, -2) . '59';
        }

        $registerId = ctype_digit((string) ($_GET['register'] ?? '')) ? (int) $_GET['register'] : 0;
        $register = null;
        $movements = [];
        $error = '';

        if ($registerId > 0) {
            $register = (new CashRegisterModel())->detail($registerId);
            if ($register === null) {
                $error = 'Caisse introuvable.';
            } elseif ($from > $to) {
                $error = 'La date de début doit précéder la date de fin.';
            } else {
                $movements = $model->movements($registerId, $from, $to);
            }
        }

        $gid = (int) Auth::user()['group_id'];
        $can = [];
        foreach (['info_sale', 'info_collection', 'info_disbursement', 'info_payment', 'info_inter_register_transfer', 'info_inter_unit_tranfer'] as $alias) {
            $can[$alias] = Permission::hasAlias($gid, $alias);
        }

        View::render('reports/cash_register_history', [
            'title'      => 'Relevé de caisse',
            'registers'  => $model->registerOptions(),
            'from'       => str_replace(' ', 'T', substr($from, 0, 16)),
            'to'         => str_replace(' ', 'T', substr($to, 0, 16)),
            'registerId' => $registerId,
            'register'   => $register,
            'movements'  => $movements,
            'error'      => $error,
            'can'        => $can,
            'head'       => '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">',
            'foot'       => '<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>'
                          . '<script src="' . e(asset('client-select.js')) . '" defer></script>',
        ]);
    }

    /** Relevé de compte bancaire (droit bank_history_report). */
    public function bank_history(): void
    {
        $model = new BankStatementModel();
        $from = self::dateTime((string) ($_GET['from_date'] ?? '')) ?? date('Y-m-01') . ' 08:00:00';
        $to = self::dateTime((string) ($_GET['to_date'] ?? '')) ?? date('Y-m-d') . ' 17:00:00';
        if (str_ends_with($to, ':00')) {
            $to = substr($to, 0, -2) . '59';
        }

        $bankId = trim((string) ($_GET['bank'] ?? ''));
        $bank = null;
        $movements = [];
        $error = '';

        if ($bankId !== '') {
            $bank = (new BankModel())->detail($bankId);
            if ($bank === null) {
                $error = 'Compte bancaire introuvable.';
            } elseif ($from > $to) {
                $error = 'La date de début doit précéder la date de fin.';
            } else {
                $movements = $model->movements($bankId, $from, $to);
            }
        }

        $gid = (int) Auth::user()['group_id'];
        $can = [];
        foreach (['info_payment', 'info_withdrawal'] as $alias) {
            $can[$alias] = Permission::hasAlias($gid, $alias);
        }

        View::render('reports/bank_history', [
            'title'     => 'Relevé de compte bancaire',
            'banks'     => $model->bankOptions(),
            'from'      => str_replace(' ', 'T', substr($from, 0, 16)),
            'to'        => str_replace(' ', 'T', substr($to, 0, 16)),
            'bankId'    => $bankId,
            'bank'      => $bank,
            'movements' => $movements,
            'error'     => $error,
            'can'       => $can,
            'head'      => '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">',
            'foot'      => '<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>'
                         . '<script src="' . e(asset('client-select.js')) . '" defer></script>',
        ]);
    }

    /** Situation globale (droit globale_state_report). */
    public function globale_state(): void
    {
        $submitted = isset($_GET['from_date']) || isset($_GET['to_date']);
        $from = self::dateTime((string) ($_GET['from_date'] ?? '')) ?? date('Y-m-01') . ' 08:00:00';
        $to = self::dateTime((string) ($_GET['to_date'] ?? '')) ?? date('Y-m-d') . ' 17:00:00';
        if (str_ends_with($to, ':00')) {
            $to = substr($to, 0, -2) . '59';
        }

        $error = '';
        $activity = [];
        $balances = [];
        if ($submitted) {
            if ($from > $to) {
                $error = 'La date de début doit précéder la date de fin.';
            } else {
                $model = new GlobalStateModel();
                $activity = $model->activity($from, $to);
                $balances = $model->balances();
            }
        }

        View::render('reports/globale_state', [
            'title'     => 'Rapports sur la situation globale',
            'from'      => str_replace(' ', 'T', substr($from, 0, 16)),
            'to'        => str_replace(' ', 'T', substr($to, 0, 16)),
            'submitted' => $submitted && $error === '',
            'activity'  => $activity,
            'balances'  => $balances,
            'error'     => $error,
        ]);
    }

    /** État des sorties de caisse (droit charges_state_report). */
    public function charges_state(): void
    {
        $submitted = isset($_GET['from_date']) || isset($_GET['registers']);
        $from = self::dateTime((string) ($_GET['from_date'] ?? '')) ?? date('Y-m-01') . ' 08:00:00';
        $to = self::dateTime((string) ($_GET['to_date'] ?? '')) ?? date('Y-m-d') . ' 17:00:00';
        if (str_ends_with($to, ':00')) {
            $to = substr($to, 0, -2) . '59';
        }
        $selected = array_values(array_unique(array_map('intval', array_filter(self::strings('registers'), 'ctype_digit'))));

        $error = '';
        $groups = [];
        $byType = [];
        $grandTotal = 0.0;
        if ($submitted) {
            if (!$selected) {
                $error = 'Choisissez au moins une caisse.';
            } elseif ($from > $to) {
                $error = 'La date de début doit précéder la date de fin.';
            } else {
                foreach ((new ChargesStateModel())->disbursements($selected, $from, $to) as $d) {
                    $name = (string) ($d['register_name'] ?? '--');
                    $groups[$name]['rows'][] = $d;
                    $groups[$name]['total'] = ($groups[$name]['total'] ?? 0.0) + ($d['pending'] ? 0.0 : $d['amount']);
                    if (!$d['pending']) {
                        $label = DisbursementModel::type($d['type']);
                        $byType[$label]['count'] = ($byType[$label]['count'] ?? 0) + 1;
                        $byType[$label]['total'] = ($byType[$label]['total'] ?? 0.0) + $d['amount'];
                        $grandTotal += $d['amount'];
                    }
                }
                uasort($byType, static fn(array $a, array $b): int => $b['total'] <=> $a['total']);
            }
        }

        View::render('reports/charges_state', [
            'title'      => 'Etat des sorties de caisse',
            'registers'  => (new RegisterStatementModel())->registerOptions(),
            'from'       => str_replace(' ', 'T', substr($from, 0, 16)),
            'to'         => str_replace(' ', 'T', substr($to, 0, 16)),
            'selected'   => $selected,
            'submitted'  => $submitted && $error === '',
            'groups'     => $groups,
            'byType'     => $byType,
            'grandTotal' => $grandTotal,
            'error'      => $error,
            'canInfo'    => Permission::hasAlias((int) Auth::user()['group_id'], 'info_disbursement'),
            'head'       => '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">',
            'foot'       => '<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>'
                          . '<script src="' . e(asset('client-select.js')) . '" defer></script>',
        ]);
    }

    /** État de la trésorerie par unité de production (droit cash_state_report). Page autonome, prête à imprimer. */
    public function cash_state(): void
    {
        View::render('reports/cash_state', [
            'state' => (new CashStateModel())->state(),
            'now'   => date('d/m/Y H:i'),
        ], null);
    }
}
