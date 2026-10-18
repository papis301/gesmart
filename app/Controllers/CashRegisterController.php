<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Permission;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\CashRegisterModel;
use App\Models\UnityModel;

final class CashRegisterController
{
    private const PER_PAGE = 30;

    private CashRegisterModel $registers;
    private UnityModel $unities;

    public function __construct()
    {
        $this->registers = new CashRegisterModel();
        $this->unities = new UnityModel();
    }

    private function me(): array
    {
        return Auth::user();
    }

    private function back(string $type, string $message, string $to = '/caisses'): never
    {
        Session::flash($type, $message);
        redirect($to);
    }

    /** Un solde à zéro (à l'arrondi près) : la caisse ne contient plus d'argent. */
    private static function isEmpty(mixed $balance): bool
    {
        return abs((float) $balance) < 0.5;
    }

    /**
     * Valide le formulaire caisse.
     *
     * @return array{0: ?array, 1: ?string} [données, message d'erreur]
     */
    private function validate(?array $current): array
    {
        $exceptId = $current !== null ? (int) $current['id'] : null;

        $unityId = (int) Request::input('unity_id');
        if (!$this->unities->exists($unityId)) {
            return [null, 'Unité de production invalide.'];
        }

        $name = trim(Request::input('name'));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            return [null, 'Le nom de la caisse est obligatoire (2 à 255 caractères).'];
        }
        if ($this->registers->nameTaken($name, $exceptId)) {
            return [null, 'Une caisse portant ce nom existe déjà.'];
        }

        $cashierId = (int) Request::input('user_id');
        $managerId = (int) Request::input('admin_user_id');

        // Une valeur déjà enregistrée reste acceptée même si la personne a été suspendue depuis.
        $cashierChanged = $current === null || (int) $current['user_id'] !== $cashierId;
        $managerChanged = $current === null || (int) $current['admin_user_id'] !== $managerId;
        if ($cashierChanged && !$this->registers->userIsActive($cashierId)) {
            return [null, 'Caissier invalide ou suspendu.'];
        }
        if ($managerChanged && !$this->registers->userIsActive($managerId)) {
            return [null, 'Responsable invalide ou suspendu.'];
        }
        if ($other = $this->registers->registerOfCashier($cashierId, $exceptId)) {
            return [null, "Ce caissier est déjà affecté à la caisse « $other »."];
        }

        if ($current !== null) {
            // L'historique reste cohérent : une caisse utilisée garde son unité.
            if ((int) $current['unity_id'] !== $unityId && array_sum($this->registers->history((int) $current['id'])) > 0) {
                return [null, "L'unité d'une caisse qui a déjà des opérations ne peut plus être changée."];
            }
            // L'argent en caisse reste sous la responsabilité de son caissier jusqu'à l'arrêté.
            if ((int) $current['user_id'] !== $cashierId && !self::isEmpty($current['balance'])) {
                return [null, 'Le caissier ne peut pas être changé tant que la caisse contient de l\'argent : faites d\'abord un arrêté de caisse.'];
            }
        }

        return [[
            'unity_id'      => $unityId,
            'name'          => $name,
            'user_id'       => $cashierId,
            'admin_user_id' => $managerId,
        ], null];
    }

    public function index(): void
    {
        $gid = (int) $this->me()['group_id'];
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $filter = [];
        foreach ((array) ($_GET['unity_filter'] ?? []) as $v) {
            if (is_string($v) && ctype_digit($v)) {
                $filter[] = (int) $v;
            }
        }
        $filter = array_values(array_unique($filter));

        $result = $this->registers->search($term, $filter, $page, self::PER_PAGE);
        $canCreate = Permission::hasAlias($gid, 'create_register');
        $old = Session::pullOld();

        View::render('cash_registers/index', [
            'title'      => 'Caisses',
            'actions'    => $canCreate
                ? '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#create">Créer une caisse</button>'
                : '',
            'rows'       => $result['rows'],
            'total'      => $result['total'],
            'page'       => $page,
            'pages'      => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'       => $term,
            'filter'     => $filter,
            'unities'    => $this->unities->activeList(),
            'users'      => $canCreate ? $this->registers->assignableUsers() : [],
            'canCreate'  => $canCreate,
            'canInfo'    => Permission::hasAlias($gid, 'info_cash_register'),
            'old'        => $old !== [] ? $old : ['unity_id' => (string) ($this->me()['unity_id'] ?? '')],
            'openCreate' => $old !== [],
        ]);
    }

    public function create(): void
    {
        [$data, $error] = $this->validate(null);
        if ($error !== null) {
            Session::setOld(array_intersect_key($_POST, array_flip(['unity_id', 'name', 'user_id', 'admin_user_id'])));
            $this->back('danger', $error);
        }

        // Une nouvelle caisse démarre vide et ouverte (solde et coupures à 0 par défaut dans la table).
        $id = (int) $this->registers->insert($data);
        Logger::log("Création d'une caisse", (int) $this->me()['id'], ['id' => $id] + $data);
        $this->back('success', "La caisse « {$data['name']} » a été créée.", '/caisses/' . $id);
    }

    public function info(string $id): void
    {
        $register = $this->registers->detail((int) $id);
        if ($register === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Caisse introuvable']);
            return;
        }
        $gid = (int) $this->me()['group_id'];
        $canEdit = Permission::hasAlias($gid, 'edit_register');
        $history = $this->registers->history((int) $register['id']);

        View::render('cash_registers/info', [
            'title'     => 'Caisse : ' . $register['name'],
            'register'  => $register,
            'unities'   => $this->unities->activeList(),
            'users'     => $this->registers->assignableUsers([(int) $register['user_id'], (int) $register['admin_user_id']]),
            'history'   => $history,
            'hasHistory' => array_sum($history) > 0,
            'empty'     => self::isEmpty($register['balance']),
            'denominations' => CashRegisterModel::DENOMINATIONS,
            'canEdit'   => $canEdit,
            'canDelete' => Permission::hasAlias($gid, 'delete_register'),
        ]);
    }

    public function edit(): void
    {
        $id = (int) Request::input('id');
        $register = $this->registers->find($id);
        if ($register === null) {
            $this->back('danger', 'Caisse introuvable.');
        }
        $to = '/caisses/' . $id;

        [$data, $error] = $this->validate($register);
        if ($error !== null) {
            $this->back('danger', $error, $to);
        }

        $this->registers->update($id, $data);
        Logger::log("Modification d'une caisse", (int) $this->me()['id'], [
            'id'          => $id,
            'nom'         => [$register['name'], $data['name']],
            'unite'       => [(int) $register['unity_id'], $data['unity_id']],
            'caissier'    => [(int) $register['user_id'], $data['user_id']],
            'responsable' => [(int) $register['admin_user_id'], $data['admin_user_id']],
        ]);
        $this->back('success', 'La caisse a été modifiée.', $to);
    }

    public function delete(): void
    {
        $id = (int) Request::input('id');
        $register = $this->registers->find($id);
        if ($register === null) {
            $this->back('danger', 'Caisse introuvable.');
        }
        $to = '/caisses/' . $id;

        // On ne supprime pas une caisse qui contient encore de l'argent (il faudrait d'abord le transférer).
        if (!self::isEmpty($register['balance'])) {
            $this->back('danger', 'Suppression impossible : la caisse contient encore ' . fcfa($register['balance']) . '. Videz-la d\'abord (transfert, versement ou arrêté de caisse).', $to);
        }

        // Suppression logique : l'historique des opérations reste intact.
        $this->registers->delete($id);
        Logger::log("Suppression d'une caisse", (int) $this->me()['id'], ['id' => $id, 'name' => $register['name']]);
        $this->back('success', "La caisse « {$register['name']} » a été supprimée.");
    }
}
