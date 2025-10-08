<?php

namespace App\Core;

class Flash
{
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
