<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Permission;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\ClientModel;
use App\Models\UnityModel;

final class ClientController
{
    private const PER_PAGE = 30;
    private const MAX_AMOUNT = 1000000000000;

    private ClientModel $clients;
    private UnityModel $unities;

    public function __construct()
    {
        $this->clients = new ClientModel();
        $this->unities = new UnityModel();
    }

    private function me(): array
    {
        return Auth::user();
    }

    private function back(string $type, string $message, string $to = '/clients'): never
    {
        Session::flash($type, $message);
        redirect($to);
    }

    /** Solde = crédit + FAGAROU/remboursements. */
    private static function solde(array $client): float
    {
        return (float) $client['credit'] + (float) $client['balance'];
    }

    /** Montant saisi (espaces et virgule acceptés) ou null si invalide. */
    private static function amount(string $raw): ?float
    {
        $raw = str_replace([' ', ','], ['', '.'], trim($raw));
        if ($raw === '') {
            return 0.0;
        }
        if (!is_numeric($raw) || abs((float) $raw) > self::MAX_AMOUNT) {
            return null;
        }
        return round((float) $raw, 2);
    }

    /**
     * Valide le formulaire client (création : $current = null).
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
        if ($current !== null && (int) $current['unity_id'] !== $unityId && array_sum($this->clients->usage((int) $current['id'])) > 0) {
            return [null, "L'unité d'un client qui a déjà des opérations ne peut plus être changée."];
        }

        $name = mb_strtoupper(trim(Request::input('name')));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            return [null, 'Le nom du client est obligatoire (2 à 255 caractères).'];
        }

        $email = trim(Request::input('email'));
        if ($email !== '' && (mb_strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            return [null, "L'adresse email n'est pas valide."];
        }

        $phone = trim(Request::input('phone'));
        if ($phone !== '' && !preg_match('/^[0-9+\s().\-\/]{6,30}$/', $phone)) {
            return [null, 'Le numéro de téléphone n\'est pas valide.'];
        }

        $address = trim(Request::input('address'));
        if (mb_strlen($address) > 255) {
            return [null, "L'adresse ne doit pas dépasser 255 caractères."];
        }

        // NINEA et NID : uniques dans la base ; une valeur vide doit rester NULL (sinon '' bloquerait le client suivant).
        $ids = [];
        foreach (['ninea' => 'Le NINEA', 'nid' => "Le numéro d'identité nationale"] as $col => $label) {
            $value = trim(Request::input($col));
            if (mb_strlen($value) > 255) {
                return [null, "$label est trop long."];
            }
            if ($value !== '' && $this->clients->taken($col, $value, $exceptId)) {
                return [null, "$label « $value » est déjà utilisé par un autre client (même supprimé)."];
            }
            $ids[$col] = $value === '' ? null : $value;
        }

        $data = [
            'unity_id' => $unityId,
            'name'     => $name,
            'email'    => $email === '' ? null : $email,
            'phone'    => $phone === '' ? null : $phone,
            'ninea'    => $ids['ninea'],
            'nid'      => $ids['nid'],
            'address'  => $address,
        ];

        if ($current === null) {
            // Soldes de départ : la suite est gérée par les opérations (ventes, encaissements, transferts...).
            $balance = self::amount(Request::input('balance'));
            $credit = self::amount(Request::input('credit'));
            if ($balance === null || $balance < 0) {
                return [null, 'Le montant FAGAROU/remboursement initial doit être un nombre positif ou nul.'];
            }
            if ($credit === null || $credit > 0) {
                return [null, 'Le crédit initial doit être un nombre négatif ou nul (ce que le client doit).'];
            }
            $data['balance'] = $balance;
            $data['credit'] = $credit;
        }

        return [$data, null];
    }

    public function index(): void
    {
        $gid = (int) $this->me()['group_id'];
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $type = in_array($_GET['type_filter'] ?? '', ['debit', 'credit'], true) ? (string) $_GET['type_filter'] : '';

        $filter = [];
        foreach ((array) ($_GET['unity_filter'] ?? []) as $v) {
            if (is_string($v) && ctype_digit($v)) {
                $filter[] = (int) $v;
            }
        }
        $filter = array_values(array_unique($filter));

        $result = $this->clients->search($term, $filter, $type, $page, self::PER_PAGE);
        $canCreate = Permission::hasAlias($gid, 'create_client');
        $old = Session::pullOld();

        View::render('clients/index', [
            'title'      => 'Clients',
            'actions'    => $canCreate
                ? '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#create">Créer un client</button>'
                : '',
            'rows'       => $result['rows'],
            'total'      => $result['total'],
            'sumBalance' => $result['sum_balance'],
            'sumCredit'  => $result['sum_credit'],
            'page'       => $page,
            'pages'      => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'       => $term,
            'filter'     => $filter,
            'type'       => $type,
            'unities'    => $this->unities->activeList(),
            'canCreate'  => $canCreate,
            'canInfo'    => Permission::hasAlias($gid, 'info_client'),
            'old'        => $old !== [] ? $old : ['unity_id' => (string) ($this->me()['unity_id'] ?? ''), 'balance' => '0', 'credit' => '0'],
            'openCreate' => $old !== [],
        ]);
    }

    public function create(): void
    {
        [$data, $error] = $this->validate(null);
        if ($error !== null) {
            Session::setOld(array_intersect_key($_POST, array_flip(['unity_id', 'balance', 'credit', 'ninea', 'nid', 'name', 'email', 'phone', 'address'])));
            $this->back('danger', $error);
        }

        // Un client créé par quelqu'un qui n'a pas le droit « Valider » reste en attente de validation.
        $me = $this->me();
        $canValidate = Permission::hasAlias((int) $me['group_id'], 'validate_client');
        $data['validated'] = $canValidate ? 1 : 0;
        $data['validated_by'] = $canValidate ? (int) $me['id'] : null;

        $id = $this->clients->createClient($data);
        Logger::log("Création d'un client", (int) $me['id'], ['id' => $id] + $data);
        $this->back('success', "Le client « {$data['name']} » a été créé (compte nº $id)."
            . ($canValidate ? '' : ' Il doit être validé avant utilisation.'), '/clients/' . $id);
    }

    public function info(string $id): void
    {
        $client = $this->clients->detail((int) $id);
        if ($client === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Client introuvable']);
            return;
        }
        $gid = (int) $this->me()['group_id'];
        $usage = $this->clients->usage((int) $client['id']);

        View::render('clients/info', [
            'title'       => 'Client : ' . $client['name'],
            'client'      => $client,
            'solde'       => self::solde($client),
            'unities'     => $this->unities->activeList(),
            'usage'       => $usage,
            'hasHistory'  => array_sum($usage) > 0,
            'canEdit'     => Permission::hasAlias($gid, 'edit_client'),
            'canDelete'   => Permission::hasAlias($gid, 'delete_client'),
            'canValidate' => Permission::hasAlias($gid, 'validate_client'),
        ]);
    }

    public function edit(): void
    {
        $id = (int) Request::input('id');
        $client = $this->clients->find($id);
        if ($client === null) {
            $this->back('danger', 'Client introuvable.');
        }
        $to = '/clients/' . $id;

        [$data, $error] = $this->validate($client);
        if ($error !== null) {
            $this->back('danger', $error, $to);
        }

        // Les soldes (crédit, FAGAROU/remboursements) ne se modifient pas ici : seules les opérations les font évoluer.
        $this->clients->update($id, $data);
        Logger::log("Modification d'un client", (int) $this->me()['id'], [
            'id'    => $id,
            'nom'   => [$client['name'], $data['name']],
            'unite' => [(int) $client['unity_id'], $data['unity_id']],
            'ninea' => [$client['ninea'], $data['ninea']],
            'nid'   => [$client['nid'], $data['nid']],
        ]);
        $this->back('success', 'Le client a été modifié.', $to);
    }

    public function validate_client(): void
    {
        $id = (int) Request::input('id');
        $client = $this->clients->find($id);
        if ($client === null) {
            $this->back('danger', 'Client introuvable.');
        }
        $to = '/clients/' . $id;
        if ((int) $client['validated'] === 1) {
            $this->back('info', 'Ce client est déjà validé.', $to);
        }

        $me = $this->me();
        $this->clients->update($id, ['validated' => 1, 'validated_by' => (int) $me['id']]);
        Logger::log("Validation d'un client", (int) $me['id'], ['id' => $id, 'name' => $client['name']]);
        $this->back('success', "Le client « {$client['name']} » a été validé.", $to);
    }

    public function delete(): void
    {
        $id = (int) Request::input('id');
        $client = $this->clients->find($id);
        if ($client === null) {
            $this->back('danger', 'Client introuvable.');
        }
        $to = '/clients/' . $id;

        // Un compte qui doit ou qui détient encore de l'argent ne peut pas disparaître.
        $solde = self::solde($client);
        if (abs($solde) >= 0.5) {
            $this->back('danger', 'Suppression impossible : le solde du compte est de ' . fcfa($solde) . '. Il doit être à 0.', $to);
        }

        // Suppression logique : l'historique des opérations reste intact (le NINEA / NID restent réservés).
        $this->clients->delete($id);
        Logger::log("Suppression d'un client", (int) $this->me()['id'], ['id' => $id, 'name' => $client['name']]);
        $this->back('success', "Le client « {$client['name']} » a été supprimé.");
    }
}
