<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Permission;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::user();
        $gid = (int) $user['group_id'];

        // Opérations en attente de validation (ni validées, ni annulées, ni supprimées).
        // Visibles pour les groupes qui ont accès à l'écran de validation correspondant.
        $sources = [
            'Décaissements' => [
                'validations/decaissements',
                'SELECT `id`, `created_at`, `description` AS label, (`returned` - `received`) AS amount
                   FROM `disbursements`
                  WHERE `validated_at` IS NULL AND `aborded_at` IS NULL AND `deleted_at` IS NULL
                  ORDER BY `created_at` ASC',
            ],
            'Versements bancaires' => [
                'validations/versements',
                'SELECT `id`, `created_at`, `desc` AS label, `total` AS amount
                   FROM `payments`
                  WHERE `validated_at` IS NULL AND `aborded_at` IS NULL AND `deleted_at` IS NULL
                  ORDER BY `created_at` ASC',
            ],
            'Retraits bancaires' => [
                'validations/retraits',
                'SELECT `id`, `created_at`, `desc` AS label, `total` AS amount
                   FROM `withdrawals`
                  WHERE `validated_at` IS NULL AND `aborded_at` IS NULL AND `deleted_at` IS NULL
                  ORDER BY `created_at` ASC',
            ],
        ];

        $pending = [];
        foreach ($sources as $title => [$path, $sql]) {
            if (!Permission::allowed($gid, 'GET', $path)) {
                continue;
            }
            $rows = Database::run($sql)->fetchAll();
            $total = 0.0;
            foreach ($rows as $r) {
                $total += (float) $r['amount'];
            }
            $pending[$title] = ['rows' => $rows, 'total' => $total];
        }

        View::render('dashboard/index', [
            'title'   => 'Tableau de bord',
            'user'    => $user,
            'pending' => $pending,
        ]);
    }
}
