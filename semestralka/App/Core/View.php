<?php

namespace App\Core;

class View
{
	public static function render(string $view, array $data = []): void
	{
		// Make variables available in the view
		extract($data);

		$file = __DIR__ . '/../Views/' . $view . '.php';

		if (!file_exists($file)) {
			throw new \Exception("View not found: $file");
		}

		require $file;
	}
}
