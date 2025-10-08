<?php

use App\Config\Config;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
	<h1>Your Posts</h1>
	<a href="<?= Config::BASE_URL ?>posts/new" class="btn btn-success">Create New Post</a>
</div>

<?php if ($posts): ?>
	<div class="accordion" id="userPostsAccordion">
		<?php foreach ($posts as $index => $post): ?>
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading<?= $post->id ?>">
					<button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>"
						type="button" data-bs-toggle="collapse"
						data-bs-target="#collapse<?= $post->id ?>"
						aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
						aria-controls="collapse<?= $post->id ?>">
						<?= htmlspecialchars($post->title) ?>
						<span class="text-muted ms-2">(Status: <?= $post->getStatusName() ?>)</span>
					</button>
				</h2>
				<div id="collapse<?= $post->id ?>"
					class="accordion-collapse collapse"
					aria-labelledby="heading<?= $post->id ?>"
					data-bs-parent="#userPostsAccordion">
					<div class="accordion-body">
						<p><?= nl2br(htmlspecialchars($post->abstract)) ?></p>

						<?php
						$reviews = $post->getReviews();
						if ($reviews): ?>
							<hr>
							<h5>Reviews</h5>
							<?php foreach ($reviews as $rev): ?>
								<div class="mb-3">
									<strong><?= htmlspecialchars($rev['reviewerName']) ?>:</strong>
									<div class="ms-2">
										<div>Interesting:
											<span><?= str_repeat('⭐', $rev['ratingInteresting']) . str_repeat('☆', 5 - $rev['ratingInteresting']) ?></span>
										</div>
										<div>Important:
											<span><?= str_repeat('⭐', $rev['ratingImportant']) . str_repeat('☆', 5 - $rev['ratingImportant']) ?></span>
										</div>
										<div>Innovative:
											<span><?= str_repeat('⭐', $rev['ratingInovative']) . str_repeat('☆', 5 - $rev['ratingInovative']) ?></span>
										</div>
									</div>

									<?php if (!empty(trim($rev['note']))): ?>
										<div class="border rounded p-2 mt-2 bg-light">
											<?= $rev['note'] ?>
										</div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						<?php else: ?>
							<p class="text-muted">No reviews yet.</p>
						<?php endif; ?>

						<?php if (!empty($post->pathPDF)): ?>
							<a href="<?= Config::BASE_URL ?>download/pdf/<?= $post->pathPDF ?>" target="_blank" class="btn btn-primary">Download PDF</a>
						<?php endif; ?>

						<?php if ($post->canEdit()): ?>
							<a href="<?= Config::BASE_URL ?>posts/<?= $post->id ?>/edit" class="btn btn-warning">Edit</a>
						<?php endif; ?>

						<?php if ($post->canEdit()): ?>
							<a href="<?= Config::BASE_URL ?>posts/delete/<?= $post->id ?>" class="btn btn-danger">Delete</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php else: ?>
	<p class="text-center text-muted">You have not created any posts yet.</p>
<?php endif; ?>
