<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\SaleModel;
use App\Models\UnityModel;

final class SaleController
{
    private const PER_PAGE = 30;

    private SaleModel $sales;
    private UnityModel $unities;

    public function __construct()
    {
        $this->sales = new SaleModel();
        $this->unities = new UnityModel();
    }

    public function index(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $filter = [];
        foreach ((array) ($_GET['unity_filter'] ?? []) as $v) {
            if (is_string($v) && ctype_digit($v)) {
                $filter[] = (int) $v;
            }
        }
        $filter = array_values(array_unique($filter));

        $result = $this->sales->search($term, $filter, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('sales/index', [
            'title'   => 'Ventes',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'filter'  => $filter,
            'unities' => $this->unities->activeList(),
            'canInfo' => \App\Core\Permission::hasAlias($gid, 'info_sale'),
        ]);
    }

    /** Ventes en attente de validation (droit list_sale_validations). */
    public function list_sale_validations(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->sales->pending($term, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('sales/validations', [
            'title'   => 'En attente de validation | Ventes',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'canInfo' => \App\Core\Permission::hasAlias($gid, 'info_sale'),
        ]);
    }

    public function info(string $id): void
    {
        $sale = $this->sales->detail($id);
        if ($sale === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Vente introuvable']);
            return;
        }

        View::render('sales/info', [
            'title'         => 'Vente : ' . $sale['id'],
            'sale'          => $sale,
            'status'        => SaleModel::status($sale),
            'lines'         => $this->sales->lines((string) $sale['id']),
            'denominations' => SaleModel::DENOMINATIONS,
        ]);
    }
}
