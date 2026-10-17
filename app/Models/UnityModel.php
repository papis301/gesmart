<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class UnityModel extends BaseModel
{
    protected string $table = 'unities';
    protected array $fillable = ['name'];

    /** @return array<int, array{id:string,name:string}> unités actives, par ordre alphabétique */
    public function activeList(): array
    {
        return Database::run('SELECT `id`, `name` FROM `unities` WHERE `deleted_at` IS NULL ORDER BY `name` ASC')->fetchAll();
    }

    public function exists(int $id): bool
    {
        return (int) Database::run('SELECT COUNT(*) FROM `unities` WHERE `id` = ? AND `deleted_at` IS NULL', [$id])->fetchColumn() > 0;
    }
}
