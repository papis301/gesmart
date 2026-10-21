<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Permission;
use App\Core\View;
use App\Models\RegularisationModel;

final class RegularisationController
{
    private const PER_PAGE = 30;

    private RegularisationModel $regularisations;

    public function __construct()
    {
        $this->regularisations = new RegularisationModel();
    }

    public function index(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->regularisations->search($term, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('regularisations/index', [
            'title'   => 'Suivi paiements-inventaire',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'canInfo' => Permission::hasAlias($gid, 'info_regularisation'),
        ]);
    }

    public function info(string $id): void
    {
        $r = ctype_digit($id) ? $this->regularisations->detail((int) $id) : null;
        if ($r === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Régularisation introuvable']);
            return;
        }
        View::render('regularisations/info', [
            'title'  => 'Suivi paiements-inventaire : ' . $r['id'],
            'reg'    => $r,
            'status' => RegularisationModel::status($r),
            'state'  => RegularisationModel::creditState($r),
        ]);
    }
}
