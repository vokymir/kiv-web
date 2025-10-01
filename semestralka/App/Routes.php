<?php

use App\Core\Router;

return function (Router $router) {
	$router->get('', 'PublicController@index');
	$router->get('/', 'PublicController@index');
	$router->get('index.php', 'PublicController@index');
	$router->get('login', 'AuthController@showLogin');
};
