<?php
declare(strict_types=1);

namespace App\Core;

/** Catalogue des droits (cases à cocher du formulaire de groupe) <-> lignes de la table `rules`. */
final class RulesCatalog
{
    private static ?array $resources = null;
    private static ?array $byAlias = null;

    /** @return array<string, array{title:string, description:string, permissions:array}> */
    public static function resources(): array
    {
        return self::$resources ??= require APP_DIR . '/rules_catalog.php';
    }

    /** @return array<string, array> alias => [request, path, controller, method, alias] */
    public static function byAlias(): array
    {
        if (self::$byAlias === null) {
            self::$byAlias = [];
            foreach (self::resources() as $resource) {
                foreach ($resource['permissions'] as $permission) {
                    self::$byAlias[$permission['rule'][4]] = $permission['rule'];
                }
            }
        }
        return self::$byAlias;
    }

    /**
     * Transforme le formulaire (rules[ressource][] = valeur) en liste d'alias valides.
     * Tout ce qui n'est pas dans le catalogue est ignoré.
     *
     * @return string[]
     */
    public static function aliasesFromForm(mixed $posted): array
    {
        $aliases = [];
        if (!is_array($posted)) {
            return $aliases;
        }
        foreach (self::resources() as $key => $resource) {
            $values = $posted[$key] ?? null;
            if (!is_array($values)) {
                continue;
            }
            foreach ($resource['permissions'] as $value => $permission) {
                if (in_array((string) $value, array_map('strval', $values), true)) {
                    $aliases[] = $permission['rule'][4];
                }
            }
        }
        return $aliases;
    }
}
