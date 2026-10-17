<?php
declare(strict_types=1);

define('BASE_DIR', dirname(__DIR__));
define('APP_DIR', __DIR__);
define('STORAGE_DIR', BASE_DIR . '/storage');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = APP_DIR . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require APP_DIR . '/helpers.php';

use App\Core\Config;

$configFile = BASE_DIR . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Configuration manquante : copiez config.example.php en config.php.');
}
Config::load($configFile);

date_default_timezone_set((string) Config::get('app.timezone', 'Africa/Dakar'));

$debug = (bool) Config::get('app.debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
if (!is_dir(STORAGE_DIR . '/logs')) {
    @mkdir(STORAGE_DIR . '/logs', 0750, true);
}
ini_set('error_log', STORAGE_DIR . '/logs/php-error.log');

set_exception_handler(static function (Throwable $e) use ($debug): void {
    error_log((string) $e);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo $debug
        ? '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>'
        : '<h1>Erreur interne</h1><p>Une erreur est survenue. Réessayez plus tard.</p>';
});
