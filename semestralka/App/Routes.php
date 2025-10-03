<?php

use App\Core\Router;

return function (Router $router) {
	$router->get('', 'PublicController@index');
	$router->get('login', 'AuthController@showLogin');
	$router->post('login', 'AuthController@login');
	$router->get('register', 'AuthController@showRegister');
	$router->post('register', 'AuthController@register');
	$router->get('logout', 'AuthController@logout');
	$router->get('program', 'PublicController@program');
	$router->get('posts', 'PostController@posts');
	$router->get('posts/new', 'PostController@new');
	$router->post('posts/new', 'PostController@storeNew');
	$router->get('download/pdf/{filename}', 'DownloadController@pdf');
};
