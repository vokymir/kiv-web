<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
	public int $id;
	public Role $role;
	public bool $blocked;

	public string $username;
	public string $name;
	public string $passwordHash;

	public function __construct(array $data = [])
	{
		if ($data) {
			$this->id = (int)($data['id'] ?? 0);
			$this->name = $data['name'] ?? '';
			$this->role = Role::tryFrom((int)($data['role'] ?? Role::Author->value)) ?? Role::Author;
			$this->blocked = (bool)($data['blocked'] ?? false);
		}
	}

	// ===== CRUD =====

	public static function all(): array
	{
		$db = new Database();
		$rows = $db->query("SELECT * FROM users ORDER BY name")->fetchAll();
		return array_map(fn($r) => new self($r), $rows);
	}

	public function update(array $data): bool
	{
		$db = new Database();
		return $db->query("
			UPDATE users
			SET role = :role, blocked = :blocked
			WHERE id = :id
		")
			->bind(':id', $this->id)
			->bind(':role', $data['role'])
			->bind(':blocked', $data['blocked'])
			->execute();
	}

	public function delete(): bool
	{
		$db = new Database();
		return $db->query("DELETE FROM users WHERE id = :id")
			->bind(':id', $this->id)
			->execute();
	}

	// ===== FINDs =====

	public static function find(int $id): ?self
	{
		$db = new Database();
		$row = $db->query("SELECT * FROM users WHERE id = :id")
			->bind(':id', $id)
			->fetchFirst();

		return $row ? new self($row) : null;
	}

	public static function getRandomSpeakers(int $limit = 3): array
	{
		$db = new Database();

		// Fetch a random set of authors with accepted posts
		$speakers = $db->query("
        SELECT u.id AS user_id, u.username, u.name, p.id AS post_id, p.title
        FROM posts p
        JOIN users u ON p.userId = u.id
        WHERE p.status = :accepted
        GROUP BY u.id, p.id
        ORDER BY RAND()
        LIMIT :limit
    ")
			->bind(':accepted', Status::Accepted->value)
			->bind(':limit', $limit, PDO::PARAM_INT)
			->fetchAll();

		// Add rating for each post using Post::rating()
		foreach ($speakers as &$speaker) {
			$post = new Post();
			$post->id = $speaker['post_id'];
			$speaker['rating'] = $post->rating();
		}

		return $speakers;
	}
}
