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

	public static function findById(int $reviewId): ?Review
	{
		$db = new Database();
		$row = $db->query("SELECT * FROM reviews WHERE id = :id")
			->bind(':id', $reviewId)
			->fetchFirst();

		return $row ? new self($row) : null;
	}

	public static function findByPostAndUser(int $postId, int $userId): ?Review
	{
		$db = new Database();
		$row = $db->query("
		SELECT * FROM reviews WHERE postId = :postId AND userId = :userId LIMIT 1
	")
			->bind(':postId', $postId)
			->bind(':userId', $userId)
			->fetchFirst();

		return $row ? new self($row) : null;
	}

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

	public static function save(array $data): bool
	{
		$db = new Database();

		$existing = $db->query("
			SELECT id FROM reviews WHERE postId = :postId AND userId = :userId LIMIT 1
		")
			->bind(':postId', $data['postId'])
			->bind(':userId', $data['userId'])
			->fetchFirst();

		if ($existing) {
			// Update existing review
			return $db->query("
				UPDATE reviews
				SET ratingInteresting = :ratingInteresting,
					ratingImportant = :ratingImportant,
					ratingInovative = :ratingInovative,
					ratingNote = :ratingNote,
					created_at = NOW()
				WHERE id = :id
			")
				->bind(':ratingInteresting', $data['ratingInteresting'])
				->bind(':ratingImportant', $data['ratingImportant'])
				->bind(':ratingInovative', $data['ratingInovative'])
				->bind(':ratingNote', $data['ratingNote'])
				->bind(':id', $existing['id'])
				->execute();
		}

		// Create new review
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
}
