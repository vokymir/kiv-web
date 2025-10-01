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
		// normalize route: join all parts (except empty ones)
		$route = implode('/', array_filter($urlParts));

		// handle root ("/")
		if ($route === '') {
			$route = '';
		}

		$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

		if (isset($this->routes[$route][$httpMethod])) {
			$routeInfo = $this->routes[$route][$httpMethod];
			return [
				'controller' => $routeInfo['controller'],
				'method' => $routeInfo['method'],
				// everything after matched route is params
				'params' => array_slice($urlParts, count(explode('/', $route)))
			];
		}

		return null;
	}
}
