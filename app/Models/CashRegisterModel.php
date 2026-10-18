<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CashRegisterModel extends BaseModel
{
    protected string $table = 'cash_registers';
    /** Le solde, les coupures et l'état (closed) ne sont modifiés que par les opérations de caisse. */
    protected array $fillable = ['unity_id', 'user_id', 'admin_user_id', 'name'];

    /** Coupures gérées par la caisse : colonne => valeur en FCFA. */
    public const DENOMINATIONS = [
        't_10_000' => 10000, 't_5_000' => 5000, 't_2_000' => 2000, 't_1_000' => 1000,
        't_500' => 500, 't_250' => 250, 't_200' => 200, 't_100' => 100,
        't_50' => 50, 't_25' => 25, 't_10' => 10, 't_5' => 5,
    ];

    /** Tables d'historique qui référencent une caisse (retraits : pas de colonne cash_register_id). */
    private const HISTORY = [
        'Ventes'          => 'sales',
        'Encaissements'   => 'collections',
        'Décaissements'   => 'disbursements',
        'Versements'      => 'payments',
        'Arrêtés de caisse' => 'closures',
    ];

    /**
     * @param int[] $unityIds filtre sur les unités (vide = toutes)
     * @return array{rows: array, total: int}
     */
    public function search(string $term, array $unityIds, int $page, int $perPage): array
    {
        $from = ' FROM `cash_registers` cr
                  LEFT JOIN `unities` un ON un.`id` = cr.`unity_id`
                  LEFT JOIN `users` cu ON cu.`id` = cr.`user_id`
                  LEFT JOIN `users` au ON au.`id` = cr.`admin_user_id`
                 WHERE cr.`deleted_at` IS NULL';
        $params = [];
        if ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $from .= ' AND (cr.`name` LIKE ? OR un.`name` LIKE ? OR cu.`name` LIKE ? OR au.`name` LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if ($unityIds) {
            $from .= ' AND cr.`unity_id` IN (' . implode(',', array_fill(0, count($unityIds), '?')) . ')';
            array_push($params, ...$unityIds);
        }
        $total = (int) Database::run('SELECT COUNT(*)' . $from, $params)->fetchColumn();
        $offset = max(0, ($page - 1) * $perPage);
        $rows = Database::run(
            'SELECT cr.`id`, cr.`name`, cr.`balance`, cr.`closed`, cr.`created_at`, cr.`updated_at`,
                    un.`name` AS unity_name, cu.`name` AS cashier_name, au.`name` AS manager_name'
            . $from . ' ORDER BY cr.`name` ASC, cr.`id` ASC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll();

        return ['rows' => $rows, 'total' => $total];
    }

    /** Fiche d'une caisse avec les noms de l'unité, du caissier et du responsable. */
    public function detail(int $id): ?array
    {
        $row = Database::run(
            'SELECT cr.*, un.`name` AS unity_name, cu.`name` AS cashier_name, au.`name` AS manager_name
               FROM `cash_registers` cr
               LEFT JOIN `unities` un ON un.`id` = cr.`unity_id`
               LEFT JOIN `users` cu ON cu.`id` = cr.`user_id`
               LEFT JOIN `users` au ON au.`id` = cr.`admin_user_id`
              WHERE cr.`id` = ? AND cr.`deleted_at` IS NULL LIMIT 1',
            [$id]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Utilisateurs que l'on peut affecter à une caisse (actifs, non suspendus).
     * Les identifiants de $keep (valeurs déjà enregistrées) restent proposés même s'ils ont été suspendus depuis.
     *
     * @param int[] $keep
     * @return array<int, array{id:string,name:string,group_name:?string,unity_name:?string}>
     */
    public function assignableUsers(array $keep = []): array
    {
        $keep = array_values(array_filter(array_map('intval', $keep)));
        $sql = 'SELECT u.`id`, u.`name`, g.`name` AS group_name, un.`name` AS unity_name
                  FROM `users` u
                  LEFT JOIN `groups` g ON g.`id` = u.`group_id`
                  LEFT JOIN `unities` un ON un.`id` = u.`unity_id`
                 WHERE u.`deleted_at` IS NULL AND (COALESCE(u.`suspended`, 0) = 0';
        $params = [];
        if ($keep) {
            $sql .= ' OR u.`id` IN (' . implode(',', array_fill(0, count($keep), '?')) . ')';
            $params = $keep;
        }
        $sql .= ') ORDER BY u.`name` ASC, u.`id` ASC';
        return Database::run($sql, $params)->fetchAll();
    }

    public function userIsActive(int $userId): bool
    {
        return (int) Database::run(
            'SELECT COUNT(*) FROM `users` WHERE `id` = ? AND `deleted_at` IS NULL AND COALESCE(`suspended`, 0) = 0',
            [$userId]
        )->fetchColumn() > 0;
    }

    /** Une personne ne tient qu'une seule caisse à la fois : nom de la caisse déjà affectée, sinon null. */
    public function registerOfCashier(int $userId, ?int $exceptId = null): ?string
    {
        $sql = 'SELECT `name` FROM `cash_registers` WHERE `deleted_at` IS NULL AND `user_id` = ?';
        $params = [$userId];
        if ($exceptId !== null) {
            $sql .= ' AND `id` <> ?';
            $params[] = $exceptId;
        }
        $name = Database::run($sql . ' LIMIT 1', $params)->fetchColumn();
        return $name === false ? null : (string) $name;
    }

    /** Aucun index unique sur le nom : on évite les doublons côté application. */
    public function nameTaken(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM `cash_registers` WHERE `deleted_at` IS NULL AND `name` = ?';
        $params = [$name];
        if ($exceptId !== null) {
            $sql .= ' AND `id` <> ?';
            $params[] = $exceptId;
        }
        return (int) Database::run($sql, $params)->fetchColumn() > 0;
    }

    /**
     * Nombre d'opérations déjà enregistrées sur la caisse.
     *
     * @return array<string, int> libellé => nombre
     */
    public function history(int $id): array
    {
        $out = [];
        foreach (self::HISTORY as $label => $table) {
            $out[$label] = (int) Database::run("SELECT COUNT(*) FROM `$table` WHERE `cash_register_id` = ?", [$id])->fetchColumn();
        }
        return $out;
    }
}
