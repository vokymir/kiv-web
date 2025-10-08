<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\Review;
use App\Models\Role;

class ReviewController extends Controller
{
	public function posts(): void
	{
		$userId = $_SESSION['user']['id'];
		$posts = Post::findAssignedToReviewer($userId);
		self::renderView('reviewer/posts', ['assignedPosts' => $posts]);
	}

	public function create(int $postId): void
	{
		// for safety
		$post = Post::find($postId);
		$review = Review::findByPostAndUser($postId, $_SESSION['user']['id']);

		$isEdit = $review !== null;

		self::renderView('reviewer/form', [
			'post' => $post,
			'review' => $review,
			'isEdit' => $isEdit
		]);
	}

	public function delete(int $id): void
	{
		$review = Review::findById($id);
		if (!$review) {
			$this->redirect('/reviews');
			return;
		}

		if ($review->userId !== $_SESSION['user']['id']) {
			http_response_code(403);
			echo "Forbidden: you cannot delete someone else's review.";
			return;
		}

		if (Review::deleteById($id)) {
			$this->redirect('reviews');
		} else {
			echo "Error deleting review.";
		}
	}


	public function edit(int $reviewId): void
	{
		$review = Review::findById($reviewId);
		$post = Post::find($review->postId);

		$isEdit = true;

		self::renderView('reviewer/form', [
			'post' => $post,
			'review' => $review,
			'isEdit' => $isEdit
		]);
	}

	function sanitizeReviewNote(string $html): string
	{
		// allow only some tags
		$allowedTags = '<p><br><b><i><strong><em><ul><ol><li>';
		return strip_tags($html, $allowedTags);
	}

	// used in routes
	public function store(): void
	{
		// ensure user is logged in
		if (!isset($_SESSION['user'])) {
			self::redirect('login');
			return;
		}

		$user = $_SESSION['user'];

		// allow only reviewers
		if ((int)$user['role'] !== Role::Reviewer->value) {
			http_response_code(403);
			echo "Forbidden: only reviewers can submit reviews.";
			exit;
		}

		$userId = (int)$user['id'];
		$postId = (int)$_POST['postId'];

		$data = [
			'userId' => $userId,
			'postId' => $postId,
			'ratingInteresting' => max(1, min(5, (int)$_POST['ratingInteresting'])),
			'ratingImportant' => max(1, min(5, (int)$_POST['ratingImportant'])),
			'ratingInovative' => max(1, min(5, (int)$_POST['ratingInovative'])),
			'ratingNote' => self::sanitizeReviewNote($_POST['ratingNote'] ?? '')
		];

		// already exists
		$existingReview = Review::findByPostAndUser($postId, $userId);

		if ($existingReview) {
			Review::update($existingReview->id, $data);
		} else {
			Review::create($data);
		}

		self::redirect('reviews');
	}
}
