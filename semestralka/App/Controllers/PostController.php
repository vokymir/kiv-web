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
		$userId = $_SESSION['user']['id'] ?? null;
		if (!$userId) {
			self::redirect('login');
			return;
		}

		$posts = Post::findByUser($userId);
		self::renderView('author/posts', ['posts' => $posts]);
	}

	public function new(): void
	{
		self::renderView('author/new');
	}

	public function edit(int $postId): void
	{
		$post = Post::find($postId);
		if (!$post) {
			http_response_code(404);
			echo "Post not found.";
			return;
		}

		self::renderView('author/edit', ['post' => $post]);
	}

	public function storeNew(): void
	{
		$userId = $_SESSION['user']['id'] ?? null;
		if (!$userId) {
			self::redirect('login');
			return;
		}

		try {
			$post = new Post();
			$post->create([
				'userId' => $userId,
				'title' => self::sanitize($_POST['title'] ?? ''),
				'abstract' => self::sanitize($_POST['abstract'] ?? ''),
				'status' => Status::PendingReview
			], $_FILES['pdf'] ?? []);

			self::redirect('posts');
		} catch (\Throwable $e) {
			self::renderView('author/new', ['error' => $e->getMessage()]);
		}
	}

	public function update(int $postId): void
	{
		$userId = $_SESSION['user']['id'] ?? null;
		if (!$userId) {
			self::redirect('login');
			return;
		}

		try {
			$post = new Post();
			$post->update($postId, [
				'title' => self::sanitize($_POST['title'] ?? ''),
				'abstract' => self::sanitize($_POST['abstract'] ?? ''),
				'status' => $_POST['status'] ?? Status::PendingReview
			], $_FILES['pdf'] ?? []);

			self::redirect('posts');
		} catch (\Throwable $e) {
			self::renderView('author/edit', [
				'error' => $e->getMessage(),
				'post' => Post::find($postId)
			]);
		}
	}

	public function delete(int $postId): void
	{
		try {
			$post = new Post();
			$post->delete($postId);
			self::redirect('posts');
		} catch (\Throwable $e) {
			http_response_code(500);
			echo "Failed to delete post: " . htmlspecialchars($e->getMessage());
		}
	}

	private static function sanitize(string $input): string
	{
		return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
	}
}
