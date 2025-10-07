<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Role;
use App\Config\Config;

class AuthController extends Controller
{
	public function login(): void
	{
		$username = $_POST['username'] ?? '';
		$password = $_POST['password'] ?? '';

		if (Auth::attemptLogin($username, $password)) {
			self::redirect('');
		} else {
			self::renderView('public/login', ['error' => 'Invalid credentials...']);
		}
	}

	public function logout(): void
	{
		Auth::logout();
		self::redirect('login');
	}

	public function register(): void
	{
		$username = trim($_POST['username'] ?? '');
		$password = $_POST['password'] ?? '';
		$confirmPassword = $_POST['confirm_password'] ?? '';
		$name = trim($_POST['name'] ?? '');

		if ($username === '' || $password === '' || $confirmPassword === '' || $name === '') {
			self::renderView('public/register', ['error' => 'Please fill all fields.']);
			return;
		}

		if ($password !== $confirmPassword) {
			self::renderView('public/register', ['error' => 'Passwords must match.']);
			return;
		}

		if (!Auth::registerUser($username, $password, $name, Role::Author->value)) {
			self::renderView('public/register', ['error' => 'Username already exists.']);
			return;
		}

		self::redirect('login');
	}
	public function showLogin(): void
	{
		self::renderView("public/login");
	}

	public function showRegister(): void
	{
		self::renderView('public/register');
	}
}
