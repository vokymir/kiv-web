<?php

use App\Core\Router;

return function (Router $router) {
	$router->get('', 'PublicController@index');
	$router->get('login', 'AuthController@showLogin');
};
