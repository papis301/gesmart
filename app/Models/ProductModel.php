<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ProductModel extends BaseModel
{
    protected string $table = 'products';
    protected array $fillable = ['unity_id', 'name', 'price', 'stock'];

    /**
     * @param int[] $unityIds filtre sur les unités (vide = toutes)
     * @return array{rows: array, total: int}
     */
    public function search(string $term, array $unityIds, int $page, int $perPage): array
    {
        $from = ' FROM `products` p LEFT JOIN `unities` un ON un.`id` = p.`unity_id` WHERE p.`deleted_at` IS NULL';
        $params = [];
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $from .= ' AND (p.`name` LIKE ? OR un.`name` LIKE ?)';
            array_push($params, $like, $like);
        }
        if ($unityIds) {
            $from .= ' AND p.`unity_id` IN (' . implode(',', array_fill(0, count($unityIds), '?')) . ')';
            array_push($params, ...$unityIds);
        }
        $total = (int) Database::run('SELECT COUNT(*)' . $from, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT p.`id`, p.`name`, p.`price`, p.`stock`, p.`created_at`, p.`updated_at`, un.`name` AS unity_name'
            . $from . ' ORDER BY p.`name` DESC, un.`name` ASC, p.`id` DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    /** Nombre de lignes de vente qui utilisent ce produit (historique). */
    public function salesLinesCount(int $id): int
    {
        return (int) Database::run('SELECT COUNT(*) FROM `sale_lines` WHERE `product_id` = ?', [$id])->fetchColumn();
    }

    public function unityName(?int $unityId): string
    {
        if ($unityId === null) {
            return '--';
        }
        $name = Database::run('SELECT `name` FROM `unities` WHERE `id` = ?', [$unityId])->fetchColumn();
        return $name === false ? '--' : (string) $name;
    }
}
