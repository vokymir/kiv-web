<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Role;
use App\Core\Flash;

class AuthController extends Controller
{
	// try to log user in
	public function login(): void
	{
		$username = $_POST['username'] ?? '';
		$password = $_POST['password'] ?? '';

		if (Auth::attemptLogin($username, $password)) {
			Flash::set('success', "Welcome back $username");
			self::redirect('');
		} else {
			Flash::set('error', 'Invalid credentials...');
			self::renderView('public/login', ['title' => 'Login']);
		}
	}

	// log user out
	public function logout(): void
	{
		Auth::logout();
		Flash::set('success', "See you!");
		self::redirect('login');
	}

	// try registering user
	public function register(): void
	{
		$username = trim($_POST['username'] ?? '');
		$password = $_POST['password'] ?? '';
		$confirmPassword = $_POST['confirm_password'] ?? '';
		$name = trim($_POST['name'] ?? '');

		if ($username === '' || $password === '' || $confirmPassword === '' || $name === '') {
			Flash::set('error', 'Please fill all fields.');
			self::renderView('public/register', ['title' => 'Register']);
			return;
		}

		if ($password !== $confirmPassword) {
			Flash::set('error', 'Passwords must match.');
			self::renderView('public/register', ['title' => 'Register']);
			return;
		}

		if (!Auth::registerUser($username, $password, $name, Role::Author->value)) {
			Flash::set('error', 'Username already exists.');
			self::renderView('public/register', ['title' => 'Register']);
			return;
		}

		self::redirect('login');
	}

	// show login page
	public function showLogin(): void
	{
		self::renderView('public/login', ['title' => 'Login']);
	}

	// show register page
	public function showRegister(): void
	{
		self::renderView('public/register', ['title' => 'Register']);
	}
}
