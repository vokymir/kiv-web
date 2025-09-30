<?php

namespace App\Core;

class Dispatcher
{
	public function dispatch(string $controllerName, string $method, array $params): mixed
	{
		$class = "App\\Controllers\\{$controllerName}";

		if (!class_exists($class)) {
			http_response_code(500);
			throw new \Exception("Controller class not found: $class");
		}

		$controller = new $class();

		if (!method_exists($controller, $method)) {
			http_response_code(404);
			throw new \Exception("Method not found: $method in $class");
		}

		return call_user_func_array([$controller, $method], $params);
	}
}
