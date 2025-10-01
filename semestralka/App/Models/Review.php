<?php

namespace App\Models;

use DateTime;

class Review
{
	public int $id;
	public int $postId;
	public int $userId;

	public int $ratingInteresting;
	public int $ratingImportant;
	public int $ratingInovative;
	public string $ratingNote;

	public DateTime $createdAt;
}
