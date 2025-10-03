<?php

namespace App\Models;

use App\Core\Database;
use DateTime;

class Post
{
	public int $id;
	public int $userId;
	public string $title = "";
	public string $abstract = "";
	public string $pathPDF = "";

	public Status $status;
	public DateTime $createdAt;
	public ?string $author = null;

	public function __construct(array $data = [])
	{
		if ($data) {
			$this->id = (int)($data['id'] ?? 0);
			$this->userId = (int)($data['userId'] ?? 0);
			$this->title = $data['title'] ?? '';
			$this->abstract = $data['abstract'] ?? '';
			$this->pathPDF = $data['pathPDF'] ?? '';
			$this->status = Status::tryFrom((int)($data['status'] ?? 0)) ?? Status::PendingReview;
			$this->author   = $data['author'] ?? null;
			$this->createdAt = isset($data['created_at'])
				? new DateTime($data['created_at'])
				: new DateTime();
		}
	}

	public function add(int $userId, string $title, string $abstract, string $pathPDF, Status $status): void
	{
		$db = new Database();
		$db->query("INSERT INTO posts (userId, title, abstract, pathPDF, status)
				VALUES (:userId, :title, :abstract, :pathPDF, :status)")
			->bind(':userId', $userId)
			->bind(':title', $title)
			->bind(':abstract', $abstract)
			->bind(':pathPDF', $pathPDF)
			->bind(':status', $status->value)
			->execute();
	}

	public function isStatus(Status $status): bool
	{
		return $this->status === $status;
	}

	public function canEdit(): bool
	{
		return ! $this->isStatus(Status::Accepted);
	}

	public function getStatusName(): string
	{
		return $this->status->label();
	}

	public static function find(int $postId): ?Post
	{
		$db = new Database();
		$row = $db->query("
        SELECT p.id, p.userId, p.title, p.abstract, p.pathPDF, p.status, p.created_at, 
               u.username AS author
        FROM posts p
        JOIN users u ON u.id = p.userId
        WHERE p.id = :postId
        LIMIT 1
			")
			->bind(':postId', $postId)
			->fetchFirst();

		if (!$row) {
			return null;
		}

		return new Post($row);
	}

	public static function findByUser(int $userId): array
	{
		$db = new Database();
		$rows = $db->query("
SELECT p.id, p.userId, p.title, p.abstract, p.pathPDF, p.status, p.created_at, u.username AS author
FROM posts p
LEFT JOIN users u ON p.userId = u.id
WHERE p.userId = :userId
ORDER BY p.created_at DESC
			")
			->bind(':userId', $userId)->fetchAll();

		return array_map(fn($row) => new self($row), $rows);
	}
}
