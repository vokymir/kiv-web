<?php

return function ($router) {
	$router->get('', 'PublicController@index');
	$router->get('/', 'PublicController@index');
	$router->get('index.php', 'PublicController@index');
};
