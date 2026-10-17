<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Password;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\UserModel;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/tableau-de-bord');
        }
        View::render('auth/login', [
            'error'    => Session::pull('error'),
            'info'     => Session::pull('info'),
            'oldLogin' => Session::pull('old_login') ?? '',
        ], null);
    }

    public function login(): void
    {
        if (!Csrf::valid(Request::input('_csrf'))) {
            $this->fail('Votre session a expiré. Veuillez réessayer.');
        }

        $login = strtoupper(trim(Request::input('login')));
        $password = Request::input('password');

        if ($login === '' || $password === '' || strlen($login) > 100 || strlen($password) > 1024) {
            $this->fail('Identifiant et mot de passe requis.', $login);
        }

        $window = (int) Config::get('security.lockout_window', 900);
        $limits = [
            'ip:' . Request::ip()  => (int) Config::get('security.max_attempts_ip', 20),
            'login:' . $login      => (int) Config::get('security.max_attempts_login', 5),
        ];
        foreach ($limits as $key => $max) {
            if (RateLimiter::tooMany($key, $max, $window)) {
                $minutes = max(1, (int) ceil(RateLimiter::retryAfter($key, $window) / 60));
                $this->fail("Trop de tentatives. Réessayez dans $minutes minute(s).", $login);
            }
        }

        $user = (new UserModel())->findByLogin($login);
        $stored = '';
        $ok = false;
        if ($user !== null) {
            $stored = (string) $user['password'];
            $ok = Password::verify($password, $stored);
        } else {
            // Même durée de calcul que pour un compte existant (évite de révéler quels identifiants existent).
            Password::hash($password);
        }

        if (!$ok) {
            foreach (array_keys($limits) as $key) {
                RateLimiter::hit($key, $window);
            }
            Logger::log('Échec de connexion', $user !== null ? (int) $user['id'] : null, ['login' => $login]);
            $this->fail('Identifiant ou mot de passe incorrect.', $login);
        }

        if (!empty($user['suspended']) || empty($user['group_id'])) {
            Logger::log('Connexion refusée (compte suspendu)', (int) $user['id']);
            $this->fail("Ce compte est suspendu. Contactez l'administrateur.", $login);
        }

        // Succès
        RateLimiter::clear('login:' . $login);
        Auth::login($user);

        if (Config::get('security.update_last_login', true)) {
            Database::run('UPDATE `users` SET `last_login_at` = ? WHERE `id` = ?', [date('Y-m-d H:i:s'), $user['id']]);
        }
        if (Config::get('security.rehash_on_login', false) && Password::needsRehash($stored)) {
            Database::run('UPDATE `users` SET `password` = ? WHERE `id` = ?', [Password::hash($password), $user['id']]);
        }
        Logger::log('Connexion', (int) $user['id']);

        $to = $_SESSION['intended'] ?? '/tableau-de-bord';
        unset($_SESSION['intended']);
        if (!is_string($to) || $to === '' || $to[0] !== '/' || str_starts_with($to, '//')) {
            $to = '/tableau-de-bord';
        }
        redirect($to);
    }

    public function logout(): void
    {
        if (!Csrf::valid(Request::input('_csrf'))) {
            redirect('/tableau-de-bord');
        }
        Auth::logout();
        Session::flash('info', 'Vous êtes déconnecté.');
        redirect('/');
    }

    private function fail(string $message, string $login = ''): never
    {
        Session::flash('error', $message);
        if ($login !== '') {
            Session::flash('old_login', $login);
        }
        redirect('/');
    }
}
