<?php

/* require("config/db.php"); */
require("config/fill_db.php");

session_start();

// Autoload models and controllers
spl_autoload_register(function ($class) {
    if (file_exists("controllers/$class.php")) include "controllers/$class.php";
    if (file_exists("models/$class.php")) include "models/$class.php";
});

// Default controller and action
$controller = $_GET['controller'] ?? 'user';
$action = $_GET['action'] ?? 'login';

// Build controller class name
$controllerName = ucfirst($controller) . 'Controller';

if (class_exists($controllerName)) {
    $ctrl = new $controllerName();
    if (method_exists($ctrl, $action)) {
        $ctrl->$action();
    } else {
        echo "Action $action not found!";
    }
} else {
    echo "Controller $controllerName not found!";
}
