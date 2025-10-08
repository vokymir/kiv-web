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
	public ?string $author = null;
	public ?Review $review = null;

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

	// ===== CRUD =====

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

	public function update(int $postId, array $data, array $file = []): bool
	{
		self::validateData($data);

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
		return $db->query("DELETE FROM posts WHERE id = :id")
			->bind(':id', $postId)
			->execute();
	}

	// ===== FIND =====

	public static function find(int $postId): ?Post
	{
		$db = new Database();
		$row = $db->query("
			SELECT p.*, u.username AS author
			FROM posts p
			JOIN users u ON u.id = p.userId
			WHERE p.id = :id
			LIMIT 1
		")
			->bind(':id', $postId)
			->fetchFirst();

		return $row ? new self($row) : null;
	}

	public static function findByUser(int $userId): array
	{
		$db = new Database();
		$rows = $db->query("
			SELECT p.*, u.username AS author
			FROM posts p
			LEFT JOIN users u ON p.userId = u.id
			WHERE p.userId = :uid
			ORDER BY p.created_at DESC
		")
			->bind(':uid', $userId)
			->fetchAll();

		return array_map(fn($r) => new self($r), $rows);
	}

	public static function findAssignedToReviewer(int $userId): array
	{
		$db = new Database();
		$rows = $db->query("
			SELECT 
				p.*, u.username AS authorName,
				r.id AS reviewId,
				r.ratingInteresting, r.ratingImportant, r.ratingInovative, r.ratingNote
			FROM post_reviewer pr
			JOIN posts p ON pr.postId = p.id
			LEFT JOIN reviews r ON r.postId = p.id AND r.userId = :uid
			LEFT JOIN users u ON p.userId = u.id
			WHERE pr.userId = :uid
			  AND p.status = 10
			ORDER BY p.created_at DESC
		")
			->bind(':uid', $userId)
			->fetchAll();

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
			$post->author = $row['authorName'] ?? null;
			return $post;
		}, $rows);
	}


	public static function findAccepted(): array
	{
		$db = new Database();
		$rows = $db->query("
        SELECT p.id, p.title, p.abstract, p.pathPDF, u.name AS author
        FROM posts p
        LEFT JOIN users u ON p.userId = u.id
        WHERE p.status = :status
        ORDER BY p.created_at DESC
    ")
			->bind(':status', Status::Accepted->value)
			->fetchAll();

		return array_map(fn($row) => new self($row), $rows);
	}

	// ===== HELPERS =====

	private static function validateData(array $data): void
	{
		if (empty($data['title']) || empty($data['abstract'])) {
			throw new \InvalidArgumentException("Title and abstract are required.");
		}
		if (mb_strlen($data['title']) > 255) {
			throw new \InvalidArgumentException("Title must be shorter than 255 characters.");
		}
	}

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

		$originalName = pathinfo($file['name'], PATHINFO_FILENAME);
		$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
		$filename = time() . '_' . $safeName . '.pdf';
		$target = $uploadDir . $filename;

		if (!move_uploaded_file($file['tmp_name'], $target)) {
			throw new \RuntimeException("Failed to upload PDF file.");
		}

		return $filename;
	}

	private function getPdfFilename(int $postId): string
	{
		$db = new Database();
		$row = $db->query("SELECT pathPDF FROM posts WHERE id = :id")
			->bind(':id', $postId)
			->fetchFirst();

		return $row['pathPDF'] ?? '';
	}

	public function isStatus(Status $status): bool
	{
		return $this->status === $status;
	}

	public function canEdit(): bool
	{
		return $this->isStatus(Status::PendingReview);
	}

	public function getStatusName(): string
	{
		return $this->status->label();
	}

	public function rating(): int
	{
		$db = new Database();
		$rows = $db->query("
			SELECT ratingInteresting, ratingImportant, ratingInovative
			FROM reviews
			WHERE postId = :pid
		")
			->bind(':pid', $this->id)
			->fetchAll();

		if (!$rows) {
			return 0;
		}

		$sum = 0;
		foreach ($rows as $r) {
			$sum += ($r['ratingInteresting'] + $r['ratingImportant'] + $r['ratingInovative']) / 3;
		}

		return (int)round($sum / count($rows));
	}
}
