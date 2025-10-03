<?php

namespace App\Models;

use App\Core\Database;
use App\Config\Config;
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

	public function create(array $data, array $file = []): bool
	{
		if (empty($data['title']) || empty($data['abstract'])) {
			throw new \InvalidArgumentException("Title and abstract are required.");
		}

		// Handle optional PDF upload
		$filename = '';
		if (!empty($file['tmp_name'])) {
			$filename = $this->uploadPdf($file);
		}

		// Insert into database 
		$db = new Database();

		return $db->query("
        INSERT INTO posts (userId, title, abstract, pathPDF, status)
        VALUES (:userId, :title, :abstract, :pathPDF, :status)
    ")
			->bind(':userId', $data['userId'])
			->bind(':title', $data['title'])
			->bind(':abstract', $data['abstract'])
			->bind(':pathPDF', $filename)
			->bind(':status', $data['status']->value)
			->execute();
	}

	private function uploadPdf(array $file): string
	{
		$uploadDir = Config::UPLOAD_DIR;

		if (!is_dir($uploadDir)) {
			mkdir($uploadDir, 0777, true);
		}

		// Validate MIME type
		$fileType = mime_content_type($file['tmp_name']);
		if ($fileType !== 'application/pdf') {
			throw new \InvalidArgumentException("Only PDF files are allowed.");
		}

		// Sanitize and generate filename
		$originalName = pathinfo($file['name'], PATHINFO_FILENAME);
		$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
		$filename = time() . '_' . $safeName . '.pdf';
		$target = $uploadDir . $filename;

		if (!move_uploaded_file($file['tmp_name'], $target)) {
			throw new \RuntimeException("Failed to upload PDF file.");
		}

		return $filename;
	}

	public function add(int $userId, string $title, string $abstract, string $filename, Status $status): bool
	{
		$db = new Database();
		return $db->query("INSERT INTO posts (userId, title, abstract, pathPDF, status)
				VALUES (:userId, :title, :abstract, :pathPDF, :status)")
			->bind(':userId', $userId)
			->bind(':title', $title)
			->bind(':abstract', $abstract)
			->bind(':pathPDF', $filename)
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

	public function update(int $postId, array $data, array $file = []): bool
	{
		if (empty($data['title']) || empty($data['abstract'])) {
			throw new \InvalidArgumentException("Title and abstract are required.");
		}

		// Keep existing PDF filename unless a new file is uploaded
		$pdfFilename = $this->getPdfFilename($postId);
		if (!empty($file['tmp_name'])) {
			$pdfFilename = $this->uploadPdf($file);
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
			->bind(':pathPDF', $pdfFilename)
			->bind(':status', $data['status'])
			->bind(':id', $postId)
			->execute();
	}

	public function delete(int $postId): bool
	{
		// Remove PDF file if it exists
		$filename = $this->getPdfFilename($postId);
		if ($filename) {
			$filePath = Config::UPLOAD_DIR . $filename;
			if (file_exists($filePath)) {
				unlink($filePath);
			}
		}

		$db = new Database();

		return $db->query("DELETE FROM posts WHERE id = :id")
			->bind(':id', $postId)
			->execute();
	}

	private function getPdfFilename(int $postId): string
	{
		$db = new Database();

		$result = $db
			->query("SELECT pathPDF FROM posts WHERE id = :id")
			->bind(':id', $postId)
			->fetchFirst();

		return $result['pathPDF'] ?? '';
	}
}
