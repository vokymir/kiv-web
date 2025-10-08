<?php

namespace App\Core;

use App\Models\Role;

class Auth
{
	public static function attemptLogin(string $username, string $password): ?array
	{
		$db = new Database();
		$user = $db->query('SELECT * FROM users WHERE username = :un')
			->bind(':un', $username)
			->fetchFirst();

		if ($user && !$user['blocked'] && password_verify($password, $user['passwordHash'])) {
			self::login($user);
			return $user;
		}
		return null;
	}

	public static function registerUser(string $username, string $password, string $name, int $role): bool
	{
		$db = new Database();
		if (self::userExists($username)) return false;

		$hash = password_hash($password, PASSWORD_BCRYPT);

		$db->query('INSERT INTO users (username, passwordHash, role, name)
		            VALUES (:username, :passwordHash, :role, :name)')
			->bind(':username', $username)
			->bind(':passwordHash', $hash)
			->bind(':role', $role)
			->bind(':name', $name)
			->execute();

		return true;
	}

	public static function userExists(string $username): bool
	{
		$db = new Database();
		return (bool) $db->query('SELECT id FROM users WHERE username = :un')
			->bind(':un', $username)
			->fetchFirst();
	}

	public static function logout(): void
	{
		session_destroy();
	}

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
			'name' => $user['name'],
			'role' => $user['role']
		];
	}

	public static function isRole(Role $role): bool
	{
		$u = self::user();
		if (!$u) return false;
		return $u['role'] === $role->value;
	}

	public static function requireRole(Role|array $roles): void
	{
		$roles = is_array($roles) ? $roles : [$roles];

		if (!self::loggedIn() || !in_array($_SESSION['user']['role'], array_map(fn($r) => $r->value, $roles), true)) {
			http_response_code(403);
			echo 'Forbidden';
			exit;
		}
	}
}
