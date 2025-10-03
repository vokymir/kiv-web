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
	public string $passwordHash;

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
