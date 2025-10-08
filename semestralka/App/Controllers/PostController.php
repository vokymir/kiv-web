<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\Status;
use App\Core\Auth;
use App\Core\Flash;
use App\Models\Role;
use App\Models\User;

class PostController extends Controller
{
	// show posts page for admin/author
	public function posts(): void
	{
		$userId = $_SESSION['user']['id'] ?? null;
		if (!$userId) {
			Flash::set('warning', 'Please login to continue.');
			self::redirect('login');
			return;
		}

		$userRole = isset($_SESSION['user']['role'])
			? Role::from((int)$_SESSION['user']['role'])
			: null;

		if ($userRole == Role::Author) {
			$posts = Post::findByUser($userId);
			self::renderView('author/posts', [
				'title' => 'My Posts',
				'posts' => $posts
			]);
			return;
		} else if ($userRole == Role::Admin || $userRole == Role::Superadmin) {
			$this->admin_posts();
		}
	}

	// show page for new post
	public function new(): void
	{
		self::renderView('author/new', ['title' => 'New Post']);
	}

	// show page for edit post if that post exists
	public function edit(int $postId): void
	{
		$post = Post::find($postId);
		if (!$post) {
			Flash::set('error', 'Post not found.');
			self::redirect('posts');
			return;
		}

		self::renderView('author/edit', [
			'title' => 'Edit Post',
			'post' => $post
		]);
	}

	// try storing new post in the database
	public function storeNew(): void
	{
		$userId = $_SESSION['user']['id'] ?? null;
		if (!$userId) {
			Flash::set('warning', 'Please login to continue.');
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

			Flash::set('success', 'Post created!');
			self::redirect('posts');
		} catch (\Throwable $e) {
			Flash::set('error', "Error while creating new post: {$e->getMessage()}");
			self::renderView('author/new', ['title' => 'New Post']);
		}
	}

	// try to update existing post
	public function update(int $postId): void
	{
		$userId = $_SESSION['user']['id'] ?? null;
		if (!$userId) {
			Flash::set('warning', 'Please login to continue.');
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

			Flash::set('success', 'Post created!');
			self::redirect('posts');
		} catch (\Throwable $e) {
			Flash::set('error', "Error while creating new post:" . htmlspecialchars($e->getMessage()));
			self::renderView('author/edit', ['title' => 'Edit Post']);
		}
	}

	// try deleting post 
	public function delete(int $postId): void
	{
		try {
			$post = new Post();
			$post->delete($postId);
			Flash::set('success', 'Post deleted!');
		} catch (\Throwable $e) {
			Flash::set('error', "Failed to delete post: " . htmlspecialchars($e->getMessage()));
			http_response_code(500);
		}
		self::redirect('posts');
	}

	// sanitize input to HTML tags
	private static function sanitize(string $input): string
	{
		return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
	}

	// show admin view of posts = all posts
	public function admin_posts(): void
	{
		Auth::requireRole([Role::Admin, Role::Superadmin]);

		$statusFilter = $_GET['status'] ?? 'all';
		$status = Status::fromFilter($statusFilter);

		$posts = Post::all($status ? [$status] : null);
		$reviewers = User::allByRole(Role::Reviewer);

		self::renderView('admin/posts', [
			'title' => 'All Posts',
			'posts' => $posts,
			'reviewers' => $reviewers,
			'statusFilter' => $statusFilter,
		]);
	}

	// update post as admin = more privileges
	public function admin_update(int $postId): void
	{
		Auth::requireRole([Role::Admin, Role::Superadmin]);

		$post = Post::find($postId);
		if (!$post) {
			Flash::set('warning', "Post cannot be updated because it doesn't exist.");
			self::redirect('posts');
		}

		$action = $_POST['action'] ?? null;
		switch ($action) {
			case 'assign':
				$reviewerIds = $_POST['reviewers'] ?? [];
				$post->assignReviewers(array_map('intval', $reviewerIds));
				break;

			case 'unassign':
				$reviewerIds = $_POST['remove_reviewers'] ?? [];
				$post->removeReviewers(array_map('intval', $reviewerIds));
				break;

			case 'publish':
			case 'reject':
				$reviewCount = count($post->getReviews() ?? []);
				if ($reviewCount < 3) {
					Flash::set('error', "Cannot $action post — at least 3 reviews are required.");
					self::redirect('posts');
					return;
				}
				$newStatus = $action === 'publish' ? Status::Accepted : Status::Rejected;
				$post->updateStatus($newStatus);
				break;
		}

		Flash::set('success', 'Post successfully updated!');
		self::redirect('posts');
	}

	// delete post if is admin
	public function admin_delete(int $postId): void
	{
		Auth::requireRole([Role::Admin, Role::Superadmin]);

		$post = Post::find($postId);
		if (!$post) {
			Flash::set('warning', "Post cannot be deleted because it doesn't exist.");
			self::redirect('posts');
		}

		$post->delete($postId);
		Flash::set('success', 'Post successfully deleted!');
		self::redirect('posts');
	}
}
