<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\UserModel;

final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    /** Ouvre la session de l'utilisateur (à appeler après vérification du mot de passe). */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int) $user['id'];
        $_SESSION['auth_at'] = time();
        Csrf::rotate();
        self::$user = null;
        self::$loaded = false;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Utilisateur connecté, relu en base à chaque requête :
     * un compte suspendu ou supprimé est déconnecté immédiatement.
     */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;

        $uid = $_SESSION['uid'] ?? null;
        if (!$uid) {
            return self::$user = null;
        }
        $user = UserModel::findActive((int) $uid);
        if ($user === null) {
            self::logout(false);
            return null;
        }
        return self::$user = $user;
    }

    public static function logout(bool $log = true): void
    {
        $uid = $_SESSION['uid'] ?? null;
        if ($log && $uid) {
            Logger::log('Déconnexion', (int) $uid);
        }
        Session::destroy();
        self::$user = null;
        self::$loaded = true;
    }
}
