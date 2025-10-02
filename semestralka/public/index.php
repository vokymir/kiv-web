<?php

use App\Core\App;
use App\Core\Dispatcher;
use App\Core\Router;

require __DIR__ . '/../vendor/autoload.php';

session_start();

// === DEBUG ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// === ===== ===

$router = new Router();
$dispatcher = new Dispatcher();

$loadRoutes = require __DIR__ . '/../App/Routes.php';
$loadRoutes($router);

$app = new App($router, $dispatcher);
$app->run();
