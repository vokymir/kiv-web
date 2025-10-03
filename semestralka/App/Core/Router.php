<?php

namespace App\Core;

class Router
{
	private $routes = [];

	/*
	* Add route and appropriate controller and its method.
	* Allows for the same URL with different HTTP request methods.
	*
	* @param string $url
	* @param string $controller
	* @param string $methodName
	* @param string $httpMethod
	* @return void
	*/
	public function add(string $url, string $controller, string $methodName, string $httpMethod = 'GET'): void
	{
		$httpMethod = strtoupper($httpMethod);
		$this->routes[$url][$httpMethod] = [
			'controller' => $controller,
			'method' => $methodName
		];
	}

	/*
	* Shorthand for adding route with GET HTTP request method.
	*
	* @param string $url
	* @param string $controllerMethod controller@method
	* @return void
	*/
	public function get($url, $controllerMethod): void
	{
		[$controller, $method] = explode('@', $controllerMethod);
		$this->add($url, $controller, $method, 'GET');
	}

	/*
	* Shorthand for adding route with GET POST request method.
	*
	* @param string $url
	* @param string $controllerMethod controller@method
	* @return void
	*/
	public function post($url, $controllerMethod): void
	{
		[$controller, $method] = explode('@', $controllerMethod);
		$this->add($url, $controller, $method, 'POST');
	}

	/*
	*
	*/
	public function match(array $urlParts): ?array
	{
		$urlStr = implode('/', array_filter($urlParts));
		$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		foreach ($this->routes as $route => $methods) {
			if (!isset($methods[$httpMethod])) continue;

			// Extract parameter names: {param}
			preg_match_all('#\{([^/]+)\}#', $route, $paramNames);
			$paramNames = $paramNames[1];

			// Convert route placeholders to regex
			$pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $route);
			$pattern = "#^$pattern$#";

			if (preg_match($pattern, $urlStr, $matches)) {
				array_shift($matches); // remove full match

				// Map parameters to their names
				$params = [];
				foreach ($paramNames as $index => $name) {
					$params[$name] = $matches[$index] ?? null;
				}

				$routeInfo = $methods[$httpMethod];
				return [
					'controller' => $routeInfo['controller'],
					'method' => $routeInfo['method'],
					'params' => $params
				];
			}
		}

		return null;
	}
}
