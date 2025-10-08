<?php

namespace App\Core;

use App\Config\Config;

// Handles rendering of views and layouts.
class View
{
	private array $sections = [];
	private array $data = [];

	// @param array<string, mixed> $data Initial data to pass to views
	public function __construct(array $data = [])
	{
		$this->data = array_merge($data, Config::VIEW_DATA ?? []);
	}

	// Render a full view with a layout.
	public function render(string $viewPath, string $layoutPath = "main"): void
	{
		// use variables from data
		extract($this->data);

		// prepare view and layout
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

	/**
	 * Render a partial (without layout).
	 *
	 * @param array<string, mixed> $data Optional data for the partial
	 */
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

	// Start capturing a section.
	public function startSection(string $name): void
	{
		ob_start();
		$this->sections[$name] = '';
	}

	// End capturing a section.
	public function endSection(string $name): void
	{
		$this->sections[$name] = ob_get_clean();
	}

	// Output the content of a section.
	public function yieldSection(string $name): void
	{
		echo $this->sections[$name] ?? '';
	}
}
