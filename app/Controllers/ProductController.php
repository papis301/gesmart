<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Logger;
use App\Core\Permission;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\ProductModel;
use App\Models\UnityModel;

final class ProductController
{
    private const PER_PAGE = 30;

    private ProductModel $products;
    private UnityModel $unities;

    public function __construct()
    {
        $this->products = new ProductModel();
        $this->unities = new UnityModel();
    }

    private function me(): array
    {
        return Auth::user();
    }

    private function back(string $type, string $message, string $to = '/produits'): never
    {
        Session::flash($type, $message);
        redirect($to);
    }

    /**
     * Valide le formulaire produit.
     *
     * @return array{0: ?array, 1: ?string} [données, message d'erreur]
     */
    private function validate(?array $current): array
    {
        $unityId = (int) Request::input('unity_id');
        if (!$this->unities->exists($unityId)) {
            return [null, 'Unité de production invalide.'];
        }
        // Un produit déjà vendu reste rattaché à son unité (cohérence de l'historique).
        if ($current !== null && (int) $current['unity_id'] !== $unityId && $this->products->salesLinesCount((int) $current['id']) > 0) {
            return [null, "L'unité d'un produit déjà vendu ne peut plus être changée."];
        }

        $name = trim(Request::input('name'));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 255) {
            return [null, 'Le nom du produit est obligatoire (2 à 255 caractères).'];
        }

        $price = str_replace([' ', ','], ['', '.'], trim(Request::input('price')));
        if ($price === '' || !is_numeric($price) || (float) $price < 0 || (float) $price > 100000000) {
            return [null, 'Le prix unitaire doit être un nombre positif (FCFA).'];
        }

        $stock = trim(Request::input('stock'));
        if ($stock === '' || !ctype_digit($stock) || (int) $stock > 2147483647) {
            return [null, 'Le stock doit être un nombre entier positif.'];
        }

        return [[
            'unity_id' => $unityId,
            'name'     => $name,
            'price'    => round((float) $price, 2),
            'stock'    => (int) $stock,
        ], null];
    }

    public function index(): void
    {
        $gid = (int) $this->me()['group_id'];
        $term = trim((string) ($_GET['s'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        $filter = [];
        $raw = $_GET['unity_filter'] ?? [];
        foreach ((array) $raw as $v) {
            if (is_string($v) && ctype_digit($v)) {
                $filter[] = (int) $v;
            }
        }
        $filter = array_values(array_unique($filter));

        $result = $this->products->search($term, $filter, $page, self::PER_PAGE);
        $canCreate = Permission::hasAlias($gid, 'create_product');
        $old = Session::pullOld();

        View::render('products/index', [
            'title'      => 'Produits',
            'actions'    => $canCreate
                ? '<button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#create">Créer un produit</button>'
                : '',
            'rows'       => $result['rows'],
            'total'      => $result['total'],
            'page'       => $page,
            'pages'      => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'term'       => $term,
            'filter'     => $filter,
            'unities'    => $this->unities->activeList(),
            'canCreate'  => $canCreate,
            'canInfo'    => Permission::hasAlias($gid, 'info_product'),
            'old'        => $old !== [] ? $old : ['unity_id' => (string) ($this->me()['unity_id'] ?? '')],
            'openCreate' => $old !== [],
        ]);
    }

    public function create(): void
    {
        [$data, $error] = $this->validate(null);
        if ($error !== null) {
            Session::setOld(array_intersect_key($_POST, array_flip(['unity_id', 'name', 'price', 'stock'])));
            $this->back('danger', $error);
        }

        $id = (int) $this->products->insert($data);
        Logger::log("Création d'un produit", (int) $this->me()['id'], ['id' => $id] + $data);
        $this->back('success', "Le produit « {$data['name']} » a été créé.", '/produits/' . $id);
    }

    public function info(string $id): void
    {
        $product = $this->products->find((int) $id);
        if ($product === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Produit introuvable']);
            return;
        }
        $gid = (int) $this->me()['group_id'];

        View::render('products/info', [
            'title'      => 'Produit : ' . $product['name'],
            'product'    => $product,
            'unityName'  => $this->products->unityName($product['unity_id'] !== null ? (int) $product['unity_id'] : null),
            'unities'    => $this->unities->activeList(),
            'salesLines' => $this->products->salesLinesCount((int) $product['id']),
            'canEdit'    => Permission::hasAlias($gid, 'edit_product'),
            'canDelete'  => Permission::hasAlias($gid, 'delete_product'),
        ]);
    }

    public function edit(): void
    {
        $id = (int) Request::input('id');
        $product = $this->products->find($id);
        if ($product === null) {
            $this->back('danger', 'Produit introuvable.');
        }
        $to = '/produits/' . $id;

        [$data, $error] = $this->validate($product);
        if ($error !== null) {
            $this->back('danger', $error, $to);
        }

        $this->products->update($id, $data);
        // Le prix et le stock sont sensibles : on garde l'ancienne et la nouvelle valeur dans le journal.
        Logger::log("Modification d'un produit", (int) $this->me()['id'], [
            'id'    => $id,
            'nom'   => $data['name'],
            'prix'  => [(float) $product['price'], $data['price']],
            'stock' => [(int) $product['stock'], $data['stock']],
        ]);
        $this->back('success', 'Le produit a été modifié.', $to);
    }

    public function delete(): void
    {
        $id = (int) Request::input('id');
        $product = $this->products->find($id);
        if ($product === null) {
            $this->back('danger', 'Produit introuvable.');
        }

        // Suppression logique : l'historique des ventes reste intact.
        $this->products->delete($id);
        Logger::log("Suppression d'un produit", (int) $this->me()['id'], ['id' => $id, 'name' => $product['name']]);
        $this->back('success', "Le produit « {$product['name']} » a été supprimé.");
    }
}
