<?php

namespace App\Models;

use App\Core\Database;
use DateTime;

class Review
{
	public int $id;
	public int $postId;
	public int $userId;
	public int $ratingInteresting;
	public int $ratingImportant;
	public int $ratingInovative;
	public string $ratingNote = '';
	public DateTime $createdAt;

	// @param array<string, mixed> $data
	public function __construct(array $data = [])
	{
		if ($data) {
			$this->id = (int)($data['id'] ?? 0);
			$this->postId = (int)($data['postId'] ?? 0);
			$this->userId = (int)($data['userId'] ?? 0);
			$this->ratingInteresting = (int)($data['ratingInteresting'] ?? 0);
			$this->ratingImportant = (int)($data['ratingImportant'] ?? 0);
			$this->ratingInovative = (int)($data['ratingInovative'] ?? 0);
			$this->ratingNote = $data['ratingNote'] ?? '';
			$this->createdAt = isset($data['created_at'])
				? new DateTime($data['created_at'])
				: new DateTime();
		}
	}

	// ===== CRUD =====

	// from data construct new review
	// @param array $data<string, mixed>
	public static function create(array $data): bool
	{
		$db = new Database();
		return $db->query("
			INSERT INTO reviews (postId, userId, ratingInteresting, ratingImportant, ratingInovative, ratingNote)
			VALUES (:postId, :userId, :ratingInteresting, :ratingImportant, :ratingInovative, :ratingNote)
		")
			->bind(':postId', $data['postId'])
			->bind(':userId', $data['userId'])
			->bind(':ratingInteresting', $data['ratingInteresting'])
			->bind(':ratingImportant', $data['ratingImportant'])
			->bind(':ratingInovative', $data['ratingInovative'])
			->bind(':ratingNote', $data['ratingNote'])
			->execute();
	}

	// update any review, rewrite all data
	// @param array<string, mixed> $data
	public static function update(int $reviewId, array $data): bool
	{
		$db = new Database();
		return $db->query("
			UPDATE reviews SET
				ratingInteresting = :ratingInteresting,
				ratingImportant = :ratingImportant,
				ratingInovative = :ratingInovative,
				ratingNote = :ratingNote
			WHERE id = :id
		")
			->bind(':ratingInteresting', $data['ratingInteresting'])
			->bind(':ratingImportant', $data['ratingImportant'])
			->bind(':ratingInovative', $data['ratingInovative'])
			->bind(':ratingNote', $data['ratingNote'])
			->bind(':id', $reviewId)
			->execute();
	}

	public static function deleteById(int $id): bool
	{
		$db = new Database();
		return $db->query("DELETE FROM reviews WHERE id = :id")
			->bind(':id', $id)
			->execute();
	}

	// ===== FINDERS =====

	public static function findById(int $reviewId): ?Review
	{
		$db = new Database();
		$row = $db->query("SELECT * FROM reviews WHERE id = :id")
			->bind(':id', $reviewId)
			->fetchFirst();

		return $row ? new self($row) : null;
	}

	// find review from specific user for specific post
	// if not found, returns null
	public static function findByPostAndUser(int $postId, int $userId): ?Review
	{
		$db = new Database();
		$row = $db->query("
			SELECT r.*
			FROM reviews r
			WHERE r.postId = :postId
			  AND r.userId = :userId
			LIMIT 1
		")
			->bind(':postId', $postId)
			->bind(':userId', $userId)
			->fetchFirst();

		return $row ? new self($row) : null;
	}
}
