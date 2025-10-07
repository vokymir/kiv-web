<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Post;
use App\Models\Review;

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
		$post = Post::find($postId);
		$review = Review::findByPostAndUser($postId, $_SESSION['user']['id']);

		$isEdit = $review !== null;

		self::renderView('reviewer/form', [
			'post' => $post,
			'review' => $review,
			'isEdit' => $isEdit
		]);
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
		// Allow only some basic tags
		$allowedTags = '<p><br><b><i><strong><em><ul><ol><li>';
		return strip_tags($html, $allowedTags);
	}

	public function store(): void
	{
		$userId = $_SESSION['user']['id'];
		$postId = (int)$_POST['postId'];

		$data = [
			'userId' => $userId,
			'postId' => $postId,
			'ratingInteresting' => (int)$_POST['ratingInteresting'],
			'ratingImportant' => (int)$_POST['ratingImportant'],
			'ratingInovative' => (int)$_POST['ratingInovative'],
			'ratingNote' => self::sanitizeReviewNote($_POST['ratingNote'])
		];

		$existingReview = Review::findByPostAndUser($postId, $userId);

		if ($existingReview) {
			Review::update($existingReview->id, $data);
		} else {
			Review::create($data);
		}

		self::redirect('reviews');
	}
}
