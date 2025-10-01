<?php

namespace App\Core;

use App\Models\Role;

class Auth
{
	public static function user(): ?array
	{
		return $_SESSION['user'] ?? null;
	}

	public static function loggedIn(): bool
	{
		return isset($_SESSION['user']);
	}

	public static function login(array $user): void
	{
		$_SESSION['user'] = [
			'id' => $user['id'],
			'username' => $user['username'],
			'role' => $user['role']
		];
	}

	public static function logout(): void
	{
		unset($_SESSION['user']);
	}

	public static function isRole(Role $role): bool
	{
		$u = self::user();
		if (!$u) return false;
		return $u['role'] === $role->value;
	}

	public static function requireRole(Role $role): void
	{
		if (!self::loggedIn() || !self::isRole($role)) {
			http_response_code(403);
			echo 'Forbidden';
			exit;
		}
	}
}
