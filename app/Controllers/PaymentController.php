<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Permission;
use App\Core\View;
use App\Models\PaymentModel;

final class PaymentController
{
    private const PER_PAGE = 30;

    private PaymentModel $payments;

    public function __construct()
    {
        $this->payments = new PaymentModel();
    }

    public function index(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->payments->search($term, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('payments/index', [
            'title'   => 'Versements bancaires',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'canInfo' => Permission::hasAlias($gid, 'info_payment'),
        ]);
    }

    /** Versements en attente de validation/confirmation (droit list_payments). */
    public function list_payments(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->payments->pending($term, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('payments/validations', [
            'title'   => 'Versements bancaires',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'canInfo' => Permission::hasAlias($gid, 'info_payment'),
        ]);
    }

    public function info(string $id): void
    {
        $p = $this->payments->detail((int) $id);
        if ($p === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Versement introuvable']);
            return;
        }
        View::render('payments/info', [
            'title'         => 'Versement : ' . $p['id'],
            'payment'       => $p,
            'status'        => PaymentModel::status($p),
            'denominations' => PaymentModel::DENOMINATIONS,
        ]);
    }
}
