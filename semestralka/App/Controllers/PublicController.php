<?php

namespace App\Controllers;

use App\Core;

class PublicController extends Core\Controller
{
	public function index(): void
	{
		$data = [
			"title" => "homepage",
		];

		$this::renderView("public/index", $data);
	}
}
