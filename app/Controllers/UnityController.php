<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Permission;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\UnityModel;

final class UnityController
{
    private const PER_PAGE = 30;

    private UnityModel $unities;

    public function __construct()
    {
        $this->unities = new UnityModel();
    }

    private function back(string $type, string $message, string $to = '/unites'): never
    {
        Session::flash($type, $message);
        redirect($to);
    }

    private function nameError(string $name, ?int $exceptId = null): ?string
    {
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            return "Le nom de l'unité est obligatoire (2 à 255 caractères).";
        }
        if ($this->unities->nameTaken($name, $exceptId)) {
            return 'Une unité de production portant ce nom existe déjà.';
        }
        return null;
    }

    public function index(): void
    {
        $gid = (int) Auth::user()['group_id'];
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->unities->search($term, $page, self::PER_PAGE);

        $canCreate = Permission::hasAlias($gid, 'create_unity');
        $old = Session::pullOld();

        View::render('unities/index', [
            'title'      => 'Unités de production',
            'actions'    => $canCreate
                ? '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#create">Créer une unité</button>'
                : '',
            'rows'       => $result['rows'],
            'total'      => $result['total'],
            'page'       => $page,
            'pages'      => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'       => $term,
            'canCreate'  => $canCreate,
            'canInfo'    => Permission::hasAlias($gid, 'info_unity'),
            'old'        => $old,
            'openCreate' => $old !== [],
        ]);
    }

    public function create(): void
    {
        $name = trim(Request::input('name'));
        if ($error = $this->nameError($name)) {
            Session::setOld(['name' => $name]);
            $this->back('danger', $error);
        }

        $id = (int) $this->unities->insert(['name' => $name]);
        Logger::log("Création d'une unité de production", (int) Auth::user()['id'], ['id' => $id, 'name' => $name]);
        $this->back('success', "L'unité « $name » a été créée.", '/unites/' . $id);
    }

    public function info(string $id): void
    {
        $unity = $this->unities->find((int) $id);
        if ($unity === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Unité introuvable']);
            return;
        }
        $gid = (int) Auth::user()['group_id'];
        $usage = $this->unities->usage((int) $unity['id']);

        View::render('unities/info', [
            'title'     => 'Unité : ' . $unity['name'],
            'unity'     => $unity,
            'usage'     => $usage,
            'inUse'     => array_sum($usage) > 0,
            'canEdit'   => Permission::hasAlias($gid, 'edit_unity'),
            'canDelete' => Permission::hasAlias($gid, 'delete_unity'),
        ]);
    }

    public function edit(): void
    {
        $id = (int) Request::input('id');
        $unity = $this->unities->find($id);
        if ($unity === null) {
            $this->back('danger', 'Unité introuvable.');
        }
        $to = '/unites/' . $id;
        $name = trim(Request::input('name'));
        if ($error = $this->nameError($name, $id)) {
            $this->back('danger', $error, $to);
        }

        $this->unities->update($id, ['name' => $name]);
        Logger::log("Modification d'une unité de production", (int) Auth::user()['id'], [
            'id' => $id, 'ancien_nom' => $unity['name'], 'nouveau_nom' => $name,
        ]);
        $this->back('success', "L'unité a été modifiée.", $to);
    }

    public function delete(): void
    {
        $id = (int) Request::input('id');
        $unity = $this->unities->find($id);
        if ($unity === null) {
            $this->back('danger', 'Unité introuvable.');
        }
        $to = '/unites/' . $id;

        // Suppression refusée tant que des éléments actifs y sont rattachés
        // (les caisses, clients, produits et liens bancaires dépendent de l'unité).
        $blocking = array_filter($this->unities->usage($id));
        if ($blocking) {
            $list = [];
            foreach ($blocking as $label => $n) {
                $list[] = "$n " . mb_strtolower($label);
            }
            $this->back('danger', 'Suppression impossible : cette unité contient encore ' . implode(', ', $list) . '.', $to);
        }

        $this->unities->delete($id);
        Logger::log("Suppression d'une unité de production", (int) Auth::user()['id'], ['id' => $id, 'name' => $unity['name']]);
        $this->back('success', "L'unité « {$unity['name']} » a été supprimée.");
    }
}
