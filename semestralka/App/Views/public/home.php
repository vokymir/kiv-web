<?php

use App\Config\Config;

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
						<p class="text-muted"><?= htmlspecialchars($speaker['name']) ?></p>
						<h5 class="fw-bold"><?= htmlspecialchars($speaker['title']) ?></h5>
						<div class="text-warning fs-5">
							<?php
							$filled = (int)floor($speaker['rating']);
							$empty = 5 - $filled;
							echo str_repeat('★', $filled);
							echo str_repeat('☆', $empty);
							?>
						</div>
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
