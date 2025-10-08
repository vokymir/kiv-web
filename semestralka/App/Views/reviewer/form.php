<?php

use App\Config\Config;
?>

<div class="container">
	<h1><?= $isEdit ? 'Edit Review' : 'Review This Post' ?></h1>
	<hr>

	<div class="mb-4">
		<h3><?= htmlspecialchars($post->title) ?></h3>
		<p><?= nl2br(htmlspecialchars($post->abstract)) ?></p>
		<?php if (!empty($post->pathPDF)): ?>
			<a href="<?= Config::BASE_URL ?>download/pdf/<?= $post->pathPDF ?>" target="_blank" class="btn btn-sm btn-primary">
				Download PDF
			</a>
		<?php endif; ?>
	</div>

	<form method="POST" action="<?= Config::BASE_URL ?><?= $isEdit ? "reviews/update" : "reviews/store" ?>">
		<input type="hidden" name="postId" value="<?= htmlspecialchars($post->id) ?>">
		<?php if ($isEdit): ?>
			<input type="hidden" name="reviewId" value="<?= htmlspecialchars($review->id) ?>">
		<?php endif; ?>

		<div class="row mb-3">
			<div class="col-md-4">
				<label class="form-label">Interesting (1–5)</label>
				<input type="number" name="ratingInteresting" class="form-control" min="1" max="5"
					value="<?= htmlspecialchars($review->ratingInteresting ?? '') ?>" required>
			</div>
			<div class="col-md-4">
				<label class="form-label">Important (1–5)</label>
				<input type="number" name="ratingImportant" class="form-control" min="1" max="5"
					value="<?= htmlspecialchars($review->ratingImportant ?? '') ?>" required>
			</div>
			<div class="col-md-4">
				<label class="form-label">Innovative (1–5)</label>
				<input type="number" name="ratingInovative" class="form-control" min="1" max="5"
					value="<?= htmlspecialchars($review->ratingInovative ?? '') ?>" required>
			</div>
		</div>

		<div class="mb-3">
			<label class="form-label">Notes</label>
			<div id="editor" style="height: 200px;"><?= $review->ratingNote ?? '' ?></div>
			<textarea name="ratingNote" id="ratingNote" hidden></textarea>
		</div>

		<div class="d-flex gap-2">
			<button type="submit" class="btn btn-<?= $isEdit ? 'warning' : 'success' ?>">
				<?= $isEdit ? 'Update Review' : 'Submit Review' ?>
			</button>
			<a href="<?= Config::BASE_URL ?>reviews" class="btn btn-secondary">Cancel</a>
		</div>
	</form>
</div>

<!-- Quill WYSIWYG -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
	const quill = new Quill('#editor', {
		theme: 'snow'
	});
	const form = document.querySelector('form');
	form.addEventListener('submit', () => {
		document.getElementById('ratingNote').value = quill.root.innerHTML;
	});
</script>
