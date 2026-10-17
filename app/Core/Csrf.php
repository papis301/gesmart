<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY]) || !is_string($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function valid(?string $token): bool
    {
        return is_string($token) && $token !== ''
            && isset($_SESSION[self::KEY]) && is_string($_SESSION[self::KEY])
            && hash_equals($_SESSION[self::KEY], $token);
    }

    public static function rotate(): void
    {
        unset($_SESSION[self::KEY]);
    }
}
