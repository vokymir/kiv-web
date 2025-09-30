<?php

use App\Core\App;
use App\Core\Dispatcher;
use App\Core\Router;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$router = new Router();
$dispatcher = new Dispatcher();

require_once __DIR__ . '/../app/routes.php';

$app = new App($router, $dispatcher);
$app->run();
