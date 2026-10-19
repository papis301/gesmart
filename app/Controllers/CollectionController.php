<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Permission;
use App\Core\View;
use App\Models\CashRegisterModel;
use App\Models\CollectionModel;
use App\Models\UnityModel;

/**
 * Encaissements : consultation (liste filtrable + fiche). La création et l'annulation modifient les caisses
 * et les comptes clients : elles ne sont pas reprises ici.
 */
final class CollectionController
{
    private const PER_PAGE = 30;

    private CollectionModel $collections;
    private UnityModel $unities;

    public function __construct()
    {
        $this->collections = new CollectionModel();
        $this->unities = new UnityModel();
    }

    private static function date(mixed $v): string
    {
        $v = is_string($v) ? trim($v) : '';
        $d = \DateTime::createFromFormat('Y-m-d', $v);
        return $d !== false && $d->format('Y-m-d') === $v ? $v : '';
    }

    public function index(): void
    {
        $gid = (int) Auth::user()['group_id'];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $filters = [
            'term'             => trim((string) ($_GET['s'] ?? '')),
            'unity_id'         => ctype_digit((string) ($_GET['unity_id'] ?? '')) ? (int) $_GET['unity_id'] : 0,
            'cash_register_id' => ctype_digit((string) ($_GET['cash_register_id'] ?? '')) ? (int) $_GET['cash_register_id'] : 0,
            'type'             => array_key_exists($_GET['type'] ?? '', CollectionModel::TYPES) ? (string) $_GET['type'] : '',
            'status'           => array_key_exists($_GET['status'] ?? '', CollectionModel::STATUSES) ? (string) $_GET['status'] : '',
            'from'             => self::date($_GET['from'] ?? ''),
            'to'               => self::date($_GET['to'] ?? ''),
        ];

        $result = $this->collections->search($filters, $page, self::PER_PAGE);

        View::render('collections/index', [
            'title'     => 'Encaissements',
            'actions'   => '',
            'rows'      => $result['rows'],
            'total'     => $result['total'],
            'sumAmount' => $result['sum_amount'],
            'page'      => $page,
            'pages'     => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'filters'   => $filters,
            'unities'   => $this->unities->activeList(),
            'registers' => $this->collections->registers(),
            'canInfo'   => Permission::hasAlias($gid, 'info_collection'),
        ]);
    }

    public function info(string $id): void
    {
        $collection = $this->collections->detail($id);
        if ($collection === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Encaissement introuvable']);
            return;
        }

        View::render('collections/info', [
            'title'         => 'Encaissement nº ' . $collection['id'],
            'c'             => $collection,
            'aborted'       => !empty($collection['aborded_at']),
            'denominations' => CashRegisterModel::DENOMINATIONS,
            'canInfoSale'   => Permission::hasAlias((int) Auth::user()['group_id'], 'info_sale'),
        ]);
    }
}
