<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Password;
use App\Core\Permission;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\GroupModel;
use App\Models\UnityModel;
use App\Models\UserModel;

final class UserController
{
    private const PER_PAGE = 30;

    private UserModel $users;
    private GroupModel $groups;
    private UnityModel $unities;

    public function __construct()
    {
        $this->users = new UserModel();
        $this->groups = new GroupModel();
        $this->unities = new UnityModel();
    }

    private function me(): array
    {
        return Auth::user();
    }

    private function back(string $type, string $message, string $to = '/utilisateurs'): never
    {
        Session::flash($type, $message);
        redirect($to);
    }

    /** Groupes que l'utilisateur connecté a le droit d'attribuer. */
    private function assignable(): array
    {
        return $this->groups->assignableGroups((int) $this->me()['group_id']);
    }

    /** Peut-on gérer ce compte ? (pas de compte plus puissant que le sien) */
    private function manageable(array $target): bool
    {
        if ($target['group_id'] === null) {
            return true;
        }
        return in_array((string) $target['group_id'], array_map('strval', array_column($this->assignable(), 'id')), true);
    }

    public function index(): void
    {
        $gid = (int) $this->me()['group_id'];
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->users->search($term, $page, self::PER_PAGE);

        $canCreate = Permission::hasAlias($gid, 'create_user');
        $old = Session::pullOld();

        View::render('users/index', [
            'title'     => 'Utilisateurs',
            'actions'   => $canCreate
                ? '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#create">Créer un utilisateur</button>'
                : '',
            'rows'      => $result['rows'],
            'total'     => $result['total'],
            'page'      => $page,
            'pages'     => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'      => $term,
            'canCreate' => $canCreate,
            'canInfo'   => Permission::hasAlias($gid, 'info_user'),
            'groups'    => $this->assignable(),
            'unities'   => $this->unities->activeList(),
            'old'       => $old,
            'openCreate' => $old !== [],
        ]);
    }

    /**
     * Valide le formulaire (création si $current est null, modification sinon).
     *
     * @return array{0: ?array, 1: ?string} [données à enregistrer, message d'erreur]
     */
    private function validate(?array $current): array
    {
        $isCreate = $current === null;
        $exceptId = $isCreate ? null : (int) $current['id'];
        $isSelf = !$isCreate && (int) $current['id'] === (int) $this->me()['id'];

        // Groupe : seulement parmi ceux que l'on peut attribuer (on garde son propre groupe).
        $groupId = (int) Request::input('group_id');
        if ($isSelf) {
            $groupId = (int) $current['group_id'];
        } elseif (!in_array((string) $groupId, array_map('strval', array_column($this->assignable(), 'id')), true)) {
            return [null, 'Groupe invalide ou non autorisé.'];
        }

        $unityId = (int) Request::input('unity_id');
        if (!$this->unities->exists($unityId)) {
            return [null, 'Unité de production invalide.'];
        }

        $name = trim(Request::input('name'));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            return [null, 'Le nom est obligatoire (2 à 255 caractères).'];
        }

        $login = strtoupper(trim(Request::input('login')));
        if (!preg_match('/^[A-Z0-9._-]{3,50}$/', $login)) {
            return [null, 'Le login doit contenir 3 à 50 caractères (lettres, chiffres, point, tiret ou souligné).'];
        }
        if ($this->users->taken('login', $login, $exceptId)) {
            return [null, 'Ce login est déjà utilisé.'];
        }

        $email = trim(Request::input('email'));
        if ($email === '') {
            $email = null;
        } else {
            if (mb_strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return [null, 'Adresse email invalide.'];
            }
            if ($this->users->taken('email', $email, $exceptId)) {
                return [null, 'Cette adresse email est déjà utilisée.'];
            }
        }

        $phone = trim(Request::input('phone'));
        if ($phone === '') {
            $phone = null;
        } else {
            if (!preg_match('/^\+?[0-9][0-9 ]{5,19}$/', $phone)) {
                return [null, 'Numéro de téléphone invalide (ex. +221771234567).'];
            }
            if ($this->users->taken('phone', $phone, $exceptId)) {
                return [null, 'Ce numéro de téléphone est déjà utilisé.'];
            }
        }

        $data = [
            'group_id'  => $groupId,
            'unity_id'  => $unityId,
            'name'      => $name,
            'login'     => $login,
            'email'     => $email,
            'phone'     => $phone,
            'suspended' => $isCreate ? 0 : ($isSelf ? (int) $current['suspended'] : (Request::input('suspended') === '1' ? 1 : 0)),
        ];

        $password = Request::input('password');
        if ($isCreate && $password === '') {
            return [null, 'Le mot de passe est obligatoire.'];
        }
        if ($password !== '') {
            if (mb_strlen($password) < 8) {
                return [null, 'Le mot de passe doit contenir au moins 8 caractères.'];
            }
            if (mb_strlen($password) > 200) {
                return [null, 'Le mot de passe est trop long.'];
            }
            if (strtoupper($password) === $login) {
                return [null, 'Le mot de passe ne doit pas être identique au login.'];
            }
            $data['password'] = Password::forStorage($password);
        }

        return [$data, null];
    }

    public function create(): void
    {
        [$data, $error] = $this->validate(null);
        if ($error !== null) {
            Session::setOld(array_intersect_key($_POST, array_flip(['group_id', 'unity_id', 'name', 'login', 'email', 'phone'])));
            $this->back('danger', $error);
        }

        $id = (int) $this->users->insert($data);
        Logger::log("Création d'un utilisateur", (int) $this->me()['id'], ['id' => $id, 'login' => $data['login']]);
        $this->back('success', "L'utilisateur « {$data['name']} » a été créé.", '/utilisateurs/' . $id);
    }

    public function info(string $id): void
    {
        $user = $this->users->find((int) $id);
        if ($user === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Utilisateur introuvable']);
            return;
        }
        $gid = (int) $this->me()['group_id'];
        $isSelf = (int) $user['id'] === (int) $this->me()['id'];
        $manageable = $this->manageable($user);

        $group = $user['group_id'] !== null ? $this->groups->find((int) $user['group_id']) : null;
        $unity = null;
        foreach ($this->unities->activeList() as $u) {
            if ((string) $u['id'] === (string) $user['unity_id']) {
                $unity = $u;
            }
        }

        View::render('users/info', [
            'title'      => 'Utilisateur : ' . $user['name'],
            'user'       => $user,
            'groupName'  => $group['name'] ?? '--',
            'unityName'  => $unity['name'] ?? '--',
            'groups'     => $this->assignable(),
            'unities'    => $this->unities->activeList(),
            'isSelf'     => $isSelf,
            'canEdit'    => $manageable && Permission::hasAlias($gid, 'edit_user'),
            'canDelete'  => $manageable && !$isSelf && Permission::hasAlias($gid, 'delete_user'),
            'logs'       => $this->users->recentLogs((int) $user['id']),
        ]);
    }

    public function edit(): void
    {
        $id = (int) Request::input('id');
        $user = $this->users->find($id);
        if ($user === null) {
            $this->back('danger', 'Utilisateur introuvable.');
        }
        $to = '/utilisateurs/' . $id;
        if (!$this->manageable($user)) {
            $this->back('danger', "Vous ne pouvez pas modifier un compte dont le groupe a plus de droits que le vôtre.", $to);
        }

        [$data, $error] = $this->validate($user);
        if ($error !== null) {
            $this->back('danger', $error, $to);
        }

        $this->users->update($id, $data);
        Logger::log("Modification d'un utilisateur", (int) $this->me()['id'], [
            'id' => $id, 'login' => $data['login'], 'mot_de_passe_modifie' => isset($data['password']),
        ]);
        $this->back('success', "L'utilisateur a été modifié.", $to);
    }

    public function delete(): void
    {
        $id = (int) Request::input('id');
        $user = $this->users->find($id);
        if ($user === null) {
            $this->back('danger', 'Utilisateur introuvable.');
        }
        $to = '/utilisateurs/' . $id;
        if ($id === (int) $this->me()['id']) {
            $this->back('danger', 'Vous ne pouvez pas supprimer votre propre compte.', $to);
        }
        if (!$this->manageable($user)) {
            $this->back('danger', "Vous ne pouvez pas supprimer un compte dont le groupe a plus de droits que le vôtre.", $to);
        }

        $this->users->delete($id);
        Logger::log("Suppression d'un utilisateur", (int) $this->me()['id'], ['id' => $id, 'login' => $user['login']]);
        $this->back('success', "L'utilisateur « {$user['name']} » a été supprimé.");
    }
}
