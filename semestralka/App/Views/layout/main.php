<?php

use App\Config\Config;
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= htmlspecialchars($title ?: $defaultTitle) ?> - <?= htmlspecialchars($sitename) ?></title>

	<!-- Bootstrap CSS -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

	<!-- Custom CSS -->
	<link rel="stylesheet" href="<?= Config::BASE_URL ?>css/style.css">
</head>

<body>
	<?php $this->renderPartial("nav") ?>

	<main class="container my-4">
		<?= $content ?>
	</main>

	<?php $this->renderPartial("footer") ?>

	<!-- Bootstrap JS -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
