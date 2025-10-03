<?php

namespace App\Controllers;

use App\Core;
use App\Models\User;

class PublicController extends Core\Controller
{
	public function index(): void
	{
		$speakers = User::getRandomSpeakers(3);

		$data = [
			'title' => 'homepage',
			'speakers' => $speakers,
			'isLoggedIn' => !empty($_SESSION['user']),
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
