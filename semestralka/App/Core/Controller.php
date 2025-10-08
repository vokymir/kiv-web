<?php

namespace App\Core;

use App\Config\Config;

// base class for all controllers, have common logic
class Controller
{
	// render a view specified with path
	// if view requieres any data, pass them in 'set'
	// if using another layout than 'main', pass that in
	// @param array<string, mixed> $data
	public static function renderView(string $viewPath, array $data = [], string $layoutPath = "main"): void
	{
		$view = new View($data);
		$view->render($viewPath, $layoutPath);
	}

	// redirect to another page. SAFE
	public static function redirect(string $url): void
	{
		// remove trailing slashes
		$base = rtrim(Config::BASE_URL, '/');
		$path = ltrim($url, '/');

		$target = $base . '/' . $path;

		if (!headers_sent()) {
			header('Location: ' . $target);
			exit;
		} else {
			// fallback for already sent headers
			echo "<script>window.location.href = '" . htmlspecialchars($target, ENT_QUOTES) . "';</script>";
			exit;
		}
	}
}
