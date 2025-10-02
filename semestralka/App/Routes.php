<?php

use App\Core\Router;

return function (Router $router) {
	$router->get('', 'PublicController@index');
	$router->get('login', 'AuthController@showLogin');
	$router->get('register', 'AuthController@showRegister');
	$router->post('login', 'AuthController@login');
	$router->post('register', 'AuthController@register');
	$router->get('logout', 'AuthController@logout');
	$router->get('program', 'PublicController@program');
	$router->get('posts', 'PostController@posts');
	$router->get('posts/new', 'PostController@new');
};
