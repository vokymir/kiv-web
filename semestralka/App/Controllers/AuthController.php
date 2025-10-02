<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Role;
use App\Config\Config;

class AuthController extends Controller
{
	public function showLogin(): void
	{
		self::renderView("public/login");
	}

	public function showRegister(): void
	{
		self::renderView('public/register');
	}

	public function login(): void
	{
		$username = $_POST['username'] ?? '';
		$password = $_POST['password'] ?? '';

		$db = new Database();
		$user = $db
			->query('SELECT * FROM users WHERE username = :un')
			->bind(':un', $username)
			->fetchFirst();

		if ($user && password_verify($password, $user['passwordHash'])) {
			Auth::login($user);
			header('Location: ' . Config::BASE_URL);
			exit;
		} else {
			$error = 'Invalid credentials...';
			self::renderView('public/login', ['error' => $error]);
			return;
		}
	}

	public function logout(): void
	{
		Auth::logout();
		header('Location: ' . Config::BASE_URL . 'login');
		exit;
	}

	public function register(): void
	{
		$username = trim($_POST['username'] ?? '');
		$password = $_POST['password'] ?? '';
		$confirmPassword = $_POST['confirm_password'] ?? '';
		$role = Role::Author;

		if ($username === '' || $password === '' || $confirmPassword === '') {
			$error = 'Please fill all fields...';
			self::renderView('public/register', ['error' => $error]);
			return;
		}

		if ($password !== $confirmPassword) {
			$error = 'You must have the same password.';
			self::renderView('public/register', ['error' => $error]);
			return;
		}

		$db = new Database();
		$u = $db
			->query('SELECT id FROM users WHERE username = :un')
			->bind(':un', $username)
			->fetchFirst();
		if ($u) {
			$error = 'Username exists...';
			self::renderView('public/register', ['error' => $error]);
			return;
		}

		$hash = password_hash($password, PASSWORD_BCRYPT);
		$db
			->query('INSERT INTO users (username, passwordHash, role) VALUES (:username, :passwordHash, :role)')
			->bind(':username', $username)
			->bind(':passwordHash', $hash)
			->bind(':role', $role->value)
			->execute();

		header('Location: ' . Config::BASE_URL . 'login');
		exit;
	}
}
