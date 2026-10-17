<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Permission;
use App\Core\Request;

/** Échappe une valeur pour l'affichage HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim((string) Config::get('app.base_path', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function asset(string $file): string
{
    if (Config::get('assets.cdn', true)) {
        $cdn = [
            'tabler.min.css' => 'https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css',
            'tabler.min.js'  => 'https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js',
        ];
        if (isset($cdn[$file])) {
            return $cdn[$file];
        }
    }
    return url('assets/' . $file);
}

function asset_exists(string $file): bool
{
    return is_file(BASE_DIR . '/public/assets/' . $file);
}

/** Icône Tabler en SVG (voir app/icons.php). */
function icon(string $name, string $class = 'icon'): string
{
    static $icons = null;
    $icons ??= require APP_DIR . '/icons.php';
    if (!isset($icons[$name])) {
        return '';
    }
    $paths = '<path stroke="none" d="M0 0h24v24H0z" fill="none"/>';
    foreach ($icons[$name] as $d) {
        $paths .= '<path d="' . e($d) . '"/>';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="'
        . e($class) . '">' . $paths . '</svg>';
}

/** Montant en FCFA : 1 234 567 FCFA. */
function fcfa(int|float|string|null $amount): string
{
    return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
}

/** Date SQL -> 16/10/2026 11:05:50. */
function dt(?string $date): string
{
    $t = $date ? strtotime($date) : false;
    return $t ? date('d/m/Y H:i:s', $t) : '';
}

/**
 * Menu filtré selon les droits du groupe (table rules).
 * @return array<string, array<int, array>> section => éléments (lien ou liste déroulante)
 */
function menu(): array
{
    $user = Auth::user();
    if ($user === null) {
        return [];
    }
    $gid = (int) $user['group_id'];
    $ready = (array) (require APP_DIR . '/menu_ready.php');
    $sections = require APP_DIR . '/menu.php';
    $out = [];

    foreach ($sections as $title => $items) {
        $visible = [];
        foreach ($items as $item) {
            if (isset($item['children'])) {
                $children = [];
                foreach ($item['children'] as [$label, $path]) {
                    if (Permission::allowed($gid, 'GET', $path)) {
                        $children[] = ['label' => $label, 'path' => $path, 'ready' => in_array($path, $ready, true)];
                    }
                }
                if ($children) {
                    $visible[] = ['label' => $item['label'], 'icon' => $item['icon'], 'children' => $children];
                }
            } elseif (Permission::allowed($gid, 'GET', $item['path'])) {
                $visible[] = $item + ['ready' => in_array($item['path'], $ready, true)];
            }
        }
        if ($visible) {
            $out[$title] = $visible;
        }
    }
    return $out;
}

function is_active(string $path): bool
{
    return Request::path() === trim($path, '/');
}

function send_security_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store');
    if (Config::get('security.csp', true)) {
        header("Content-Security-Policy: default-src 'self'; "
            . "script-src 'self' https://cdn.jsdelivr.net; "
            . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://rsms.me; "
            . "font-src 'self' data: https://cdn.jsdelivr.net https://rsms.me; "
            . "img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
    }
}
