<?php

namespace App\Models;

use App\Core\Database;
use App\Config\Config;
use App\Models\Status;
use App\Models\Review;
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
	// these are not saved in DB, but are good for storing related data
	public ?string $author = null;
	public ?Review $review = null;
	public ?array $reviews = null;
	public ?array $assignedReviewers = null;

	// Constructor from row
	// @param array<string, mixed> $data row from DB
	public function __construct(array $data = [])
	{
		if ($data) {
			$this->id = (int)($data['id'] ?? 0);
			$this->userId = (int)($data['userId'] ?? 0);
			$this->title = $data['title'] ?? '';
			$this->abstract = $data['abstract'] ?? '';
			$this->pathPDF = $data['pathPDF'] ?? '';
			$this->status = Status::tryFrom((int)($data['status'] ?? 0)) ?? Status::PendingReview;
			$this->author = $data['author'] ?? null;
			$this->createdAt = isset($data['created_at'])
				? new DateTime($data['created_at'])
				: new DateTime();
		}
	}

	// ===== CRUD =====

	// return all posts, optionally filtered by statuses
	// @param array<Status|int>|null $statuses
	// @return array<Post>
	public static function all(?array $statuses = null): array
	{
		$db = new Database();

		$sql = "
			SELECT p.*, u.username AS authorName
			FROM posts p
			LEFT JOIN users u ON p.userId = u.id
		";

		$bindParams = [];

		if ($statuses) {
			$placeholders = [];
			foreach ($statuses as $i => $status) {
				$ph = ":status$i";
				$placeholders[] = $ph;
				$bindParams[$ph] = $status instanceof Status ? $status->value : $status;
			}
			$sql .= " WHERE p.status IN (" . implode(',', $placeholders) . ")";
		}

		$sql .= " ORDER BY p.created_at DESC";

		$rows = $db->query($sql)->bindBulk($bindParams)->fetchAll();
		$posts = array_map(fn($row) => new self($row), $rows);

		foreach ($posts as $post) {
			$post->assignedReviewers = $post->getAssignedReviewers();
			$post->reviews = $post->getReviews();
		}

		return $posts;
	}

	// Create a new post
	// @param array<string, mixed> $data
	// @param array<string, mixed> $file
	public function create(array $data, array $file = []): bool
	{
		self::validateData($data);

		$pdfName = '';
		if (!empty($file['tmp_name'])) {
			$pdfName = $this->uploadPdf($file);
		}

		$db = new Database();
		return $db->query("
			INSERT INTO posts (userId, title, abstract, pathPDF, status)
			VALUES (:userId, :title, :abstract, :pathPDF, :status)
		")
			->bind(':userId', $data['userId'])
			->bind(':title', $data['title'])
			->bind(':abstract', $data['abstract'])
			->bind(':pathPDF', $pdfName)
			->bind(':status', $data['status']->value)
			->execute();
	}

	// Update post data
	// @param array<string, mixed> $data
	// @param array<string, mixed> $file
	public function update(int $postId, array $data, array $file = []): bool
	{
		self::validateData($data);

		// check PDF, optionally delete old if is new
		$pdfName = $this->getPdfFilename($postId);
		if (!empty($file['tmp_name'])) {
			if ($pdfName && file_exists(Config::UPLOAD_DIR . $pdfName)) {
				unlink(Config::UPLOAD_DIR . $pdfName);
			}
			$pdfName = $this->uploadPdf($file);
		}

		$db = new Database();
		return $db->query("
			UPDATE posts
			SET title = :title,
				abstract = :abstract,
				pathPDF = :pathPDF,
				status = :status
			WHERE id = :id
		")
			->bind(':title', $data['title'])
			->bind(':abstract', $data['abstract'])
			->bind(':pathPDF', $pdfName)
			->bind(':status', is_object($data['status']) ? $data['status']->value : $data['status'])
			->bind(':id', $postId)
			->execute();
	}

	// Delete post and related records
	public function delete(int $postId): bool
	{
		$filename = $this->getPdfFilename($postId);
		if ($filename) {
			$filePath = Config::UPLOAD_DIR . $filename;
			if (is_file($filePath)) {
				unlink($filePath);
			}
		}

		$db = new Database();

		$db->query("DELETE FROM post_reviewer WHERE postId = :pid")
			->bind(':pid', $this->id)
			->execute();

		$db->query("DELETE FROM reviews WHERE postId = :pid")
			->bind(':pid', $this->id)
			->execute();

		return $db->query("DELETE FROM posts WHERE id = :id")
			->bind(':id', $postId)
			->execute();
	}

	// Update status for specific post
	public function updateStatus(Status $status): bool
	{
		$db = new Database();
		$this->status = $status;
		return $db->query("UPDATE posts SET status = :status WHERE id = :pid")
			->bind(':status', $status->value)
			->bind(':pid', $this->id)
			->execute();
	}

	// ===== FIND =====

	// find post by its ID
	public static function find(int $postId): ?Post
	{
		$db = new Database();
		$row = $db->query("
			SELECT p.*, u.name AS author
			FROM posts p
			JOIN users u ON u.id = p.userId
			WHERE p.id = :id
			LIMIT 1
		")->bind(':id', $postId)->fetchFirst();

		return $row ? new self($row) : null;
	}

	// find post by its author ID
	/** @return array<Post> */
	public static function findByUser(int $userId): array
	{
		$db = new Database();
		$rows = $db->query("
			SELECT p.*, u.name AS author
			FROM posts p
			LEFT JOIN users u ON p.userId = u.id
			WHERE p.userId = :uid
			ORDER BY p.created_at DESC
		")->bind(':uid', $userId)->fetchAll();

		return array_map(fn($r) => new self($r), $rows);
	}

	// find all posts assigned to one reviewer
	/** @return array<Post> */
	public static function findAssignedToReviewer(int $userId): array
	{
		$db = new Database();
		$rows = $db->query("
			SELECT 
				p.*, u.name AS author,
				r.id AS reviewId,
				r.ratingInteresting, r.ratingImportant, r.ratingInovative, r.ratingNote
			FROM post_reviewer pr
			JOIN posts p ON pr.postId = p.id
			LEFT JOIN reviews r ON r.postId = p.id AND r.userId = :uid
			LEFT JOIN users u ON p.userId = u.id
			WHERE pr.userId = :uid
			  AND p.status = 10
			ORDER BY p.created_at DESC
		")->bind(':uid', $userId)->fetchAll();

		return array_map(function ($row) {
			$post = new self($row);
			if (!empty($row['reviewId'])) {
				$post->review = new Review([
					'id' => $row['reviewId'],
					'postId' => $row['id'],
					'userId' => $row['userId'],
					'ratingInteresting' => $row['ratingInteresting'],
					'ratingImportant' => $row['ratingImportant'],
					'ratingInovative' => $row['ratingInovative'],
					'ratingNote' => $row['ratingNote']
				]);
			}
			$post->author = $row['author'] ?? null;
			return $post;
		}, $rows);
	}

	// find all posts which are status == accepted
	/** @return array<Post> */
	public static function findAccepted(): array
	{
		$db = new Database();
		$rows = $db->query("
			SELECT p.id, p.title, p.abstract, p.pathPDF, u.name AS author
			FROM posts p
			LEFT JOIN users u ON p.userId = u.id
			WHERE p.status = :status
			ORDER BY p.created_at DESC
		")->bind(':status', Status::Accepted->value)->fetchAll();

		return array_map(fn($row) => new self($row), $rows);
	}

	// ===== REVIEWER ASSIGNMENT =====

	// add new reviewers to post by their IDs
	/** @param array<int> $reviewerIds */
	public function assignReviewers(array $reviewerIds): bool
	{
		$db = new Database();
		$current = $this->getAssignedReviewerIds();

		$toAdd = array_diff($reviewerIds, $current);
		foreach ($toAdd as $uid) {
			$db->query("INSERT INTO post_reviewer (postId, userId) VALUES (:pid, :uid)")
				->bind(':pid', $this->id)
				->bind(':uid', $uid)
				->execute();
		}
		return true;
	}

	// remove reviewers from post by their IDs
	/** @param array<int> $reviewerIds */
	public function removeReviewers(array $reviewerIds): bool
	{
		$db = new Database();
		$current = $this->getAssignedReviewerIds();

		$toRemove = array_intersect($current, $reviewerIds);
		foreach ($toRemove as $uid) {
			$db->query("DELETE FROM post_reviewer WHERE postId = :pid AND userId = :uid")
				->bind(':pid', $this->id)
				->bind(':uid', $uid)
				->execute();
		}
		return true;
	}

	// ===== HELPERS =====

	// Validate title and abstract
	// @param array<string, mixed> $data
	private static function validateData(array $data): void
	{
		if (empty($data['title']) || empty($data['abstract'])) {
			throw new \InvalidArgumentException("Title and abstract are required.");
		}
		if (mb_strlen($data['title']) > 255) {
			throw new \InvalidArgumentException("Title must be shorter than 255 characters.");
		}
	}

	// Upload PDF and return filename
	// @param array<string, mixed> $file
	private function uploadPdf(array $file): string
	{
		$uploadDir = rtrim(Config::UPLOAD_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		if (!is_dir($uploadDir)) {
			mkdir($uploadDir, 0777, true);
		}

		$fileType = mime_content_type($file['tmp_name']);
		if ($fileType !== 'application/pdf') {
			throw new \InvalidArgumentException("Only PDF files are allowed.");
		}

		// safe name, also not identical because of time
		$originalName = pathinfo($file['name'], PATHINFO_FILENAME);
		$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
		$filename = time() . '_' . $safeName . '.pdf';
		$target = $uploadDir . $filename;

		if (!move_uploaded_file($file['tmp_name'], $target)) {
			throw new \RuntimeException("Failed to upload PDF file.");
		}

		return $filename;
	}

	// Get stored PDF filename
	private function getPdfFilename(int $postId): string
	{
		$db = new Database();
		$row = $db->query("SELECT pathPDF FROM posts WHERE id = :id")
			->bind(':id', $postId)
			->fetchFirst();

		return $row['pathPDF'] ?? '';
	}

	// ===== STATUS / META =====

	public function isStatus(Status $status): bool
	{
		return $this->status === $status;
	}

	// user can only edit if post is in review process
	public function canEdit(): bool
	{
		return $this->isStatus(Status::PendingReview);
	}

	public function getStatusName(): string
	{
		return $this->status->label();
	}

	// ===== REVIEWS =====

	// Average rating of all reviews
	public function rating(): int
	{
		$db = new Database();
		$rows = $db->query("
			SELECT ratingInteresting, ratingImportant, ratingInovative
			FROM reviews
			WHERE postId = :pid
		")->bind(':pid', $this->id)->fetchAll();

		if (!$rows) {
			return 0;
		}

		$sum = 0;
		foreach ($rows as $r) {
			$sum += ($r['ratingInteresting'] + $r['ratingImportant'] + $r['ratingInovative']) / 3;
		}
		return (int)round($sum / count($rows));
	}

	// get all reviews for one specific post
	/** @return array<array<string,mixed>> */
	public function getReviews(): array
	{
		$db = new Database();
		$rows = $db->query("
			SELECT 
				r.ratingInteresting,
				r.ratingImportant,
				r.ratingInovative,
				r.ratingNote,
				u.name AS reviewerName
			FROM reviews r
			JOIN users u ON r.userId = u.id
			WHERE r.postId = :pid
			ORDER BY u.name
		")->bind(':pid', $this->id)->fetchAll();

		return array_map(function ($r) {
			$avg = round(($r['ratingInteresting'] + $r['ratingImportant'] + $r['ratingInovative']) / 3);
			return [
				'reviewerName' => $r['reviewerName'],
				'avgStars' => str_repeat('⭐', $avg) . str_repeat('☆', 5 - $avg),
				'ratingInteresting' => $r['ratingInteresting'],
				'ratingImportant' => $r['ratingImportant'],
				'ratingInovative' => $r['ratingInovative'],
				'note' => $r['ratingNote'],
			];
		}, $rows);
	}

	// get all reviewers assigned to this post
	/** @return array<User> */
	public function getAssignedReviewers(): array
	{
		$db = new Database();
		$rows = $db->query("
			SELECT u.*
			FROM post_reviewer pr
			JOIN users u ON pr.userId = u.id
			WHERE pr.postId = :pid
		")->bind(':pid', $this->id)->fetchAll();

		return array_map(fn($r) => new User($r), $rows);
	}

	// get IDs of all reviewers assigned to this post
	/** @return array<int> */
	public function getAssignedReviewerIds(): array
	{
		return array_map(fn($u) => $u->id, $this->getAssignedReviewers());
	}
}
