<?php

namespace App\Controllers;

use App\Core;
use App\Models\User;
use App\Models\Post;

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
		$posts = Post::findAccepted();

		$data = [
			'title' => 'program',
			'posts' => $posts,
		];

		$this::renderView('public/program', $data);
	}

	public function error(): void
	{
		http_response_code(404);
		$this::renderView('public/404');
	}
}
