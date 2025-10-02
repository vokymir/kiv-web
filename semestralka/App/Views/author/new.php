<?php

use App\Config\Config;
?>

<div class="row justify-content-center">
	<div class="col-md-8">
		<h1 class="mb-4">Create New Post</h1>

		<?php if (!empty($error)): ?>
			<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
		<?php endif; ?>

		<form action="<?= Config::BASE_URL ?>posts/store" method="post" enctype="multipart/form-data">
			<!-- Title -->
			<div class="mb-3">
				<label for="title" class="form-label">Title</label>
				<input type="text" name="title" id="title" class="form-control" required>
			</div>

			<!-- Abstract -->
			<div class="mb-3">
				<label for="abstract" class="form-label">Abstract</label>
				<textarea name="abstract" id="abstract" rows="5" class="form-control" required></textarea>
			</div>

			<!-- PDF Upload -->
			<div class="mb-3">
				<label for="pdf" class="form-label">Upload PDF</label>
				<input type="file" name="pdf" id="pdf" accept="application/pdf" class="form-control" required>
			</div>

			<!-- Status radio buttons -->
			<div class="mb-3">
				<label class="form-label">Save as:</label>
				<div class="form-check">
					<input class="form-check-input" type="radio" name="status" id="draft" value="<?= \App\Models\Status::WorkingOn->value ?>" checked>
					<label class="form-check-label" for="draft">Draft (Working On)</label>
				</div>
				<div class="form-check">
					<input class="form-check-input" type="radio" name="status" id="review" value="<?= \App\Models\Status::PendingReview->value ?>">
					<label class="form-check-label" for="review">Send to Review</label>
				</div>
			</div>

			<button type="submit" class="btn btn-success">Submit</button>
			<a href="<?= Config::BASE_URL ?>posts" class="btn btn-secondary">Cancel</a>
		</form>
	</div>
</div>
