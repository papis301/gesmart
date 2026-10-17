<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Permission;
use App\Core\Request;
use App\Core\RulesCatalog;
use App\Core\Session;
use App\Core\View;
use App\Models\GroupModel;

final class GroupController
{
    private const PER_PAGE = 25;
    /** Droits qu'un groupe ne peut pas se retirer à lui-même (évite de se verrouiller). */
    private const SELF_PROTECTED = ['groups', 'info_group', 'edit_group'];

    private GroupModel $groups;

    public function __construct()
    {
        $this->groups = new GroupModel();
    }

    private function myGroupId(): int
    {
        return (int) Auth::user()['group_id'];
    }

    /** Droits que l'utilisateur connecté peut accorder (il ne peut donner que ce qu'il possède). */
    private function grantable(): array
    {
        return array_values(array_intersect($this->groups->aliasesOf($this->myGroupId()), array_keys(RulesCatalog::byAlias())));
    }

    private function back(string $type, string $message, string $to = '/groupes'): never
    {
        Session::flash($type, $message);
        redirect($to);
    }

    /** Valide le nom ; renvoie le message d'erreur ou null. */
    private function nameError(string $name, ?int $exceptId = null): ?string
    {
        if ($name === '') {
            return 'Le nom du groupe est obligatoire.';
        }
        if (mb_strlen($name) > 100) {
            return 'Le nom du groupe est trop long (100 caractères maximum).';
        }
        if ($this->groups->nameTaken($name, $exceptId)) {
            return 'Un groupe portant ce nom existe déjà.';
        }
        return null;
    }

    public function index(): void
    {
        $gid = $this->myGroupId();
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->groups->search($term, $page, self::PER_PAGE);

        $canCreate = Permission::hasAlias($gid, 'create_group');
        $actions = $canCreate
            ? '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#create">Créer un groupe</button>'
            : '';

        View::render('groups/index', [
            'title'     => 'Groupes',
            'actions'   => $actions,
            'rows'      => $result['rows'],
            'total'     => $result['total'],
            'page'      => $page,
            'pages'     => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'      => $term,
            'canCreate' => $canCreate,
            'canInfo'   => Permission::hasAlias($gid, 'info_group'),
            'resources' => RulesCatalog::resources(),
            'grantable' => $this->grantable(),
        ]);
    }

    public function create(): void
    {
        $name = trim(Request::input('name'));
        if ($error = $this->nameError($name)) {
            $this->back('danger', $error);
        }

        $id = (int) $this->groups->insert(['name' => $name]);
        $this->groups->syncRules($id, RulesCatalog::aliasesFromForm($_POST['rules'] ?? []), $this->grantable());

        Logger::log("Création d'un groupe", (int) Auth::user()['id'], ['id' => $id, 'name' => $name]);
        $this->back('success', 'Le groupe « ' . $name . ' » a été créé.', '/groupes/' . $id);
    }

    public function info(string $id): void
    {
        $group = $this->groups->find((int) $id);
        if ($group === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Groupe introuvable']);
            return;
        }
        $gid = $this->myGroupId();
        $checked = array_fill_keys($this->groups->aliasesOf((int) $group['id']), true);
        $grantable = array_fill_keys($this->grantable(), true);

        View::render('groups/info', [
            'title'      => 'Groupe : ' . $group['name'],
            'group'      => $group,
            'resources'  => RulesCatalog::resources(),
            'checked'    => $checked,
            'grantable'  => $grantable,
            'usersCount' => $this->groups->usersCount((int) $group['id']),
            'canEdit'    => Permission::hasAlias($gid, 'edit_group'),
            'canDelete'  => Permission::hasAlias($gid, 'delete_group') && (int) $group['id'] !== $gid,
        ]);
    }

    public function edit(): void
    {
        $id = (int) Request::input('id');
        $group = $this->groups->find($id);
        if ($group === null) {
            $this->back('danger', 'Groupe introuvable.');
        }
        $back = '/groupes/' . $id;
        $name = trim(Request::input('name'));
        if ($error = $this->nameError($name, $id)) {
            $this->back('danger', $error, $back);
        }

        $protected = $id === $this->myGroupId() ? self::SELF_PROTECTED : [];
        $this->groups->update($id, ['name' => $name]);
        $this->groups->syncRules($id, RulesCatalog::aliasesFromForm($_POST['rules'] ?? []), $this->grantable(), $protected);

        Logger::log("Modification d'un groupe", (int) Auth::user()['id'], ['id' => $id, 'name' => $name]);
        $this->back('success', 'Le groupe a été modifié.', $back);
    }

    public function delete(): void
    {
        $id = (int) Request::input('id');
        $group = $this->groups->find($id);
        if ($group === null) {
            $this->back('danger', 'Groupe introuvable.');
        }
        if ($id === $this->myGroupId()) {
            $this->back('danger', 'Vous ne pouvez pas supprimer votre propre groupe.', '/groupes/' . $id);
        }
        $count = $this->groups->usersCount($id);
        if ($count > 0) {
            $this->back('danger', "Suppression impossible : $count utilisateur(s) appartiennent encore à ce groupe.", '/groupes/' . $id);
        }

        $this->groups->delete($id);
        Logger::log("Suppression d'un groupe", (int) Auth::user()['id'], ['id' => $id, 'name' => $group['name']]);
        $this->back('success', 'Le groupe « ' . $group['name'] . ' » a été supprimé.');
    }
}
