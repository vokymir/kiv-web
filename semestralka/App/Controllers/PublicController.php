<?php

namespace App\Controllers;

use App\Core\View;

class PublicController
{
	public function index(): void
	{
		View::render("public/index");
	}
}
