<?php

use App\Config\Config;
use App\Core\Database;

// Fetch accepted posts with author info
$db = new Database();
$posts = $db->query("
    SELECT p.id, p.title, p.abstract, p.pathPDF, u.username AS author
    FROM posts p
    LEFT JOIN users u ON p.userId = u.id
    WHERE p.status = 20
    ORDER BY p.created_at DESC
")->fetchAll();
?>

<h1 class="mb-4 text-center">Conference Program</h1>

<?php if ($posts): ?>
	<div class="accordion" id="programAccordion">
		<?php foreach ($posts as $index => $post): ?>
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading<?= $post['id'] ?>">
					<button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $post['id'] ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $post['id'] ?>">
						<?= htmlspecialchars($post['title']) ?> - <span class="text-muted"><?= htmlspecialchars($post['author'] ?? 'Unknown') ?></span>
					</button>
				</h2>
				<div id="collapse<?= $post['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $post['id'] ?>" data-bs-parent="#programAccordion">
					<div class="accordion-body">
						<p><?= nl2br(htmlspecialchars($post['abstract'])) ?></p>
						<?php if (!empty($post['pathPDF'])): ?>
							<a href="<?= Config::BASE_URL . htmlspecialchars($post['pathPDF']) ?>" target="_blank" class="btn btn-primary">Download PDF</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php else: ?>
	<p class="text-center text-muted">No accepted papers yet. Check back later!</p>
<?php endif; ?>
