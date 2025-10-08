<?php

use App\Config\Config;
use App\Models\Post;

?>

<h1 class="mb-4 text-center">Conference Program</h1>

<?php if ($posts): ?>
	<div class="accordion" id="programAccordion">
		<?php foreach ($posts as $index => $post): ?>
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading<?= $post->id ?>">
					<button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>"
						type="button" data-bs-toggle="collapse"
						data-bs-target="#collapse<?= $post->id ?>"
						aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>">
						<span class="text-muted"><?= htmlspecialchars($post->author ?? 'Anonymous') ?>:&nbsp;</span>
						<?= htmlspecialchars($post->title) ?>

						<?php
						$rating = $post->rating() ?? 1;
						$fullStars = $rating;
						$emptyStars = 5 - $fullStars;
						?>

						<span class="ms-auto">
							<?php for ($i = 0; $i < $fullStars; $i++): ?>
								⭐
							<?php endfor; ?>
							<?php for ($i = 0; $i < $emptyStars; $i++): ?>
								☆
							<?php endfor; ?>
						</span>
					</button>
				</h2>
				<div id="collapse<?= $post->id ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>">
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
