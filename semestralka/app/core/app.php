<?php

namespace App\Core;

class App
{
	private $router;
	private $dispatcher;

	public function __construct(Router $router, Dispatcher $dispatcher)
	{
		$this->router = $router;
		$this->dispatcher = $dispatcher;
	}

	public function run(): void
	{
		$urlParts = $this->parseUrl();

		$routeInfo = $this->router->match($urlParts);

		if (!$routeInfo) {
			http_response_code(404);
			echo "404 - Route not found";
			return;
		}

		$this->dispatcher->dispatch(
			$routeInfo['controller'],
			$routeInfo['method'],
			$routeInfo['params']
		);
	}

	private function parseUrl(): array
	{
		if (isset($_GET['url'])) {
			return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
		}
		return [''];
	}
}
