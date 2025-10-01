<?php

namespace App\Core;

use App\Config\Config;

class View
{
	private array $sections = [];
	private array $data = [];

	public function __construct(array $data = [])
	{
		$this->data = array_merge($data, Config::VIEW_DATA ?? []);
	}

	public function render(string $viewPath, string $layoutPath = "main"): void
	{
		extract($this->data);

		$viewFile   = __DIR__ . '/../Views/' . $viewPath . '.php';
		$layoutFile = __DIR__ . '/../Views/layout/' . $layoutPath . '.php';

		if (!file_exists($viewFile)) {
			throw new \Exception("View not found: $viewFile");
		}
		if (!file_exists($layoutFile)) {
			throw new \Exception("Layout not found: $layoutFile");
		}

		// render the view into buffer
		ob_start();
		require $viewFile;
		$content = ob_get_clean();

		// render the layout
		require $layoutFile;
	}

	public function renderPartial(string $partialPath, array $data = []): void
	{
		$file = __DIR__ . '/../Views/layout/' . $partialPath . '.php';
		if (!file_exists($file)) {
			throw new \Exception("Partial layout not found: $file");
		}

		extract($this->data);
		extract($data);
		require $file;
	}

	public function startSection(string $name): void
	{
		ob_start();
		$this->sections[$name] = '';
	}

	public function endSection(string $name): void
	{
		$this->sections[$name] = ob_get_clean();
	}

	public function yieldSection(string $name): void
	{
		echo $this->sections[$name] ?? '';
	}
}
