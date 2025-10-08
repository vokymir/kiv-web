<?php

namespace App\Core;

// Displaying messages
// (in layout/main.php on top is Flash::get(), in controllers are Flash::set())
class Flash
{
	// set message to be shown
	public static function set(string $type, string $message): void
	{
		if (!isset($_SESSION)) {
			session_start();
		}

		$_SESSION['flash'] = [
			'type' => $type,
			'message' => $message,
		];
	}

	// get message to be shown
	public static function get(): ?array
	{
		if (!isset($_SESSION)) {
			session_start();
		}

		if (!isset($_SESSION['flash'])) {
			return null;
		}

		$flash = $_SESSION['flash'];
		unset($_SESSION['flash']);
		return $flash;
	}
}
