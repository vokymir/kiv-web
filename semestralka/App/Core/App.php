<?php

namespace App\Core;

// is used to MANAGE the app and call the right controller and method
class App
{
	private $router;
	private $dispatcher;

	public function __construct(Router $router, Dispatcher $dispatcher)
	{
		$this->router = $router;
		$this->dispatcher = $dispatcher;
	}

	// find the right controller and method and call them
	public function run(): void
	{
		$urlParts = $this->parseUrl();

		$routeInfo = $this->router->match($urlParts);

		if (!$routeInfo) {
			$routeInfo = [
				'controller' => 'PublicController',
				'method' => 'error',
				'params' => []
			];
		}

		$this->dispatcher->dispatch(
			$routeInfo['controller'],
			$routeInfo['method'],
			$routeInfo['params']
		);
	}

	// parse url into array
	/** @return string[] */
	private function parseUrl(): array
	{
		if (isset($_GET['url'])) {
			return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
		}
		return [''];
	}
}
