<?php

use App\Config\Config;

?>

<h1 class="mb-4 text-center">Conference Program</h1>

<?php if ($posts): ?>
	<div class="accordion" id="programAccordion">
		<?php foreach ($posts as $index => $post): ?>
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading<?= $post->id ?>">
					<button class="accordion-button collapsed"
						type="button" data-bs-toggle="collapse"
						data-bs-target="#collapse<?= $post->id ?>"
						aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>">

						<div class="w-100 d-flex align-items-center me-2">
							<span class="text-muted"><?= htmlspecialchars($post->author ?? 'Anonymous') ?>:&nbsp;</span>
							<span><?= htmlspecialchars($post->title) ?></span>

							<?php
							$rating = $post->rating() ?? 1;
							$fullStars = $rating;
							$emptyStars = 5 - $fullStars;
							?>
							<span class="ms-auto d-flex align-items-center">
								<?php for ($i = 0; $i < $fullStars; $i++): ?>
									<span class="text-warning">⭐</span>
								<?php endfor; ?>
								<?php for ($i = 0; $i < $emptyStars; $i++): ?>
									<span class="text-secondary">☆</span>
								<?php endfor; ?>
							</span>
						</div>
					</button>
				</h2>
				<div id="collapse<?= $post->id ?>" class="accordion-collapse collapse">
					<div class="accordion-body">
						<p><?= nl2br(htmlspecialchars($post->abstract)) ?></p>
						<?php if (!empty($post->pathPDF)): ?>
							<a href="<?= Config::BASE_URL ?>download/pdf/<?= $post->pathPDF ?>"
								target="_blank" class="btn btn-primary">Download PDF</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php else: ?>
	<p class="text-center text-muted">No accepted posts yet. Check back later!</p>
<?php endif; ?>
