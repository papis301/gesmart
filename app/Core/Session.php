<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name((string) Config::get('security.session_name', 'gesmart_sid'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => Request::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $now = time();
        $idle = (int) Config::get('security.idle_timeout', 7200);
        if (isset($_SESSION['_last']) && ($now - (int) $_SESSION['_last']) > $idle) {
            self::destroy();
        }
        $_SESSION['_last'] = $now;
    }

    /** Détruit la session courante puis en démarre une nouvelle, vide. */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => 'Lax',
            ]);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        self::start();
    }

    public static function flash(string $key, string $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /** Conserve la saisie d'un formulaire refusé (jamais les mots de passe). */
    public static function setOld(array $data): void
    {
        $_SESSION['_old'] = $data;
    }

    public static function pullOld(): array
    {
        $old = $_SESSION['_old'] ?? [];
        unset($_SESSION['_old']);
        return is_array($old) ? $old : [];
    }

    /** Lit puis supprime un message flash. */
    public static function pull(string $key): ?string
    {
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return is_string($value) ? $value : null;
    }
}