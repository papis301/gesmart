<?php
declare(strict_types=1);

namespace App\Core;

/** Limiteur d'essais basé sur des fichiers (aucune modification de la base de données). */
final class RateLimiter
{
    private static function file(string $key): string
    {
        return STORAGE_DIR . '/ratelimit/' . hash('sha256', $key) . '.json';
    }

    /** @return int[] horodatages encore dans la fenêtre */
    private static function read(string $key, int $window): array
    {
        $file = self::file($key);
        if (!is_file($file)) {
            return [];
        }
        $list = json_decode((string) @file_get_contents($file), true);
        if (!is_array($list)) {
            return [];
        }
        $min = time() - $window;
        return array_values(array_filter($list, static fn($t) => is_int($t) && $t > $min));
    }

    public static function tooMany(string $key, int $max, int $window): bool
    {
        return count(self::read($key, $window)) >= $max;
    }

    /** Secondes avant que le plus ancien essai sorte de la fenêtre. */
    public static function retryAfter(string $key, int $window): int
    {
        $list = self::read($key, $window);
        return $list ? max(0, min($list) + $window - time()) : 0;
    }

    public static function hit(string $key, int $window): void
    {
        if (!is_dir(STORAGE_DIR . '/ratelimit')) {
            @mkdir(STORAGE_DIR . '/ratelimit', 0750, true);
        }
        $list = self::read($key, $window);
        $list[] = time();
        @file_put_contents(self::file($key), json_encode($list), LOCK_EX);
        if (random_int(1, 100) === 1) {
            self::purge($window);
        }
    }

    public static function clear(string $key): void
    {
        $file = self::file($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private static function purge(int $window): void
    {
        foreach (glob(STORAGE_DIR . '/ratelimit/*.json') ?: [] as $file) {
            if (filemtime($file) < time() - $window * 2) {
                @unlink($file);
            }
        }
    }
}
