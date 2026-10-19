<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Permission;
use App\Core\View;
use App\Models\WithdrawalModel;

final class WithdrawalController
{
    private const PER_PAGE = 30;

    private WithdrawalModel $withdrawals;

    public function __construct()
    {
        $this->withdrawals = new WithdrawalModel();
    }

    public function index(): void
    {
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->withdrawals->search($term, $page, self::PER_PAGE);
        $gid = (int) Auth::user()['group_id'];

        View::render('withdrawals/index', [
            'title'   => 'Retraits bancaires',
            'actions' => '',
            'rows'    => $result['rows'],
            'total'   => $result['total'],
            'page'    => $page,
            'pages'   => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'    => $term,
            'canInfo' => Permission::hasAlias($gid, 'info_withdrawal'),
        ]);
    }

    public function info(string $id): void
    {
        $w = $this->withdrawals->detail((int) $id);
        if ($w === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Retrait introuvable']);
            return;
        }
        View::render('withdrawals/info', [
            'title'      => 'Retrait : ' . $w['id'],
            'withdrawal' => $w,
            'status'     => WithdrawalModel::status($w),
        ]);
    }
}
