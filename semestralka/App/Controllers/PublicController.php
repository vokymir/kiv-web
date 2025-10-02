<?php

namespace App\Controllers;

use App\Core;

class PublicController extends Core\Controller
{
	public function index(): void
	{
		$data = [
			'title' => 'homepage',
		];

		$this::renderView('public/home', $data);
	}

	public function program(): void
	{
		$data = [
			'title' => 'program',
		];

		$this::renderView('public/program', $data);
	}
}
