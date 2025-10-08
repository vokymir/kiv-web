<?php

use App\Core\Router;

// @param Router $router
// Registers all application routes
return function (Router $router) {

	// ===== Public pages =====
	$router->get('', 'PublicController@index');
	$router->get('program', 'PublicController@program');
	$router->get('error', 'PublicController@error');

	// ===== Authentication =====
	$router->get('login', 'AuthController@showLogin');
	$router->post('login', 'AuthController@login');
	$router->get('register', 'AuthController@showRegister');
	$router->post('register', 'AuthController@register');
	$router->get('logout', 'AuthController@logout');

	// ===== Posts =====
	$router->get('posts', 'PostController@posts');
	$router->get('posts/new', 'PostController@new');
	$router->post('posts/new', 'PostController@storeNew');
	$router->get('posts/{postId}/edit', 'PostController@edit');
	$router->post('posts/update/{postId}', 'PostController@update');
	$router->get('posts/delete/{postId}', 'PostController@delete');

	// ===== Admin post management =====
	$router->post('posts/{postId}/update', 'PostController@admin_update');
	$router->get('posts/{postId}/delete', 'PostController@admin_delete');

	// ===== Reviews =====
	$router->get('reviews', 'ReviewController@posts');
	$router->get('reviews/{postId}/create', 'ReviewController@create');
	$router->get('reviews/{reviewId}/edit', 'ReviewController@edit');
	$router->post('reviews/store', 'ReviewController@store');
	$router->post('reviews/update', 'ReviewController@store');
	$router->get('reviews/{id}/delete', 'ReviewController@delete');

	// ===== Users =====
	$router->get('users', 'UserController@users');
	$router->post('users/{userId}/update', 'UserController@update');
	$router->get('users/{userId}/delete', 'UserController@delete');

	// ===== Downloads =====
	$router->get('download/pdf/{filename}', 'DownloadController@pdf');
};
