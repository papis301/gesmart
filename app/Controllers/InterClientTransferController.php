<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Permission;
use App\Core\View;
use App\Models\InterClientTransferModel;

final class InterClientTransferController
{
    private const PER_PAGE = 30;

    private InterClientTransferModel $transfers;

    public function __construct()
    {
        $this->transfers = new InterClientTransferModel();
    }

    public function index(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->transfers->search($term, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('inter_client_transfers/index', [
            'title'   => 'Transferts compte à compte',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'canInfo' => Permission::hasAlias($gid, 'info_inter_client_transfer'),
        ]);
    }

    /** Transferts compte à compte en attente de validation (droit list_inter_client_transfer_validations). */
    public function list_inter_client_transfer_validations(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->transfers->pending($term, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('inter_client_transfers/validations', [
            'title'   => 'En attente de validation | Transferts comptes à comptes',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'canInfo' => Permission::hasAlias($gid, 'info_inter_client_transfer'),
        ]);
    }

    public function info(string $id): void
    {
        $t = $this->transfers->detail((int) $id);
        if ($t === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Transfert introuvable']);
            return;
        }
        View::render('inter_client_transfers/info', [
            'title'    => 'Transfert : ' . $t['id'],
            'transfer' => $t,
            'status'   => InterClientTransferModel::status($t),
        ]);
    }
}
