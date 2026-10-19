<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Permission;
use App\Core\View;
use App\Models\InterRegisterTransferModel;

final class InterRegisterTransferController
{
    private const PER_PAGE = 30;

    private InterRegisterTransferModel $transfers;

    public function __construct()
    {
        $this->transfers = new InterRegisterTransferModel();
    }

    public function index(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->transfers->search($term, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('inter_register_transfers/index', [
            'title'   => 'Transferts inter-caisse',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'canInfo' => Permission::hasAlias($gid, 'info_inter_register_transfer'),
        ]);
    }

    /** Transferts en attente de validation (droit list_inter_register_transfer_validations). */
    public function list_inter_register_transfer_validations(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->transfers->pending($term, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('inter_register_transfers/validations', [
            'title'   => 'En attente de validation | Transferts inter-caisse',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'canInfo' => Permission::hasAlias($gid, 'info_inter_register_transfer'),
        ]);
    }

    public function info(string $id): void
    {
        $t = $this->transfers->detail($id);
        if ($t === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Transfert introuvable']);
            return;
        }
        View::render('inter_register_transfers/info', [
            'title'         => 'Transfert : ' . $t['id'],
            'transfer'      => $t,
            'status'        => InterRegisterTransferModel::status($t),
            'denominations' => InterRegisterTransferModel::DENOMINATIONS,
        ]);
    }
}
