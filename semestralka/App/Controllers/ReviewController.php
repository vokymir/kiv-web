<?php

namespace App\Controllers;

use App\Models\Post;
use App\Core\Controller;

class ReviewController extends Controller
{
	public function posts(): void
	{
		$userId = $_SESSION['user']['id'];

		// Find posts assigned to this reviewer
		$assignedPosts = Post::findAssignedToReviewer($userId);

		self::renderView('reviewer/posts', [
			'assignedPosts' => $assignedPosts
		]);
	}
}
