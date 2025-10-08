<?php

use App\Config\Config;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
	<h1>Your Assigned Reviews</h1>
</div>

<?php if ($assignedPosts): ?>
	<div class="accordion" id="reviewerPostsAccordion">
		<?php foreach ($assignedPosts as $index => $post): ?>
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading<?= $post->id ?>">
					<button class="accordion-button collapsed"
						type="button" data-bs-toggle="collapse"
						data-bs-target="#collapse<?= $post->id ?>"
						aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
						aria-controls="collapse<?= $post->id ?>">
						<?= htmlspecialchars($post->title) ?>
						<span class="text-muted ms-2">(Author: <?= htmlspecialchars($post->author ?? 'Unknown') ?>)</span>
						<?php if (!empty($post->review)): ?>
							<span class="badge bg-success ms-2">Reviewed</span>
						<?php else: ?>
							<span class="badge bg-secondary ms-2">Not Reviewed</span>
						<?php endif; ?>

						<div class="ms-2 d-flex align-items-center me-2">

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
				<div id="collapse<?= $post->id ?>"
					class="accordion-collapse collapse "
					aria-labelledby="heading<?= $post->id ?>"
					data-bs-parent="#reviewerPostsAccordion">
					<div class="accordion-body">

						<!-- Abstract -->
						<p><strong>Abstract:</strong></p>
						<p><?= nl2br(htmlspecialchars($post->abstract)) ?></p>

						<!-- PDF -->
						<?php if (!empty($post->pathPDF)): ?>
							<a href="<?= Config::BASE_URL ?>download/pdf/<?= $post->pathPDF ?>"
								target="_blank"
								class="btn btn-sm btn-primary mb-3">
								Download PDF
							</a>
						<?php endif; ?>

						<hr>

						<!-- Review Section -->
						<?php if (!empty($post->review)): ?>
							<h5>Your Review</h5>
							<ul class="list-group mb-3">
								<li class="list-group-item d-flex justify-content-between align-items-center">
									Interesting
									<div class="text-warning fs-5">
										<?php
										$filled = (int)floor($post->review->ratingInteresting);
										$empty = 5 - $filled;
										echo str_repeat('★', $filled);
										echo str_repeat('☆', $empty);
										?>
									</div>
								</li>
								<li class="list-group-item d-flex justify-content-between align-items-center">
									Important
									<div class="text-warning fs-5">
										<?php
										$filled = (int)floor($post->review->ratingImportant);
										$empty = 5 - $filled;
										echo str_repeat('★', $filled);
										echo str_repeat('☆', $empty);
										?>
									</div>
								</li>
								<li class="list-group-item d-flex justify-content-between align-items-center">
									Innovative
									<div class="text-warning fs-5">
										<?php
										$filled = (int)floor($post->review->ratingInovative);
										$empty = 5 - $filled;
										echo str_repeat('★', $filled);
										echo str_repeat('☆', $empty);
										?>
									</div>
								</li>
								<?php if (!empty($post->review->ratingNote)): ?>
									<li class="list-group-item">
										<strong>Note:</strong><br>
										<div><?= $post->review ? $post->review->ratingNote : '' ?></div>
									</li>
								<?php endif; ?>
							</ul>

							<a href="<?= Config::BASE_URL ?>reviews/<?= $post->review->id ?>/edit"
								class="btn btn-warning">
								Edit Review
							</a>
						<?php else: ?>
							<p class="text-muted mb-3">You haven’t reviewed this post yet.</p>
							<a href="<?= Config::BASE_URL ?>reviews/<?= $post->id ?>/create"
								class="btn btn-success">
								Add Review
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php else: ?>
	<p class="text-center text-muted">No posts have been assigned to you yet.</p>
<?php endif; ?>
