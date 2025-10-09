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
		"Thoroughly convincing — would attend the talk and bring a notepad. The presenter builds a charming narrative, peppers the talk with cheeky demonstrations and even the skeptical aunt might giggle. Recommend reserving extra Q&A time for conspiratorial footnotes and audience confessions.",

		"Interesting approach but lacks calibrated measurements. Nice intuition and backyard bravado, however please add reproducible steps: tape measures, level apps, timestamped photos and a notes log. A short troubleshooting section (wind, reflections, syrup on pancake) would make this much stronger.",

		"Good attempt, consider tighter error analysis. The pancakes-to-horizon comparison is delightful but needs standard deviations, repeated trials and a clear method for controlling confounders (birds, drones, tourists). Still very readable and charming — just tighten the numbers.",

		"Entertaining and creative; presentation recommended. Audience engagement is guaranteed — particularly the live demo bit. Suggest adding an inflatable globe for dramatic contrast, a one-slide myth-busting cheat sheet, and a wink at mainstream science to keep things playful.",

		"Findings are questionable — suggest revisions before cosmic peer review. The logic trail includes a few interpretive leaps (and one literal hop); expand the methods, include raw data and pre-register the next backyard experiment. With those fixes this could be a crowd favorite.",

		"Solid demonstration, minor corrections suggested. Diagrams are fun but labels are ambiguous; move the moonbeam anecdote to an appendix and include a clear, repeatable protocol for backyard replication. Add a short checklist so volunteers can reproduce results reliably.",

		"Novel perspective, needs clearer diagrams and a glossary. 'Edge effects' is delightfully named but underdefined — include step-by-step photos, a list of required household props and brief captions for each figure. The idea is fresh and theatrical; clarity will make it credible (in the playful sense).",

		"Fun and persuasive — bring props to the talk. A laser pointer, a frying pan and one polite moderator will elevate the demo to theatre. Recommend a live demo slot, printed takeaway with ‘how-to’ home experiments, and a signed napkin giveaway for maximum charm.",

		"Clever mashup of satire and fieldwork — the writing sparkles and the diagrams are delightfully theatrical. Tighten up the slide deck to 8 slides, add a one-page handout with stepwise experiments for kids, and consider a short FAQ addressing obvious sceptic questions (answered with humour).",

		"Well-written and amusing; the methodology could use one more control test (cat on the table optional). Strong party-talk potential — propose a short follow-up study with clearer controls and reproducible notes so the next generation of horizon-measurers can build on this work."
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
