<?php

use App\Config\Config;
use App\Core\Database;
use App\Models\Status;

// Current user ID
$userId = $_SESSION['user']['id'];

// Fetch current user's posts
$db = new Database();
$posts = $db->query("
    SELECT p.id, p.title, p.abstract, p.pathPDF, p.status, u.username AS author
    FROM posts p
    LEFT JOIN users u ON p.userId = u.id
    WHERE p.userId = :userId
    ORDER BY p.created_at DESC
")->bind(':userId', $userId)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
	<h1>Your Papers</h1>
	<a href="<?= Config::BASE_URL ?>posts/new" class="btn btn-success">Create New Post</a>
</div>

<?php if ($posts): ?>
	<div class="accordion" id="userPostsAccordion">
		<?php foreach ($posts as $index => $post): ?>
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading<?= $post['id'] ?>">
					<button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $post['id'] ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $post['id'] ?>">
						<?= htmlspecialchars($post['title']) ?>
						<span class="text-muted ms-2">(Status:
							<?php
							switch ($post['status']) {
								case Status::WorkingOn->value:
									echo 'Working On';
									break;
								case Status::PendingReview->value:
									echo 'Pending Review';
									break;
								case Status::Accepted->value:
									echo 'Accepted';
									break;
								case Status::Rejected->value:
									echo 'Rejected';
									break;
							}
							?>
							)</span>
					</button>
				</h2>
				<div id="collapse<?= $post['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $post['id'] ?>" data-bs-parent="#userPostsAccordion">
					<div class="accordion-body">
						<p><?= nl2br(htmlspecialchars($post['abstract'])) ?></p>
						<?php if (!empty($post['pathPDF'])): ?>
							<a href="<?= Config::BASE_URL . htmlspecialchars($post['pathPDF']) ?>" target="_blank" class="btn btn-primary me-2">Download PDF</a>
						<?php endif; ?>

						<?php if ($post->canEdit()): ?>
							<a href="<?= Config::BASE_URL ?>posts/<?= $post->id ?>/edit" class="btn btn-warning">Edit</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php else: ?>
	<p class="text-center text-muted">You have not created any posts yet.</p>
<?php endif; ?>
