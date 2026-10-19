<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Permission;
use App\Core\View;
use App\Models\CashRegisterModel;
use App\Models\InterUnitTransferModel;
use App\Models\UnityModel;

/**
 * Transferts hors place : liste, fiche et liste des transferts en attente de validation (lecture seule).
 */
final class InterUnitTransferController
{
    private const PER_PAGE = 30;

    private InterUnitTransferModel $transfers;
    private UnityModel $unities;

    public function __construct()
    {
        $this->transfers = new InterUnitTransferModel();
        $this->unities = new UnityModel();
    }

    private static function date(mixed $v): string
    {
        $v = is_string($v) ? trim($v) : '';
        $d = \DateTime::createFromFormat('Y-m-d', $v);
        return $d !== false && $d->format('Y-m-d') === $v ? $v : '';
    }

    /** Liste commune : $pendingOnly = true pour la page de validation. */
    private function listing(bool $pendingOnly): void
    {
        $gid = (int) Auth::user()['group_id'];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $filters = [
            'term'      => trim((string) ($_GET['s'] ?? '')),
            'unity_id'  => ctype_digit((string) ($_GET['unity_id'] ?? '')) ? (int) $_GET['unity_id'] : 0,
            'status'    => $pendingOnly ? 'pending' : (array_key_exists($_GET['status'] ?? '', InterUnitTransferModel::STATUSES) ? (string) $_GET['status'] : ''),
            'from_date' => self::date($_GET['from_date'] ?? ''),
            'to_date'   => self::date($_GET['to_date'] ?? ''),
        ];

        $result = $this->transfers->search($filters, $page, self::PER_PAGE);

        View::render('inter_unit_transfers/index', [
            'title'       => $pendingOnly ? 'Transferts hors place — en attente de validation' : 'Transferts hors place',
            'actions'     => '',
            'rows'        => $result['rows'],
            'total'       => $result['total'],
            'sumAmount'   => $result['sum_amount'],
            'page'        => $page,
            'pages'       => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'filters'     => $filters,
            'unities'     => $this->unities->activeList(),
            'pendingOnly' => $pendingOnly,
            'baseUrl'     => $pendingOnly ? '/validations/transferts/hors_places' : '/transferts/hors_places',
            'canInfo'     => Permission::hasAlias($gid, 'info_inter_unit_tranfer'),
        ]);
    }

    public function index(): void
    {
        $this->listing(false);
    }

    /** validations/transferts/hors_places */
    public function pending(): void
    {
        $this->listing(true);
    }

    public function info(string $id): void
    {
        $t = $this->transfers->detail($id);
        if ($t === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Transfert introuvable']);
            return;
        }

        View::render('inter_unit_transfers/info', [
            'title'         => 'Transfert hors place nº ' . $t['id'],
            't'             => $t,
            'status'        => InterUnitTransferModel::status($t),
            'denominations' => CashRegisterModel::DENOMINATIONS,
        ]);
    }
}
