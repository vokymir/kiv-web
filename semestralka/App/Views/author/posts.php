<?php

use App\Config\Config;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
	<h1>Your Papers</h1>
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
					class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
					aria-labelledby="heading<?= $post->id ?>"
					data-bs-parent="#userPostsAccordion">
					<div class="accordion-body">
						<p><?= nl2br(htmlspecialchars($post->abstract)) ?></p>
						<?php if (!empty($post->pathPDF)): ?>
							<a href="<?= Config::BASE_URL ?>download/pdf/<?= $post->pathPDF ?>" class="btn btn-primary">Download PDF</a>
						<?php endif; ?>

						<?php if ($post->canEdit()): ?>
							<a href="<?= Config::BASE_URL ?>posts/<?= $post->id ?>/edit" class="btn btn-warning">Edit</a>
						<?php endif; ?>

						<?php if ($post->canEdit()): ?>
							<a href="<?= Config::BASE_URL ?>posts/<?= $post->id ?>/delete" class="btn btn-danger">Delete</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php else: ?>
	<p class="text-center text-muted">You have not created any posts yet.</p>
<?php endif; ?>
