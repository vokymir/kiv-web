<?php
// scripts/seed_assign_reviews.php
// Usage:
//   php scripts/seed_assign_reviews.php              # process all posts
//   php scripts/seed_assign_reviews.php -v           # verbose
//   php scripts/seed_assign_reviews.php --dry-run    # simulate only
//   php scripts/seed_assign_reviews.php --post=123   # only for post id 123
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Role;
use App\Models\Status;

if (php_sapi_name() !== 'cli') {
	echo "Run from CLI: php scripts/seed_assign_reviews.php\n";
	exit(1);
}

// parse flags
$argv_copy = $argv;
array_shift($argv_copy);
$verbose = in_array('-v', $argv_copy, true) || in_array('--verbose', $argv_copy, true);
$dryRun = in_array('--dry-run', $argv_copy, true);

$postFilter = null;
foreach ($argv_copy as $a) {
	if (strpos($a, '--post=') === 0) {
		$postFilter = (int)substr($a, 7);
	}
}

function logv(string $msg)
{
	global $verbose;
	if ($verbose) echo $msg . PHP_EOL;
}

function info(string $msg)
{
	echo $msg . PHP_EOL;
}

try {
	$db = new Database();

	// get reviewer ids
	$rows = $db->query("SELECT id, username FROM users WHERE role = :r")
		->bind(':r', Role::Reviewer->value)
		->fetchAll();
	$reviewerIds = array_map(fn($r) => (int)$r['id'], $rows);

	if (empty($reviewerIds)) {
		throw new RuntimeException("No reviewers found (role=Reviewer). Seed some users first.");
	}

	// notes and rating pool
	$notes = [
		"Thoroughly convincing — would attend the talk.",
		"Interesting approach but lacks calibrated measurements.",
		"Good attempt, consider tighter error analysis.",
		"Entertaining and creative, presentation recommended.",
		"Findings are questionable — suggest revisions.",
		"Solid demonstration, minor corrections suggested.",
		"Novel perspective, needs clearer diagrams.",
		"Fun and persuasive; bring props to the talk."
	];

	// fetch posts (optionally single)
	if ($postFilter) {
		$posts = $db->query("SELECT id, userId, title, status FROM posts WHERE id = :id")
			->bind(':id', $postFilter)
			->fetchAll();
	} else {
		$posts = $db->query("SELECT id, userId, title, status FROM posts ORDER BY id")
			->fetchAll();
	}

	if (!$posts) {
		info("No posts found to process.");
		exit(0);
	}

	$totalAssigned = 0;
	$totalReviewsCreated = 0;

	foreach ($posts as $p) {
		$postId = (int)$p['id'];
		$status = (int)$p['status'];
		$title = $p['title'] ?? '';

		// determine required minimum reviewers & reviews
		$isFinalized = in_array($status, [Status::Accepted->value, Status::Rejected->value], true);
		$minRequired = $isFinalized ? 3 : 1;

		// get currently assigned reviewers
		$assignedRows = $db->query("SELECT userId FROM post_reviewer WHERE postId = :pid")
			->bind(':pid', $postId)
			->fetchAll();
		$assignedIds = array_map(fn($r) => (int)$r['userId'], $assignedRows);

		// choose how many we want assigned in total (between minRequired and 5)
		$targetAssignCount = $isFinalized ? max(3, rand(3, 5)) : rand(1, 5);

		// compute which reviewers we can add (exclude already assigned and the post author)
		$available = array_values(array_diff($reviewerIds, $assignedIds, [$p['userId']]));
		if (empty($available) && count($assignedIds) < $targetAssignCount) {
			logv("Post {$postId}: not enough reviewers available to reach target (assigned " . count($assignedIds) . ").");
		}

		// pick reviewers to add
		$toAdd = [];
		if (count($assignedIds) < $targetAssignCount) {
			shuffle($available);
			$need = $targetAssignCount - count($assignedIds);
			$toAdd = array_slice($available, 0, $need);
		}

		// insert post_reviewer for each toAdd
		foreach ($toAdd as $rid) {
			if ($dryRun) {
				logv("[DRY] Would assign reviewer {$rid} to post {$postId}");
			} else {
				$db->query("INSERT IGNORE INTO post_reviewer (postId, userId) VALUES (:pid, :uid)")
					->bind(':pid', $postId)
					->bind(':uid', $rid)
					->execute();
				logv("Assigned reviewer {$rid} -> post {$postId}");
			}
			$totalAssigned++;
			$assignedIds[] = $rid;
		}

		// refresh assignedIds (in case of DRY we simply combine)
		$assignedIds = array_values(array_unique($assignedIds));

		// fetch existing reviews for this post (map userId => review id)
		$reviewRows = $db->query("SELECT id, userId FROM reviews WHERE postId = :pid")
			->bind(':pid', $postId)
			->fetchAll();
		$existingReviewers = array_map(fn($r) => (int)$r['userId'], $reviewRows);

		// ensure at least minRequired reviews exist for finalized posts
		$currentReviewsCount = count($existingReviewers);
		$needReviews = $isFinalized ? max(0, $minRequired - $currentReviewsCount) : 0;

		// create reviews from assigned reviewers who don't yet have a review
		$candidates = array_values(array_diff($assignedIds, $existingReviewers));
		shuffle($candidates);

		// create reviews to satisfy min requirement first
		$createdForThisPost = 0;
		while ($needReviews > 0 && !empty($candidates)) {
			$uid = array_shift($candidates);
			$ratingI = rand(1, 5);
			$ratingJ = rand(1, 5);
			$ratingK = rand(1, 5);
			$note = $notes[array_rand($notes)];

			if ($dryRun) {
				logv("[DRY] Would create review by user {$uid} for post {$postId} ({$ratingI},{$ratingJ},{$ratingK})");
			} else {
				$db->query("
                    INSERT INTO reviews (postId, userId, ratingInteresting, ratingImportant, ratingInovative, ratingNote)
                    VALUES (:pid, :uid, :ri, :rj, :rk, :note)
                ")->bind(':pid', $postId)
					->bind(':uid', $uid)
					->bind(':ri', $ratingI)
					->bind(':rj', $ratingJ)
					->bind(':rk', $ratingK)
					->bind(':note', $note)
					->execute();
				logv("Created review by {$uid} for post {$postId}");
			}
			$totalReviewsCreated++;
			$createdForThisPost++;
			$needReviews--;
		}

		// optionally create a few extra random reviews from remaining assigned reviewers (non-final posts)
		if (!$isFinalized) {
			// small chance to create reviews for some assigned reviewers
			foreach ($assignedIds as $uid) {
				if (rand(1, 100) <= 35) { // ~35% chance
					if (in_array($uid, $existingReviewers, true)) continue;
					$ratingI = rand(1, 5);
					$ratingJ = rand(1, 5);
					$ratingK = rand(1, 5);
					$note = $notes[array_rand($notes)];
					if ($dryRun) {
						logv("[DRY] Would create extra review by {$uid} for post {$postId}");
					} else {
						$db->query("
                            INSERT INTO reviews (postId, userId, ratingInteresting, ratingImportant, ratingInovative, ratingNote)
                            VALUES (:pid, :uid, :ri, :rj, :rk, :note)
                        ")->bind(':pid', $postId)
							->bind(':uid', $uid)
							->bind(':ri', $ratingI)
							->bind(':rj', $ratingJ)
							->bind(':rk', $ratingK)
							->bind(':note', $note)
							->execute();
						logv("Created extra review by {$uid} for post {$postId}");
					}
					$totalReviewsCreated++;
				}
			}
		}

		// final info
		$finalAssigned = count($assignedIds);
		$finalReviewCount = (int)$db->query("SELECT COUNT(*) AS c FROM reviews WHERE postId = :pid")->bind(':pid', $postId)->fetchFirst()['c'] ?? 0;
		info("Post {$postId} | status=" . Status::from($status)->label() . " | title=\"{$title}\" | assigned={$finalAssigned} | reviews={$finalReviewCount}");
	}

	info("\nDone. Assigned reviewer pairs (attempted): {$totalAssigned}. Reviews created (attempted): {$totalReviewsCreated}");
	if ($dryRun) info("Note: dry-run mode, no DB changes were made.");

	exit(0);
} catch (\Throwable $e) {
	fwrite(STDERR, "Error: " . $e->getMessage() . PHP_EOL);
	exit(1);
}
