<?php

use App\Core\Flash;

$flash = Flash::get();
if ($flash):
	$type = $flash['type'];
	$message = htmlspecialchars($flash['message']);
	$alertClass = match ($type) {
		'success' => 'alert-success',
		'error' => 'alert-danger',
		'warning' => 'alert-warning',
		default => 'alert-info',
	};
?>
	<div class="container mt-3">
		<div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
			<?= $message ?>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
	</div>
	<script>
		setTimeout(() => {
			const alert = document.querySelector('.alert');
			if (alert) {
				const bsAlert = new bootstrap.Alert(alert);
				bsAlert.close();
			}
		}, 4000);
	</script>
<?php endif; ?>
