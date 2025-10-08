<?php

namespace App\Core;

// store all possible routes
class Router
{
	private $routes = [];

	/*
	* Add route and appropriate controller and its method.
	* Allows for the same URL with different HTTP request methods.
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
	*/
	public function get(string $url, string $controllerMethod): void
	{
		[$controller, $method] = explode('@', $controllerMethod);
		$this->add($url, $controller, $method, 'GET');
	}

	/*
	* Shorthand for adding route with GET POST request method.
	*/
	public function post(string $url, string $controllerMethod): void
	{
		[$controller, $method] = explode('@', $controllerMethod);
		$this->add($url, $controller, $method, 'POST');
	}

	/**
	 * Match a URL against the registered routes.
	 *
	 * @param array<int, string> $urlParts Parts of the URL to match
	 * @return array{controller: string, method: string, params: array<string, string|null>}|null
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
