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
	$router->get('posts/{postId}/edit', 'PostController@edit');
	$router->post('posts/update/{postId}', 'PostController@update');
	$router->get('posts/{postId}/delete', 'PostController@delete');
	$router->get('reviews', 'ReviewController@posts');
	$router->get('reviews/{postId}/create', 'ReviewController@create');
	$router->get('reviews/{reviewId}/edit', 'ReviewController@edit');
	$router->post('reviews/update', 'ReviewController@store');
	$router->post('reviews/store', 'ReviewController@store');
	$router->get('users', 'UserController@users');
	$router->post('users/{userId}/update', 'UserController@update');
	$router->get('users/{userId}/delete', 'UserController@delete');
	$router->post('posts/{postId}/update', 'PostController@admin_update');
	$router->post('posts/{postId}/delete', 'PostController@admin_delete');
};
