<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Suivi paiements-inventaire (S.P.I.), table regularisations :
 * un plafond de crédit accordé à un client jusqu'à une échéance, soldé quand regulated_at est renseigné.
 */
final class RegularisationModel extends BaseModel
{
    protected string $table = 'regularisations';
    protected array $fillable = [];

    /** Date MySQL exploitable (les « 0000-00-00 » de l'ancienne base sont ignorés). */
    public static function date(?string $v): ?string
    {
        return ($v === null || $v === '' || str_starts_with($v, '0000')) ? null : $v;
    }

    /** @return array{label:string, class:string} */
    public static function status(array $r): array
    {
        return $r['regulated_at'] !== null
            ? ['label' => 'PAYÉ', 'class' => 'success']
            : ['label' => 'EN COURS', 'class' => 'warning'];
    }

    /**
     * État du crédit. Règle déduite des pages de l'ancienne application :
     * soldé à l'échéance ou après = « PAYE EN RETARD », non soldé et échéance dépassée = « EN RETARD ».
     *
     * @return array{label:string, class:string}
     */
    public static function creditState(array $r): array
    {
        $due = self::date($r['due_date'] ?? null);
        if ($r['regulated_at'] !== null) {
            $reg = self::date($r['regulated_at']);
            $late = $due === null || $reg === null || substr($reg, 0, 10) >= $due;
            return $late
                ? ['label' => 'PAYE EN RETARD', 'class' => 'warning']
                : ['label' => 'PAYÉ À TEMPS', 'class' => 'success'];
        }
        if ($due !== null && $due < date('Y-m-d')) {
            return ['label' => 'EN RETARD', 'class' => 'warning'];
        }
        return ['label' => 'DANS LES DÉLAIS', 'class' => 'info'];
    }

    private const FROM = ' FROM `regularisations` r
                  LEFT JOIN `clients` c ON c.`id` = r.`client_id`
                  LEFT JOIN `users` u ON u.`id` = r.`user_id`
                 WHERE r.`deleted_at` IS NULL';

    /** @return array{rows: array, total: int} */
    public function search(string $term, int $page, int $perPage): array
    {
        $where = '';
        $params = [];
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $where = ' AND (CAST(r.`id` AS CHAR) LIKE ? OR CAST(r.`client_id` AS CHAR) LIKE ? OR c.`name` LIKE ? OR u.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        $total = (int) Database::run('SELECT COUNT(*)' . self::FROM . $where, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT r.`id`, r.`client_id`, r.`granted_credit`, r.`due_date`, r.`regulated_at`, r.`created_at`, r.`updated_at`,
                    c.`name` AS client_name, c.`email` AS client_email, c.`phone` AS client_phone,
                    (COALESCE(c.`balance`, 0) + COALESCE(c.`credit`, 0)) AS account_balance,
                    u.`name` AS author_name, u.`email` AS author_email, u.`phone` AS author_phone'
            . self::FROM . $where . ' ORDER BY r.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    public function detail(int $id): ?array
    {
        $row = Database::run(
            'SELECT r.*, c.`name` AS client_name, c.`email` AS client_email, c.`phone` AS client_phone,
                    (COALESCE(c.`balance`, 0) + COALESCE(c.`credit`, 0)) AS account_balance,
                    u.`name` AS author_name, u.`email` AS author_email, u.`phone` AS author_phone'
            . self::FROM . ' AND r.`id` = ? LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }
}
