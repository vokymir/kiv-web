<?php

namespace App\Controllers;

use App\Core\Controller;

class PostController extends Controller
{
	public function posts(): void
	{
		self::renderView('author/posts');
	}

	public function new(): void
	{
		self::renderView('author/new');
	}

	// controll this
	public function store(): void
	{
		$title = trim($_POST['title'] ?? '');
		$abstract = trim($_POST['abstract'] ?? '');
		$status = (int)($_POST['status'] ?? 0);
		$pdfPath = '';

		if (!empty($_FILES['pdf']['tmp_name'])) {
			$filename = basename($_FILES['pdf']['name']);
			$target = 'uploads/' . $filename;
			move_uploaded_file($_FILES['pdf']['tmp_name'], $target);
			$pdfPath = $target;
		}

		$userId = $_SESSION['user']['id'];

		$post = new \App\Models\Post();
		$post->add($userId, $title, $abstract, $pdfPath, \App\Models\Status::from($status));

		header('Location: ' . Config::BASE_URL . 'posts');
		exit;
	}
}
