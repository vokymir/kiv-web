<?php

namespace App\Core;


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
}
