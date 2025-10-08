<?php

use App\Config\Config;
use App\Models\Status;
use App\Models\User;
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
	<h1>Manage Posts</h1>
	<div class="btn-group mt-2">
		<a href="?status=all" class="btn btn-outline-primary <?= ($filter ?? '') === 'all' ? 'active' : '' ?>">All</a>
		<a href="?status=published" class="btn btn-outline-success <?= ($filter ?? '') === 'published' ? 'active' : '' ?>">Published</a>
		<a href="?status=in_review" class="btn btn-outline-warning <?= ($filter ?? '') === 'in_review' ? 'active' : '' ?>">In Review</a>
		<a href="?status=rejected" class="btn btn-outline-danger <?= ($filter ?? '') === 'rejected' ? 'active' : '' ?>">Rejected</a>
	</div>
</div>

<?php if ($posts): ?>
	<div class="accordion" id="postsAccordion">
		<?php foreach ($posts as $index => $post): ?>
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading<?= $post->id ?>">
					<button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>"
						type="button" data-bs-toggle="collapse"
						data-bs-target="#collapse<?= $post->id ?>"
						aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
						aria-controls="collapse<?= $post->id ?>">
						<?= htmlspecialchars($post->title) ?>
						<span class="badge ms-2 <?= match ($post->status) {
										Status::Accepted => 'bg-success',
										Status::PendingReview => 'bg-warning',
										Status::Rejected => 'bg-danger',
										default => 'bg-secondary',
									} ?>">
							<?= $post->getStatusName() ?>
						</span>
					</button>
				</h2>

				<div id="collapse<?= $post->id ?>"
					class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
					aria-labelledby="heading<?= $post->id ?>"
					data-bs-parent="#postsAccordion">
					<div class="accordion-body">
						<p><?= nl2br(htmlspecialchars($post->abstract)) ?></p>

						<?php if (!empty($post->pathPDF)): ?>
							<a href="<?= Config::BASE_URL ?>download/pdf/<?= $post->pathPDF ?>" target="_blank" class="btn btn-primary mb-3">Download PDF</a>
						<?php endif; ?>

						<!-- Assign reviewers -->
						<form method="post" action="<?= Config::BASE_URL ?>posts/<?= $post->id ?>/update" class="mb-3">
							<div class="row g-2 mb-3">
								<?php
								$assignedIds = $post->getAssignedReviewerIds();
								$unassignedReviewers = array_filter($reviewers, fn($r) => !in_array($r->id, $assignedIds));
								$assignedReviewers = array_filter($reviewers, fn($r) => in_array($r->id, $assignedIds));
								?>
								<div class="col-md-6">
									<label class="form-label">Assign Reviewers</label>
									<select name="reviewers[]" class="form-select" multiple>
										<?php foreach ($unassignedReviewers as $rev): ?>
											<option value="<?= $rev->id ?>"><?= htmlspecialchars($rev->name) ?></option>
										<?php endforeach; ?>
									</select>
									<button type="submit" name="action" value="assign" class="btn btn-sm btn-primary mt-2">Assign Selected</button>
								</div>

								<div class="col-md-6">
									<label class="form-label">Currently Assigned</label>
									<select name="remove_reviewers[]" class="form-select" multiple>
										<?php foreach ($assignedReviewers as $rev): ?>
											<option value="<?= $rev->id ?>"><?= htmlspecialchars($rev->name) ?></option>
										<?php endforeach; ?>
									</select>
									<button type="submit" name="action" value="unassign" class="btn btn-sm btn-danger mt-2">Unassign Selected</button>
								</div>
							</div>

							<!-- Publish/Reject/Delete buttons at the very end -->
							<div class="d-flex gap-2 mt-3 flex-wrap">
								<button type="submit" name="action" value="publish" class="btn btn-success">Publish</button>
								<button type="submit" name="action" value="reject" class="btn btn-warning">Reject</button>
								<a href="<?= Config::BASE_URL ?>posts/<?= $post->id ?>/delete" class="btn btn-danger"
									onclick="return confirm('Delete this post?')">Delete</a>
							</div>
						</form>
						<!-- Reviews -->
						<h5>Reviews</h5>
						<?php $reviews = $post->getReviews(); ?>
						<?php if ($reviews): ?>
							<?php foreach ($reviews as $rev): ?>
								<div class="border rounded p-2 mb-2 bg-light">
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
										<?php if (!empty(trim($rev['note']))): ?>
											<div class="border rounded p-2 mt-1 bg-white">
												<?= $rev['note'] ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						<?php else: ?>
							<p class="text-muted">No reviews yet.</p>
						<?php endif; ?>

						<!-- Publish / Reject / Delete buttons at the very end -->
						<div class="mt-3 d-flex gap-2 flex-wrap">
							<form method="post" action="<?= Config::BASE_URL ?>posts/<?= $post->id ?>/update">
								<button type="submit" name="action" value="publish" class="btn btn-success btn-sm">Publish</button>
							</form>
							<form method="post" action="<?= Config::BASE_URL ?>posts/<?= $post->id ?>/update">
								<button type="submit" name="action" value="reject" class="btn btn-warning btn-sm">Reject</button>
							</form>
							<a href="<?= Config::BASE_URL ?>posts/<?= $post->id ?>/delete" class="btn btn-danger btn-sm" onclick="return confirm('Delete this post?')">Delete</a>
						</div>

					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php else: ?>
	<p class="text-center text-muted">No posts available.</p>
<?php endif; ?>
