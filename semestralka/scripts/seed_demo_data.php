<?php
// Run from CLI: php scripts/seed_demo_data.php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Config\Config;
use App\Models\Role;
use App\Models\Status;

if (php_sapi_name() !== 'cli') {
	echo "Please run from CLI: php scripts/seed_demo_data.php\n";
	exit;
}

function h($p)
{
	return password_hash($p, PASSWORD_BCRYPT);
}

try {
	$db = new Database();

	// -------------------------------------------------------------------------
	// Helper data (funny names / usernames)
	// -------------------------------------------------------------------------
	$first = ['Flat', 'Round', 'Spinning', 'Wobbly', 'Gravityless', 'Moonbeam', 'Compass', 'Pole', 'Skewed', 'Tilted', 'Sky', 'Horizon', 'Tinfoil', 'Feather', 'Quirky', 'Crispy', 'Bouncy'];
	$last  = ['Jones', 'McGee', 'O\'Flat', 'Vanishing', 'Sprocket', 'Turner', 'Fitz', 'McHollow', 'Droplet', 'Featherstone', 'Noodle', 'Wren', 'Pancake', 'Whimsy', 'Loop'];
	$adjectives = ['Professor', 'Dr', 'Sir', 'Madam', 'Captain', 'Agent', 'Mister', 'Ms'];
	$nouns = ['Orbit', 'Edge', 'Rim', 'Map', 'Compass', 'Plate', 'Panel', 'Skyscape', 'Echo', 'Beacon', 'Slide'];

	$funPasswords = ['flatIsFun', 'noGravity!', 'pancake42', 'tilt&spin', 'gravity?no', 'round?nah', 'horiz0n', 'tinfoilHat', 'beacon123', 'coriolis'];

	// roles values assumed: Author=10, Reviewer=20, Admin=30, Superadmin=50
	$ROLE_AUTHOR = Role::Author->value;
	$ROLE_REVIEWER = Role::Reviewer->value;
	$ROLE_ADMIN = Role::Admin->value;
	$ROLE_SUPER = Role::Superadmin->value;

	// -------------------------------------------------------------------------
	// Create users
	// -------------------------------------------------------------------------
	echo "== Seeding users ==\n";

	// ensure one superadmin
	$superUsername = 'supremus';
	$db->query("INSERT IGNORE INTO users (username, passwordHash, name, role) VALUES (:u,:p,:n,:r)")
		->bind(':u', $superUsername)
		->bind(':p', h('superfun'))
		->bind(':n', 'Supreme Admin')
		->bind(':r', $ROLE_SUPER)
		->execute();

	// function to insert N users of a role
	$insertUsers = function (int $count, int $role, string $prefix) use ($db, $first, $last, $adjectives, $nouns, $funPasswords) {
		$created = 0;
		$tries = 0;
		while ($created < $count && $tries < $count * 5) {
			$tries++;
			$fn = $first[array_rand($first)];
			$ln = $last[array_rand($last)];
			$adj = $adjectives[array_rand($adjectives)];
			$noun = $nouns[array_rand($nouns)];
			$username = strtolower(preg_replace('/[^a-z0-9]/', '', $prefix . '_' . $fn . substr($ln, 0, 3) . rand(1, 999)));
			$name = "$adj $fn $ln";
			$pwd = $funPasswords[array_rand($funPasswords)] . rand(1, 99);

			try {
				$db->query("INSERT INTO users (username, passwordHash, name, role) VALUES (:username,:ph,:name,:role)")
					->bind(':username', $username)
					->bind(':ph', password_hash($pwd, PASSWORD_BCRYPT))
					->bind(':name', $name)
					->bind(':role', $role)
					->execute();
				$created++;
				echo " - created user $username (role=$role)\n";
			} catch (\Throwable $e) {
				// duplicate username or other error - skip and continue
				echo " skip duplicate $username\n";
			}
		}
		return $created;
	};

	// create at least few per role (admins/authors/reviewers)
	$adminsCreated = $insertUsers(8, $ROLE_ADMIN, 'admin');
	$authorsCreated = $insertUsers(13, $ROLE_AUTHOR, 'author'); // a few more authors
	$reviewersCreated = $insertUsers(10, $ROLE_REVIEWER, 'rev');

	echo "Users created: admins=$adminsCreated authors=$authorsCreated reviewers=$reviewersCreated\n\n";

	// fetch user ids grouped by role
	$rolesToIds = function (int $role) use ($db) {
		$rows = $db->query("SELECT id FROM users WHERE role = :r")
			->bind(':r', $role)
			->fetchAll();
		return array_map(fn($r) => (int)$r['id'], $rows);
	};

	$adminIds = $rolesToIds($ROLE_ADMIN);
	$authorIds = $rolesToIds($ROLE_AUTHOR);
	$reviewerIds = $rolesToIds($ROLE_REVIEWER);
	$superIds = $rolesToIds($ROLE_SUPER);

	if (count($authorIds) < 3) {
		echo "Not enough authors (need at least 3). Exiting.\n";
		exit;
	}
	if (count($reviewerIds) < 5) {
		echo "Not enough reviewers (need at least 5). Exiting.\n";
		exit;
	}

	// -------------------------------------------------------------------------
	// create posts: 20 per status
	// statuses: 10 = in_review/pending, 20 = accepted, 30 = rejected
	// -------------------------------------------------------------------------
	echo "== Creating posts ==\n";

	// get all statuses dynamically
	$statuses = [];
	foreach (Status::cases() as $s) {
		$statuses[$s->value] = $s->label(); // map value => label
	}

	// simple abstract generator
	$lorem = [
		"Explores the evidence for a perfectly flat horizon and implications for navigation.",
		"A humorous take on satellite imagery and conspiracy interpretation.",
		"Proposes a new method to measure curvature (spoiler: none found).",
		"Historical analysis of flat-earth societies and conference culture.",
		"Experimental demonstration using lasers and long-range photography.",
		"Philosophical implications of a non-spherical planet for science pedagogy.",
		"Crowdsourced measurements and the social dynamics of dissent.",
		"Practical tips for presenting flat-earth claims in mixed audiences.",
		"Data visualization: how to make a convincing horizon plot.",
		"The effect of cognitive bias on interpreting geodetic measurements."
	];

	$createdPosts = 0;
	foreach ($statuses as $stValue => $stLabel) {
		for ($i = 0; $i < 20; $i++) {
			$title = sprintf(
				"%s — %s #%d [%s]",
				$lorem[array_rand($lorem)],
				ucfirst($nouns[array_rand($nouns)]),
				rand(1, 999),
				date('YmdHis') . substr(md5(rand()), 0, 6)
			);

			$abstract = $lorem[array_rand($lorem)] . " This is a demonstration abstract for the Flat Earth Society Conference. Generated seed.";
			// pick an author (spread posts among authors)
			$authorId = $authorIds[array_rand($authorIds)];

			// insert
			$db->query("INSERT INTO posts (userId, title, abstract, pathPDF, status) VALUES (:uid,:title,:abstract,'',:status)")
				->bind(':uid', $authorId)
				->bind(':title', $title)
				->bind(':abstract', $abstract)
				->bind(':status', $stValue)
				->execute();

			// fetch post id by unique title (safe because title includes timestamp/hash)
			$row = $db->query("SELECT id FROM posts WHERE title = :title ORDER BY id DESC LIMIT 1")
				->bind(':title', $title)
				->fetchFirst();
			$postId = (int)$row['id'];

			echo " - created post #$postId (status=$stValue)\n";
			$createdPosts++;
		}
	}

	echo "Total posts created: $createdPosts\n\n";

	// -------------------------------------------------------------------------
	// For each post: assign 1-5 reviewers and create 1-5 reviews (distinct reviewers)
	// -------------------------------------------------------------------------
	echo "== Assigning reviewers and creating reviews ==\n";

	$allPosts = $db->query("SELECT id FROM posts")->fetchAll();
	foreach ($allPosts as $p) {
		$postId = (int)$p['id'];

		// decide how many reviewers to assign (1..5)
		$assignCount = rand(1, 5);
		// pick distinct reviewer ids
		$shuffled = $reviewerIds;
		shuffle($shuffled);
		$assign = array_slice($shuffled, 0, $assignCount);

		foreach ($assign as $rid) {
			// insert into post_reviewer but ignore duplicates
			try {
				$db->query("INSERT IGNORE INTO post_reviewer (postId, userId) VALUES (:pid,:uid)")
					->bind(':pid', $postId)
					->bind(':uid', $rid)
					->execute();
			} catch (\Throwable $e) {
				// ignore
			}
		}

		// create reviews: choose a random number between 1 and min(5, assigned count)
		$reviewsToCreate = rand(1, min(5, count($assign)));
		$selectedForReview = array_slice($assign, 0, $reviewsToCreate);

		foreach ($selectedForReview as $revId) {
			$ri = rand(1, 5);
			$rj = rand(1, 5);
			$rk = rand(1, 5);

			$notes = [
				"Thoroughly convincing — would attend the talk.",
				"Interesting ideas but needs more solid evidence.",
				"Good methodology, some gaps in measurement.",
				"Entertaining and creative, presentation recommended.",
				"Findings are questionable — suggest revisions.",
				"Solid demonstration, minor corrections suggested."
			];
			$note = $notes[array_rand($notes)];

			// insert review
			$db->query("INSERT INTO reviews (postId, userId, ratingInteresting, ratingImportant, ratingInovative, ratingNote) VALUES (:pid,:uid,:ri,:rj,:rk,:note)")
				->bind(':pid', $postId)
				->bind(':uid', $revId)
				->bind(':ri', $ri)
				->bind(':rj', $rj)
				->bind(':rk', $rk)
				->bind(':note', $note)
				->execute();
		}

		echo " - post #$postId assigned " . count($assign) . " reviewers, created " . count($selectedForReview) . " reviews\n";
	}

	echo "\nSeeding complete.\n";
} catch (Throwable $e) {
	echo "Error while seeding: " . $e->getMessage() . "\n";
	exit(1);
}
