<?php

use App\Config\Config;
use App\Core\Database;
use App\Models\Status;

$db = new Database();

// Pull random 3 authors of accepted posts
$speakers = $db->query("
    SELECT u.id, u.username 
    FROM posts p
    JOIN users u ON p.userId = u.id
    WHERE p.status = :accepted
    GROUP BY u.id, u.username
    ORDER BY RAND()
    LIMIT 3
")
	->bind(':accepted', Status::Accepted->value)
	->fetchAll();

$isLoggedIn = !empty($_SESSION['user']);
?>

<!-- Hero Section -->
<section class="bg-dark text-light text-center py-5">
	<div class="container">
		<h1 class="display-4 fw-bold"><?= htmlspecialchars($sitename) ?></h1>
		<p class="lead mb-4">Join us for an inspiring event filled with knowledge, networking, and new ideas.</p>

		<?php if ($isLoggedIn): ?>
			<button id="shareBtn" class="btn btn-success btn-lg px-4">Invite friends</button>
		<?php else: ?>
			<a href="<?= Config::BASE_URL ?>register" class="btn btn-primary btn-lg px-4">Register Now</a>
		<?php endif; ?>
		<a href="<?= Config::BASE_URL ?>program" class="btn btn-outline-light btn-lg px-4">View Program</a>
	</div>
</section>

<!-- Keynote Speakers -->
<section class="bg-light py-5">
	<div class="container">
		<h2 class="text-center fw-bold mb-5">Keynote Speakers</h2>
		<div class="row g-4">
			<?php if ($speakers): ?>
				<?php foreach ($speakers as $speaker): ?>
					<div class="col-md-4 text-center">
						<img src="<?= Config::BASE_URL ?>images/avatar-placeholder.png"
							alt="<?= htmlspecialchars($speaker['username']) ?>"
							class="rounded-circle mb-3"
							width="150" height="150">
						<h5 class="fw-bold"><?= htmlspecialchars($speaker['username']) ?></h5>
						<p class="text-muted">Author of accepted paper</p>
					</div>
				<?php endforeach; ?>
			<?php else: ?>
				<p class="text-center text-muted">Speakers will be announced soon.</p>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php if (!$isLoggedIn): ?>
	<!-- Call to Action (only for logged out) -->
	<section class="py-5 text-center bg-primary text-light">
		<div class="container">
			<h2 class="fw-bold mb-3">Don’t Miss Out!</h2>
			<p class="mb-4">Reserve your spot today and be part of an unforgettable event.</p>
			<a href="<?= Config::BASE_URL ?>register" class="btn btn-light btn-lg px-4">Register Now</a>
		</div>
	</section>
<?php else: ?>
	<script>
		document.getElementById('shareBtn').addEventListener('click', async () => {
			if (navigator.share) {
				try {
					await navigator.share({
						title: document.title,
						url: window.location.href
					});
				} catch (err) {
					console.error('Error sharing:', err);
				}
			} else {
				alert('Sharing not supported in this browser. Copy the URL manually.');
			}
		});
	</script>
<?php endif; ?>
