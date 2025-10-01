<?php

namespace App\Controllers;

use App\Core\Controller;

class AuthController extends Controller
{
	public function showLogin(): void
	{
		self::renderView("public/login");
	}
}
