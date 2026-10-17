<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;

/**
 * CRUD générique, adapté aux tables existantes (aucune modification de structure).
 * Chaque table a son modèle qui précise : $table, $fillable et, si besoin, les options ci-dessous.
 */
abstract class BaseModel
{
    protected string $table = '';
    protected string $pk = 'id';
    /** Colonnes que l'on a le droit d'écrire (protège contre l'injection de colonnes). */
    protected array $fillable = [];
    /** true : « supprimer » renseigne deleted_at au lieu d'effacer la ligne. */
    protected bool $softDelete = true;
    /** true : created_at / updated_at gérés automatiquement. */
    protected bool $timestamps = true;
    /** false pour les tables dont l'id est fourni par l'application (ex. horodatage varchar). */
    protected bool $autoIncrement = true;

    protected static function q(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new InvalidArgumentException('Identifiant SQL invalide : ' . $identifier);
        }
        return '`' . $identifier . '`';
    }

    /** Construit la clause WHERE (égalité, IS NULL, IN) et exclut les lignes supprimées. */
    protected function where(array $where, array &$params, bool $withTrashed = false): string
    {
        $parts = [];
        foreach ($where as $column => $value) {
            $col = self::q((string) $column);
            if ($value === null) {
                $parts[] = "$col IS NULL";
            } elseif (is_array($value)) {
                if ($value === []) {
                    $parts[] = '1 = 0';
                    continue;
                }
                $parts[] = "$col IN (" . implode(',', array_fill(0, count($value), '?')) . ')';
                foreach ($value as $v) {
                    $params[] = $v;
                }
            } else {
                $parts[] = "$col = ?";
                $params[] = $value;
            }
        }
        if ($this->softDelete && !$withTrashed) {
            $parts[] = self::q('deleted_at') . ' IS NULL';
        }
        return $parts ? ' WHERE ' . implode(' AND ', $parts) : '';
    }

    public function find(int|string $id, bool $withTrashed = false): ?array
    {
        $params = [];
        $sql = 'SELECT * FROM ' . self::q($this->table)
            . $this->where([$this->pk => $id], $params, $withTrashed) . ' LIMIT 1';
        $row = Database::run($sql, $params)->fetch();
        return $row ?: null;
    }

    public function all(array $where = [], ?string $orderBy = null, string $dir = 'DESC', ?int $limit = null, int $offset = 0): array
    {
        $params = [];
        $sql = 'SELECT * FROM ' . self::q($this->table) . $this->where($where, $params);
        $sql .= ' ORDER BY ' . self::q($orderBy ?? $this->pk) . ' ' . (strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC');
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(0, $limit) . ' OFFSET ' . max(0, $offset);
        }
        return Database::run($sql, $params)->fetchAll();
    }

    public function count(array $where = []): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*) FROM ' . self::q($this->table) . $this->where($where, $params);
        return (int) Database::run($sql, $params)->fetchColumn();
    }

    /** Ne garde que les colonnes autorisées. */
    protected function filter(array $data): array
    {
        $allowed = $this->fillable;
        if (!$this->autoIncrement) {
            $allowed[] = $this->pk;
        }
        return array_intersect_key($data, array_flip($allowed));
    }

    /** @return int|string identifiant de la ligne créée */
    public function insert(array $data): int|string
    {
        $data = $this->filter($data);
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $data['created_at'] ?? $now;
            $data['updated_at'] = $data['updated_at'] ?? $now;
        }
        if ($data === []) {
            throw new InvalidArgumentException('Aucune donnée à insérer.');
        }
        $cols = implode(', ', array_map([self::class, 'q'], array_keys($data)));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        Database::run('INSERT INTO ' . self::q($this->table) . " ($cols) VALUES ($marks)", array_values($data));

        return $this->autoIncrement ? (int) Database::pdo()->lastInsertId() : $data[$this->pk];
    }

    /** @return int nombre de lignes modifiées */
    public function update(int|string $id, array $data): int
    {
        $data = $this->filter($data);
        unset($data[$this->pk]);
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        if ($data === []) {
            return 0;
        }
        $set = implode(', ', array_map(static fn($c) => self::q($c) . ' = ?', array_keys($data)));
        $params = array_values($data);
        $params[] = $id;
        $stmt = Database::run('UPDATE ' . self::q($this->table) . " SET $set WHERE " . self::q($this->pk) . ' = ?', $params);
        return $stmt->rowCount();
    }

    /** Suppression logique (deleted_at) ou définitive selon $softDelete. */
    public function delete(int|string $id): int
    {
        if ($this->softDelete) {
            $now = date('Y-m-d H:i:s');
            $stmt = Database::run(
                'UPDATE ' . self::q($this->table) . ' SET `deleted_at` = ?, `updated_at` = ? WHERE ' . self::q($this->pk) . ' = ? AND `deleted_at` IS NULL',
                [$now, $now, $id]
            );
            return $stmt->rowCount();
        }
        return Database::run('DELETE FROM ' . self::q($this->table) . ' WHERE ' . self::q($this->pk) . ' = ?', [$id])->rowCount();
    }

    public function restore(int|string $id): int
    {
        if (!$this->softDelete) {
            return 0;
        }
        return Database::run(
            'UPDATE ' . self::q($this->table) . ' SET `deleted_at` = NULL WHERE ' . self::q($this->pk) . ' = ?',
            [$id]
        )->rowCount();
    }
}
