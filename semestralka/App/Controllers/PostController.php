<?php

namespace App\Controllers;

use App\Config\Config;
use App\Core\Controller;
use App\Models\Post;
use App\Models\Status;

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

	public function storeNew(): void
	{
		$title = trim($_POST['title'] ?? '');
		$abstract = trim($_POST['abstract'] ?? '');
		$userId = $_SESSION['user']['id'] ?? null;
		$status = Status::PendingReview;

		if (!$userId) {
			header('Location: ' . Config::BASE_URL . 'login');
			exit;
		}

		// check required fields
		if (empty($title) || empty($abstract)) {
			$error = "Title and abstract are required.";
			self::renderView('author/new', ['error' => $error]);
			return;
		}

		$pdfPath = '';
		$filename = '';

		if (!empty($_FILES['pdf']['tmp_name'])) {
			$uploadDir = Config::UPLOAD_DIR;

			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0777, true);
			}

			// validate file type
			$fileType = mime_content_type($_FILES['pdf']['tmp_name']);
			if ($fileType !== 'application/pdf') {
				$error = "Only PDF files are allowed.";
				self::renderView('author/new', ['error' => $error]);
				return;
			}

			// sanitize filename
			$originalName = pathinfo($_FILES['pdf']['name'], PATHINFO_FILENAME);
			$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
			$filename = time() . '_' . $safeName . '.pdf';

			$target = $uploadDir . $filename;

			if (move_uploaded_file($_FILES['pdf']['tmp_name'], $target)) {
				// store relative path
				$pdfPath = 'uploads/' . $filename;
			} else {
				$error = "Failed to upload PDF file.";
				self::renderView('author/new', ['error' => $error]);
				return;
			}
		}

		$post = new Post();
		$post->add($userId, $title, $abstract, $filename, $status);

		header('Location: ' . Config::BASE_URL . 'posts');
		exit;
	}
}
