<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Permission;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\BankModel;
use App\Models\UnityModel;

final class BankController
{
    private const PER_PAGE = 30;

    private BankModel $banks;
    private UnityModel $unities;

    public function __construct()
    {
        $this->banks = new BankModel();
        $this->unities = new UnityModel();
    }

    private function me(): array
    {
        return Auth::user();
    }

    private function back(string $type, string $message, string $to = '/banques'): never
    {
        Session::flash($type, $message);
        redirect($to);
    }

    private static function isEmpty(mixed $balance): bool
    {
        return abs((float) $balance) < 0.5;
    }

    /** Champs communs création / modification. @return array{0: ?array, 1: ?string} */
    private function validateCommon(?array $current): array
    {
        $exceptId = $current !== null ? (string) $current['id'] : null;

        $name = trim(Request::input('name'));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            return [null, 'Le nom de la banque est obligatoire (2 à 255 caractères).'];
        }
        $name = mb_strtoupper($name);
        if ($this->banks->nameTaken($name, $exceptId)) {
            return [null, 'Une banque portant ce nom existe déjà.'];
        }

        $email = trim(Request::input('email'));
        if ($email !== '' && (mb_strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            return [null, 'Adresse email invalide.'];
        }
        $phone = trim(Request::input('phone'));
        if ($phone !== '' && !preg_match('/^\+?[0-9 .\-]{6,20}$/', $phone)) {
            return [null, 'Numéro de téléphone invalide.'];
        }

        $agentId = (int) Request::input('user_id');
        $managerId = (int) Request::input('admin_user_id');
        $agentChanged = $current === null || (int) $current['user_id'] !== $agentId;
        $managerChanged = $current === null || (int) $current['admin_user_id'] !== $managerId;
        if ($agentChanged && !$this->banks->userIsActive($agentId)) {
            return [null, 'Agent invalide ou suspendu.'];
        }
        if ($managerChanged && !$this->banks->userIsActive($managerId)) {
            return [null, 'Gestionnaire invalide ou suspendu.'];
        }

        return [[
            'name'          => $name,
            'email'         => $email !== '' ? $email : null,
            'phone'         => $phone !== '' ? $phone : null,
            'user_id'       => $agentId,
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

        $result = $this->banks->search($term, $filter, $page, self::PER_PAGE);
        $canCreate = Permission::hasAlias($gid, 'create_bank');
        $old = Session::pullOld();

        View::render('banks/index', [
            'title'      => 'Banques',
            'actions'    => $canCreate
                ? '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#create">Créer une banque</button>'
                : '',
            'rows'       => $result['rows'],
            'total'      => $result['total'],
            'page'       => $page,
            'pages'      => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'       => $term,
            'filter'     => $filter,
            'unities'    => $this->unities->activeList(),
            'users'      => $canCreate ? $this->banks->assignableUsers() : [],
            'canCreate'  => $canCreate,
            'canInfo'    => Permission::hasAlias($gid, 'info_bank'),
            'old'        => $old !== [] ? $old : ['balance' => '0', 'credit' => '0', 'warranty' => '0'],
            'openCreate' => $old !== [],
        ]);
    }

    public function create(): void
    {
        $keys = ['id', 'name', 'email', 'phone', 'user_id', 'admin_user_id', 'balance', 'credit', 'warranty'];
        $fail = function (string $msg) use ($keys): never {
            Session::setOld(array_intersect_key($_POST, array_flip($keys)));
            $this->back('danger', $msg);
        };

        [$data, $error] = $this->validateCommon(null);
        if ($error !== null) {
            $fail($error);
        }

        $id = mb_strtoupper(trim(Request::input('id')));
        if (!preg_match('/^[A-Z0-9 \/\-]{4,255}$/', $id)) {
            $fail('Numéro de compte invalide (4 caractères minimum : lettres, chiffres, espace, / ou -).');
        }
        if ($this->banks->idTaken($id)) {
            $fail('Ce numéro de compte existe déjà.');
        }

        $amounts = [];
        foreach (['balance', 'credit', 'warranty'] as $k) {
            $raw = trim(Request::input($k));
            $amounts[$k] = $raw === '' ? 0.0 : (is_numeric($raw) ? (float) $raw : null);
            if ($amounts[$k] === null || abs($amounts[$k]) > 1e13) {
                $fail('Montant invalide pour « ' . ['balance' => 'solde', 'credit' => 'crédit', 'warranty' => 'garantie'][$k] . ' ».');
            }
        }
        if ($amounts['balance'] < 0) {
            $fail('Le solde initial ne peut pas être négatif.');
        }
        if ($amounts['credit'] > 0) {
            $fail('Le crédit s\'exprime par un montant négatif ou nul.');
        }
        if ($amounts['warranty'] < 0) {
            $fail('La garantie ne peut pas être négative.');
        }

        $data = ['id' => $id] + $data + $amounts;
        $this->banks->insert($data);
        Logger::log("Création d'une banque", (int) $this->me()['id'], $data);
        $this->back('success', "La banque « {$data['name']} » a été créée.", '/banques/' . rawurlencode($id));
    }

    public function info(string $id): void
    {
        $id = rawurldecode($id);
        $bank = $this->banks->detail($id);
        if ($bank === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Banque introuvable']);
            return;
        }
        $gid = (int) $this->me()['group_id'];
        $history = $this->banks->history($id);
        $links = $this->banks->unityLinks($id);
        $linked = array_map(static fn($l) => (int) $l['unity_id'], $links);

        View::render('banks/info', [
            'title'      => 'Banque : ' . $bank['name'],
            'bank'       => $bank,
            'users'      => $this->banks->assignableUsers([(int) $bank['user_id'], (int) $bank['admin_user_id']]),
            'credits'    => $this->banks->credits($id),
            'links'      => $links,
            'freeUnities' => array_values(array_filter($this->unities->activeList(), static fn($u) => !in_array((int) $u['id'], $linked, true))),
            'history'    => $history,
            'empty'      => self::isEmpty($bank['balance']),
            'canEdit'    => Permission::hasAlias($gid, 'edit_bank'),
            'canDelete'  => Permission::hasAlias($gid, 'delete_bank'),
            'canLink'    => Permission::hasAlias($gid, 'create_bank_unity_link'),
            'canUnlink'  => Permission::hasAlias($gid, 'delete_bank_unity_link'),
        ]);
    }

    public function edit(): void
    {
        $id = Request::input('id');
        $bank = $this->banks->find($id);
        if ($bank === null) {
            $this->back('danger', 'Banque introuvable.');
        }
        $to = '/banques/' . rawurlencode($id);

        [$data, $error] = $this->validateCommon($bank);
        if ($error !== null) {
            $this->back('danger', $error, $to);
        }

        $this->banks->update($id, $data);
        Logger::log("Modification d'une banque", (int) $this->me()['id'], [
            'id'          => $id,
            'nom'         => [$bank['name'], $data['name']],
            'agent'       => [(int) $bank['user_id'], $data['user_id']],
            'gestionnaire' => [(int) $bank['admin_user_id'], $data['admin_user_id']],
        ]);
        $this->back('success', 'La banque a été modifiée.', $to);
    }

    public function delete(): void
    {
        $id = Request::input('id');
        $bank = $this->banks->find($id);
        if ($bank === null) {
            $this->back('danger', 'Banque introuvable.');
        }
        $to = '/banques/' . rawurlencode($id);

        if (!self::isEmpty($bank['balance'])) {
            $this->back('danger', 'Suppression impossible : le compte présente encore un solde de ' . fcfa($bank['balance']) . '.', $to);
        }
        if (abs((float) $bank['credit']) >= 0.5) {
            $this->back('danger', 'Suppression impossible : le compte a un crédit en cours (' . fcfa($bank['credit']) . ').', $to);
        }

        // Suppression logique : l'historique des opérations reste intact.
        $this->banks->delete($id);
        Logger::log("Suppression d'une banque", (int) $this->me()['id'], ['id' => $id, 'name' => $bank['name']]);
        $this->back('success', "La banque « {$bank['name']} » a été supprimée.");
    }

    public function create_bank_unity_link(): void
    {
        $id = Request::input('bank_id');
        $to = '/banques/' . rawurlencode($id);
        if ($this->banks->find($id) === null) {
            $this->back('danger', 'Banque introuvable.');
        }
        $unityId = (int) Request::input('unity_id');
        if (!$this->unities->exists($unityId)) {
            $this->back('danger', 'Unité de production invalide.', $to);
        }
        if ($this->banks->linkExists($id, $unityId)) {
            $this->back('danger', 'Cette banque est déjà liée à cette unité.', $to);
        }
        $this->banks->addLink($id, $unityId);
        Logger::log('Création d\'une relation Banque/Unité de production', (int) $this->me()['id'], ['bank_id' => $id, 'unity_id' => $unityId]);
        $this->back('success', 'La relation a été créée.', $to);
    }

    public function delete_bank_unity_link(): void
    {
        $id = Request::input('bank_id');
        $to = '/banques/' . rawurlencode($id);
        if ($this->banks->find($id) === null) {
            $this->back('danger', 'Banque introuvable.');
        }
        $linkId = (int) Request::input('link_id');
        if ($this->banks->removeLink($id, $linkId) === 0) {
            $this->back('danger', 'Relation introuvable.', $to);
        }
        Logger::log('Suppression d\'une relation Banque/Unité de production', (int) $this->me()['id'], ['bank_id' => $id, 'link_id' => $linkId]);
        $this->back('success', 'La relation a été supprimée.', $to);
    }
}
