<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

send_security_headers();
Session::start();

$router = new Router();
require APP_DIR . '/routes.php';
$router->dispatch(Request::method(), Request::path());
