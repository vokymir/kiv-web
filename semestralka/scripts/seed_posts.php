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
	"Exploring why the horizon never curves in airplane windows. We take a friendly, slightly paranoid tour through economy-class observation techniques — from squinting strategically at the wing to insisting the flight attendant move the tray table for better viewing. Includes practical tips for convincing sceptical neighbours, a short appendix on how to politely interrupt pilots, and one patented 'horizon-is-flat' napkin-folding method (patent pending).",

	"A hilarious deep dive into the mystery of disappearing satellites. A totally scientific investigation involving binoculars, a backyard telescope and one suspiciously reflective pie tin. Features eyewitness testimony, a timeline of suspicious beeps, and a 12-point plan for locating the hidden satellite studio (spoiler: it's probably behind the deli). Contains sarcastic footnotes that gently roast mainstream astronomy and a corny joke about GPS being 'just a polite suggestion'.",

	"Experimental tests with laser pointers and pancake plates. We set up lab-grade rigging (read: a table, two chairs and leftover breakfast) and attempt to measure curvature using household cookware. The methods section includes diagrams, a safety warning about lasers and pets, and a 'what to tell your significant other' script. Results are presented with charming confidence and a generous helping of tongue-in-cheek conclusions — plus a dad-joke about syrup and evidence.",

	"Flat-earth mapping: how to measure the world without curvature. A convivial handbook for cartographers who prefer plates to balls, featuring step-by-step instructions for long-distance measurements, compass re-interpretations, and how to explain contour lines at dinner parties. We also discuss scale models (cracker-based), community-sourced measurements that conveniently ignore outliers, and a short meditation on why maps look nicer when you don't have to draw poles.",

	"Evidence that gravity is just a conspiracy by round-earthers. This piece gently proposes that 'down' is an opinion and presents several tongue-in-cheek experiments you can do at home to test whether heavier objects fall faster because of mass or because 'the narrative says so'. There's a satirical methodology, a philosophical aside about apples and authority, and a footnote that admits we may have dropped a few apples on purpose for dramatic effect. Good for cocktail party debates and awkward family reunions.",

	"Using moonbeams to prove the Earth is perfectly level. A whimsical chapter on lunar lighting, including advice on how to interpret shadows cast by garden gnomes and why moonlight looks different when you are convinced of a theory. Includes an illustrated argument for why tides are 'just polite waves' and a short poem about celestial choreography. Also contains a mild warning: do not attempt to herd moonbeams without adult supervision.",

	"The secret physics of wobbling compasses and skewed shadows. A forensic-style look at compass misdirection, sun angle subterfuge, and how perspective artists might be trying to trick your eyeballs. We review classic experiments, provide a flowchart for deciding whether your instrument is malfunctioning or 'telling the truth', and include a cynical aside about how some data is more equal than other data. Comes with a handy troubleshooting list and one sarcastic motivational quote.",

	"Practical guide to presenting flat-earth arguments at parties. Networking advice, humour cues, and an etiquette section on how to avoid starting a shouting match with the in-laws. You'll find sample opening lines, a recommended three-slide deck (slide three: dessert), and tips for gracefully redirecting scientific evidence to metaphors about pancakes. Bonus: an appendix on turning objections into icebreakers and one dad-joke guaranteed to win you a polite laugh.",

	"Social psychology of flat-earth believers and skeptics alike. We examine why people love contrarian stories, how groupthink becomes a hobby, and why confirmation bias pairs so well with high-quality tinfoil hats. The piece mixes gentle anthropology with dry humour, offers suggestions for civil debate, and ends with a cheerful reminder that everyone is allowed to be slightly wrong sometimes. Includes a short, sardonic case study about a community meeting that lasted eight hours and concluded with cookies.",

	"Demonstrating a round-earth illusion with everyday kitchen tools. A short, silly lab manual showing how to create convincing 'round-world' demos using a bowl, a flashlight and someone willing to hold the bowl still. It includes step-by-step instructions, choreography notes (for the person passing the flashlight), and a tongue-in-cheek critique of theatrical props being mistaken for evidence. Perfect for classroom skits, late-night conversations and people who appreciate theatrical science with a wink.",

	"Crowdsourced horizon measurements and the art of selective data. An upbeat, slightly mischievous exploration of how to run a distributed measurement campaign, from recruiting volunteers to gracefully handling contradictory results. We celebrate community spirit, explain how to bake statistics into a convincing pie chart, and offer cheeky strategies for interpreting 'noisy' data as 'interesting'. Also includes a recipe for celebratory pancakes if your campaign convinces even one neighbour.",

	"How to fold a map when the world is a plate, not a globe. A lighthearted craft-and-theory guide combining origami, cartography and policy suggestions for dinner conversation. Step-by-step folding diagrams, a flow of rhetorical flourishes for when someone invokes the Coriolis effect, and a short satirical manifesto on why plates should be appreciated for their aerodynamic neutrality. Finish with a dad-joke about why maps are better when they fit in a lunchbox and an optional 'certificate of flatness' to hand out at the end of your presentation."
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
