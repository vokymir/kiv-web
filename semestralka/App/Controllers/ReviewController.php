<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Flash;
use App\Models\Post;
use App\Models\Review;
use App\Models\Role;

class ReviewController extends Controller
{
	// show all posts assigned to logged in reviewer
	public function posts(): void
	{
		$userId = $_SESSION['user']['id'];
		$posts = Post::findAssignedToReviewer($userId);
		self::renderView('reviewer/posts', ['assignedPosts' => $posts]);
	}

	// show form for NEW/EDIT review
	public function create(int $postId): void
	{
		// for safety look if already exist
		$post = Post::find($postId);
		$review = Review::findByPostAndUser($postId, $_SESSION['user']['id']);

		$isEdit = $review !== null; // if already exist, isEdit = true

		self::renderView('reviewer/form', [
			'post' => $post,
			'review' => $review,
			'isEdit' => $isEdit
		]);
	}

	// try deleting review
	public function delete(int $id): void
	{
		$review = Review::findById($id);
		if (!$review) {
			Flash::set('error', 'Cannot delete non-existing review.');
			$this->redirect('reviews');
			return;
		}

		if ($review->userId !== $_SESSION['user']['id']) {
			http_response_code(403);
			Flash::set('error', "Forbidden: you cannot delete someone else's review.");
			$this->redirect('reviews');
			return;
		}

		if (Review::deleteById($id)) {
			Flash::set('success', 'Review successfully deleted.');
		} else {
			Flash::set('error', "Error deleting review.");
		}
		$this->redirect('reviews');
	}

	// render form for edit review - shortcut compared to create, because we know it already exists
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

	// prevent SQL inject
	function sanitizeReviewNote(string $html): string
	{
		// allow only some tags
		$allowedTags = '<p><br><b><i><strong><em><ul><ol><li>';
		return strip_tags($html, $allowedTags);
	}

	// store results from NEW/EDIT review in DB
	public function store(): void
	{
		if (!isset($_SESSION['user'])) {
			Flash::set('warning', 'Please login to continue.');
			self::redirect('login');
			return;
		}

		$user = $_SESSION['user'];

		// allow only reviewers
		if ((int)$user['role'] !== Role::Reviewer->value) {
			Flash::set('error', "Forbidden: only reviewers can submit reviews.");
			http_response_code(403);
			self::redirect('reviews');
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
