<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Permission;
use App\Core\View;
use App\Models\ClosureModel;

final class ClosureController
{
    private const PER_PAGE = 30;

    private ClosureModel $closures;

    public function __construct()
    {
        $this->closures = new ClosureModel();
    }

    public function index(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->closures->search($term, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('closures/index', [
            'title'   => 'Arrêtés de caisses',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'canInfo' => Permission::hasAlias($gid, 'info_closure'),
        ]);
    }

    public function info(string $id): void
    {
        $c = $this->closures->detail($id);
        if ($c === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Arrêté introuvable']);
            return;
        }
        View::render('closures/info', [
            'title'         => 'Arrêté de caisse : ' . $c['id'],
            'closure'       => $c,
            'denominations' => ClosureModel::DENOMINATIONS,
        ]);
    }
}
