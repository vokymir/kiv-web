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

	// @param array<string, mixed> $data
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

	// get all users in the DB
	// @return array<User>
	public static function all(): array
	{
		$db = new Database();
		$rows = $db->query("SELECT * FROM users ORDER BY name")->fetchAll();
		return array_map(fn($r) => new self($r), $rows);
	}

	// get all users in DB with certain role, or any role from array
	// @param Role|array<Role> $roles
	// @return array<User>
	public static function allByRole(Role|array $roles): array
	{
		if ($roles instanceof Role) {
			$roles = [$roles];
		}
		if (empty($roles)) {
			return [];
		}

		$db = new Database();
		$placeholders = [];
		$bindParams = [];

		// prepare placeholders and value binding
		foreach ($roles as $i => $role) {
			$ph = ":role$i";
			$placeholders[] = $ph;
			$bindParams[$ph] = $role->value;
		}

		$sql = "SELECT * FROM users WHERE role IN (" . implode(',', $placeholders) . ") ORDER BY name";
		$rows = $db->query($sql)
			->bindBulk($bindParams)
			->fetchAll();

		return array_map(fn($r) => new self($r), $rows);
	}

	// update user = only role and blocked I/0
	// @param array $data
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

	// delete user
	public function delete(): bool
	{
		$db = new Database();
		return $db->query("DELETE FROM users WHERE id = :id")
			->bind(':id', $this->id)
			->execute();
	}

	// ===== FINDERS =====

	// find user with specific ID, or return null
	// @return ?User
	public static function find(int $id): ?self
	{
		$db = new Database();
		$row = $db->query("SELECT * FROM users WHERE id = :id")
			->bind(':id', $id)
			->fetchFirst();

		return $row ? new self($row) : null;
	}

	// find <limit> number of authors who have any of their posts accepted 
	// @return array<array<string,mixed>>
	public static function getRandomSpeakers(int $limit = 3): array
	{
		$db = new Database();

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

		foreach ($speakers as &$speaker) {
			$post = new Post();
			$post->id = $speaker['post_id'];
			$speaker['rating'] = $post->rating();
		}

		return $speakers;
	}
}
