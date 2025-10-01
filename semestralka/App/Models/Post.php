<?php

namespace App\Models;

use App\Core\Database;
use DateTime;

enum Status: int
{
	case PendingReview = 10;
	case Accepted = 20;
	case Rejected = 30;
}

class Post
{
	public int $id;
	public int $userId;
	public string $title = "";
	public string $abstract = "";
	public string $pathPDF = "";

	public Status $status = Status::PendingReview;
	public DateTime $createdAt;

	public function add(int $userId, string $title, string $abstract, string $pathPDF): void
	{
		$db = new Database();
		$db->query("INSERT INTO post (userId, title, abstract, pathPDF) VALUES (:userId, :title, :abstract, :pathPDF)")
			->bind(':userId', $userId)
			->bind(':title', $title)
			->bind(':abstract', $abstract)
			->bind(':pathPDF', $pathPDF)
			->execute();
	}
}
