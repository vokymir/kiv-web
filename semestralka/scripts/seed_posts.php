<?php
// scripts/seed_posts_by_authors.php
// Usage:
//   php scripts/seed_posts_by_authors.php        # create posts (1..5) per author
//   php scripts/seed_posts_by_authors.php -v     # verbose
//   php scripts/seed_posts_by_authors.php --pdf  # also attempt to create PDFs (requires mpdf/mpdf)

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Role;
use App\Models\Status;
use Mpdf\Mpdf;

if (php_sapi_name() !== 'cli') {
	echo "Run from CLI: php scripts/seed_posts_by_authors.php\n";
	exit(1);
}

$argv_flags = $argv;
array_shift($argv_flags); // remove script name

$verbose = in_array('-v', $argv_flags, true) || in_array('--verbose', $argv_flags, true);
$doPdf = in_array('--pdf', $argv_flags, true);

function logv(string $s)
{
	global $verbose;
	if ($verbose) echo $s . PHP_EOL;
}

$lorem = [
	"Exploring why the horizon never curves in airplane windows.",
	"A hilarious deep dive into the mystery of disappearing satellites.",
	"Experimental tests with laser pointers and pancake plates.",
	"Flat-earth mapping: how to measure the world without curvature.",
	"Evidence that gravity is just a conspiracy by round-earthers.",
	"Using moonbeams to prove the Earth is perfectly level.",
	"The secret physics of wobbling compasses and skewed shadows.",
	"Practical guide to presenting flat-earth arguments at parties.",
	"Social psychology of flat-earth believers and skeptics alike.",
	"Demonstrating a round-earth illusion with everyday kitchen tools.",
	"Crowdsourced horizon measurements and the art of selective data.",
	"How to fold a map when the world is a plate, not a globe."
];

function rand_abstract(array $lorem): string
{
	$parts = [];
	$sentences = rand(3, 6);
	for ($i = 0; $i < $sentences; $i++) $parts[] = $lorem[array_rand($lorem)];
	$parts[] = "Seed details: " . substr(md5((string)rand()), 0, 10);
	return implode("\n\n", $parts);
}

function make_clean_title(array $lorem): string
{
	$t = $lorem[array_rand($lorem)];
	$t = preg_replace('/[^\p{L}\p{N}\s\-,:]/u', '', $t);
	$t = preg_replace('/\d+/', '', $t);
	return trim(mb_substr($t, 0, 80));
}

// optional PDF generator
function generatePdfForPost(int $postId, string $title, string $abstract, string $outDir): string
{
	if (!class_exists(\Mpdf\Mpdf::class)) {
		throw new RuntimeException("mPDF not installed. Install with: composer require mpdf/mpdf");
	}
	if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
		throw new RuntimeException("Failed to create PDF output directory: $outDir");
	}

	$html = "<h1>$title</h1><p>" . nl2br(htmlspecialchars($abstract)) . "</p>";
	$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8']);
	$mpdf->WriteHTML($html);
	$filename = "post_$postId.pdf";
	$mpdf->Output(rtrim($outDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename, \Mpdf\Output\Destination::FILE);
	return $filename;
}

try {
	$db = new Database();
	$authors = $db->query("SELECT id, username, name FROM users WHERE role = :role ORDER BY id")
		->bind(':role', Role::Author->value)
		->fetchAll();

	if (!$authors) {
		echo "No authors found (role=Author).\n";
		exit(0);
	}

	$outPdfDir = __DIR__ . '/../public/uploads';
	if ($doPdf && !is_dir($outPdfDir)) mkdir($outPdfDir, 0777, true);

	$total = 0;
	foreach ($authors as $a) {
		$authorId = (int)$a['id'];
		$num = rand(3, 6); // ensure enough per author
		logv("Author #$authorId -> $num posts");

		for ($i = 0; $i < $num; $i++) {
			$title = make_clean_title($lorem);
			$abstract = rand_abstract($lorem);

			// ensure good mix of statuses
			$statuses = [Status::Accepted->value, Status::Rejected->value, Status::PendingReview->value];
			$status = $statuses[array_rand($statuses)];

			$db->query("INSERT INTO posts (userId, title, abstract, pathPDF, status) VALUES (:u,:t,:a,'',:s)")
				->bind(':u', $authorId)
				->bind(':t', $title)
				->bind(':a', $abstract)
				->bind(':s', $status)
				->execute();

			$row = $db->query(
				"SELECT id FROM posts WHERE userId = :uid AND title = :title ORDER BY id DESC LIMIT 1"
			)
				->bind(':uid', $authorId)
				->bind(':title', $title)
				->fetchFirst();

			$postId = (int)$row['id'];

			if ($doPdf) {
				try {
					$pdfRel = generatePdfForPost($postId, $title, $abstract, $outPdfDir);
					$db->query("UPDATE posts SET pathPDF = :pdf WHERE id = :id")
						->bind(':pdf', $pdfRel)
						->bind(':id', $postId)
						->execute();
				} catch (Throwable $e) {
					echo "PDF gen failed for post $postId: " . $e->getMessage() . PHP_EOL;
				}
			}
			$total++;
		}
	}

	echo "✅ Done: created $total posts for " . count($authors) . " authors.\n";

	$counts = $db->query("SELECT status, COUNT(*) AS c FROM posts GROUP BY status")->fetchAll();
	echo "Posts by status:\n";
	foreach ($counts as $c) {
		$label = Status::from((int)$c['status'])->label();
		echo " - $label: {$c['c']}\n";
	}

	exit(0);
} catch (Throwable $e) {
	fwrite(STDERR, "Error: {$e->getMessage()}\n");
	exit(1);
}
