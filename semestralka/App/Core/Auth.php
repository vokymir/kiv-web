<?php

namespace App\Core;

use App\Models\Role;

// login/register
class Auth
{
	// try to login with credentials
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

	// try to register
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

	// does the user already exist?
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

	// get user if is set in session
	public static function user(): ?array
	{
		return $_SESSION['user'] ?? null;
	}

	// only check if user is logged in
	public static function loggedIn(): bool
	{
		return isset($_SESSION['user']);
	}

	// save user into session
	/* @param array{id:int, username:string, name:string, role:int|string} $user */
	public static function login(array $user): void
	{
		$_SESSION['user'] = [
			'id' => $user['id'],
			'username' => $user['username'],
			'name' => $user['name'],
			'role' => $user['role']
		];
	}

	// does user have a specific role?
	public static function isRole(Role $role): bool
	{
		$u = self::user();
		if (!$u) return false;
		return $u['role'] === $role->value;
	}

	/**
	 * Don't let in user without role or one of roles.
	 * @param Role|Role[] $roles A single role or an array of roles.
	 */
	public static function requireRole(Role|array $roles): void
	{
		$roles = is_array($roles) ? $roles : [$roles];

		if (!self::loggedIn() || !in_array($_SESSION['user']['role'], array_map(fn($r) => $r->value, $roles), true)) {
			http_response_code(403);
			Flash::set('error', 'Forbidden.');
			exit;
		}
	}
}
