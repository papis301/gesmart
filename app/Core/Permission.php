<?php
declare(strict_types=1);

namespace App\Core;

/** Droits d'accès lus dans la table `rules` (une règle = une méthode HTTP + un chemin pour un groupe). */
final class Permission
{
    private static array $cache = [];

    private static function rules(int $groupId): array
    {
        if (!isset(self::$cache[$groupId])) {
            self::$cache[$groupId] = Database::run(
                'SELECT `request`, `path`, `alias` FROM `rules` WHERE `group_id` = ?',
                [$groupId]
            )->fetchAll();
        }
        return self::$cache[$groupId];
    }

    public static function allowed(int $groupId, string $method, string $path): bool
    {
        $method = strtoupper($method);
        $path = trim($path, '/');
        foreach (self::rules($groupId) as $rule) {
            $rulePath = trim((string) $rule['path']);
            if ($rulePath === '' || strtoupper(trim((string) $rule['request'])) !== $method) {
                continue;
            }
            if (preg_match(Router::toRegex($rulePath), $path) === 1) {
                return true;
            }
        }
        return false;
    }

    /** Pour les règles « sans route » (ex. cr_data, financial_data) ou pour tester un alias. */
    public static function hasAlias(int $groupId, string $alias): bool
    {
        foreach (self::rules($groupId) as $rule) {
            if ((string) $rule['alias'] === $alias) {
                return true;
            }
        }
        return false;
    }
}
