<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Permission;
use App\Core\View;
use App\Models\DisbursementModel;

final class DisbursementController
{
    private const PER_PAGE = 30;

    private DisbursementModel $disbursements;

    public function __construct()
    {
        $this->disbursements = new DisbursementModel();
    }

    /** Décaissements en attente de validation (droit list_disbursement_validations). */
    public function list_disbursement_validations(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->disbursements->pending($term, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('disbursements/validations', [
            'title'   => 'En attente de validation | Décaissements',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'canInfo' => Permission::hasAlias($gid, 'info_disbursement'),
        ]);
    }

    public function info(string $id): void
    {
        $d = $this->disbursements->detail($id);
        if ($d === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Décaissement introuvable']);
            return;
        }
        View::render('disbursements/info', [
            'title'         => 'Décaissement : ' . $d['id'],
            'disbursement'  => $d,
            'status'        => DisbursementModel::status($d),
            'denominations' => DisbursementModel::DENOMINATIONS,
        ]);
    }
}
