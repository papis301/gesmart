<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Compatible avec les mots de passe actuels (40 caractères hexadécimaux, SHA-1)
 * et avec les hachages modernes de password_hash().
 */
final class Password
{
    public static function isLegacy(string $stored): bool
    {
        return (bool) preg_match('/^[a-f0-9]{40}$/i', $stored);
    }

    public static function verify(string $plain, string $stored): bool
    {
        if ($stored === '') {
            return false;
        }
        if (self::isLegacy($stored)) {
            return hash_equals(strtolower($stored), sha1($plain));
        }
        return password_verify($plain, $stored);
    }

    public static function needsRehash(string $stored): bool
    {
        return self::isLegacy($stored) || password_needs_rehash($stored, PASSWORD_DEFAULT);
    }

    public static function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }
}
