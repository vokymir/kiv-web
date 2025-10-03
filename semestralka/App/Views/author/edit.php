<?php

use App\Config\Config;
use App\Models\Post;

?>
<div class="row justify-content-center">
	<div class="col-lg-8 col-md-10">
		<h1 class="mb-4">Edit Post</h1>

		<?php if (!empty($error)): ?>
			<div class="alert alert-danger">
				<?= htmlspecialchars($error) ?>
			</div>
		<?php endif; ?>

		<form action="<?= Config::BASE_URL ?>posts/update/<?= $post->id ?>" method="post" enctype="multipart/form-data">
			<div class="mb-3">
				<label for="title" class="form-label">Title <span class="text-danger">*</span></label>
				<input type="text" class="form-control" id="title" name="title" required
					value="<?= htmlspecialchars($post->title ?? '') ?>">
			</div>

			<div class="mb-3">
				<label for="abstract" class="form-label">Abstract <span class="text-danger">*</span></label>
				<textarea class="form-control" id="abstract" name="abstract" rows="5" required><?= htmlspecialchars($post->abstract ?? '') ?></textarea>
			</div>

			<div class="mb-3">
				<label for="pdf" class="form-label">PDF File</label>
				<?php if (!empty($post->pathPDF)): ?>
					<div class="mb-2">
						Current PDF:
						<a href="<?= Config::BASE_URL . 'download/pdf/' . htmlspecialchars($post->pathPDF) ?>" target="_blank">
							<?= htmlspecialchars($post->pathPDF) ?>
						</a>
					</div>
				<?php endif; ?>
				<input type="file" class="form-control" id="pdf" name="pdf" accept="application/pdf">
				<div class="form-text">Leave empty to keep the current PDF.</div>
			</div>

			<div class="d-flex justify-content-between">
				<a href="<?= Config::BASE_URL ?>posts" class="btn btn-secondary">Cancel</a>
				<button type="submit" class="btn btn-primary">Update Post</button>
			</div>
		</form>
	</div>
</div>
