<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\RulesCatalog;
use Throwable;

final class GroupModel extends BaseModel
{
    protected string $table = 'groups';
    protected array $fillable = ['name'];

    /** @return array{rows: array, total: int} */
    public function search(string $term, int $page, int $perPage): array
    {
        $where = ' WHERE g.`deleted_at` IS NULL';
        $params = [];
        if ($term !== '') {
            $where .= ' AND g.`name` LIKE ?';
            $params[] = '%' . addcslashes($term, '%_\\') . '%';
        }
        $total = (int) Database::run("SELECT COUNT(*) FROM `groups` g$where", $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            "SELECT g.`id`, g.`name`, g.`created_at`, g.`updated_at`,
                    (SELECT COUNT(*) FROM `rules` r WHERE r.`group_id` = g.`id`) AS rules_count
               FROM `groups` g$where
              ORDER BY g.`name` ASC
              LIMIT " . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    /** @return string[] alias des droits du groupe */
    public function aliasesOf(int $groupId): array
    {
        $rows = Database::run('SELECT DISTINCT `alias` FROM `rules` WHERE `group_id` = ?', [$groupId])->fetchAll();
        return array_column($rows, 'alias');
    }

    public function usersCount(int $groupId): int
    {
        return (int) Database::run(
            'SELECT COUNT(*) FROM `users` WHERE `group_id` = ? AND `deleted_at` IS NULL',
            [$groupId]
        )->fetchColumn();
    }

    /** La colonne `name` est UNIQUE : les groupes supprimés (logiquement) comptent aussi. */
    public function nameTaken(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM `groups` WHERE `name` = ?';
        $params = [$name];
        if ($exceptId !== null) {
            $sql .= ' AND `id` <> ?';
            $params[] = $exceptId;
        }
        return (int) Database::run($sql, $params)->fetchColumn() > 0;
    }

    /**
     * Aligne les droits du groupe sur la sélection.
     *
     * @param string[] $wanted    alias cochés dans le formulaire
     * @param string[] $changeable alias que l'utilisateur connecté a le droit d'accorder/retirer
     * @param string[] $protected  alias qui ne doivent jamais être retirés
     */
    public function syncRules(int $groupId, array $wanted, array $changeable, array $protected = []): void
    {
        $catalog = RulesCatalog::byAlias();
        $current = $this->aliasesOf($groupId);
        $changeable = array_values(array_intersect($changeable, array_keys($catalog)));
        $wanted = array_unique(array_merge(array_intersect($wanted, $changeable), $protected));

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            foreach ($changeable as $alias) {
                $has = in_array($alias, $current, true);
                $want = in_array($alias, $wanted, true);
                if ($want && !$has) {
                    [$request, $path, $controller, $method] = $catalog[$alias];
                    Database::run(
                        'INSERT INTO `rules` (`group_id`, `request`, `path`, `controller`, `method`, `alias`) VALUES (?, ?, ?, ?, ?, ?)',
                        [$groupId, $request, $path, $controller, $method, $alias]
                    );
                } elseif (!$want && $has) {
                    Database::run('DELETE FROM `rules` WHERE `group_id` = ? AND `alias` = ?', [$groupId, $alias]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
