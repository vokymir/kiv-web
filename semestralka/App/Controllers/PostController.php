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

		$userId = $_SESSION['user']['id'];
		$posts = Post::findByUser($userId);

		self::renderView('author/posts', ['posts' => $posts]);
	}

	public function new(): void
	{
		self::renderView('author/new');
	}

	public function edit(int $postId): void
	{
		$post = new Post();
		$existing = $post->find($postId);

		if (!$existing) {
			http_response_code(404);
			echo "Post not found";
			return;
		}

		self::renderView('author/edit', ['post' => $existing]);
	}

	public function update(int $postId): void
	{
		$userId = $_SESSION['user']['id'] ?? null;
		if (!$userId) {
			header('Location: ' . Config::BASE_URL . 'login');
			exit;
		}

		$post = new Post();
		try {
			$post->update($postId, [
				'title' => trim($_POST['title'] ?? ''),
				'abstract' => trim($_POST['abstract'] ?? ''),
				'status' => $_POST['status'] ?? Status::PendingReview
			], $_FILES['pdf'] ?? []);

			header('Location: ' . Config::BASE_URL . 'posts');
			exit;
		} catch (\Exception $e) {
			self::renderView('author/edit', [
				'error' => $e->getMessage(),
				'post' => $post->find($postId)
			]);
		}
	}

	public function delete(int $postId): void
	{
		$post = new Post();
		try {
			$post->delete($postId);
			header('Location: ' . Config::BASE_URL . 'posts');
			exit;
		} catch (\Exception $e) {
			http_response_code(500);
			echo "Failed to delete post: " . $e->getMessage();
		}
	}

	public function storeNew(): void
	{
		$userId = $_SESSION['user']['id'] ?? null;
		if (!$userId) {
			header('Location: ' . Config::BASE_URL . 'login');
			exit;
		}

		$post = new Post();
		try {
			$post->create([
				'userId' => $userId,
				'title' => trim($_POST['title'] ?? ''),
				'abstract' => trim($_POST['abstract'] ?? ''),
				'status' => Status::PendingReview
			], $_FILES['pdf'] ?? []);

			header('Location: ' . Config::BASE_URL . 'posts');
			exit;
		} catch (\Exception $e) {
			self::renderView('author/new', ['error' => $e->getMessage()]);
		}
	}
}
