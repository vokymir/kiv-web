<?php

namespace App\Core;

use App\Config\Config;

class Controller
{
	protected function loadModel(string $model): mixed
	{
		require_once __DIR__ . '/../Models/' . $model . '.php';
		return new $model;
	}

	public static function renderView(string $viewPath, array $data = [], string $layoutPath = "main"): void
	{
		$view = new View($data);
		$view->render($viewPath, $layoutPath);
	}

	public static function redirect(string $url): void
	{
		if (!headers_sent()) {
			header('Location: ' . Config::BASE_URL . $url);
			exit;
		} else {
			// Fallback: JavaScript redirect if headers already sent
			echo "<script>window.location.href = '" . htmlspecialchars($url, ENT_QUOTES) . "';</script>";
			exit;
		}
	}
}
