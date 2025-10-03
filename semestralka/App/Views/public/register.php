<?php

use App\Config\Config;

?>

<div class="row justify-content-center">
	<div class="col-md-6 col-lg-4">
		<div class="card shadow-sm">
			<div class="card-body">
				<h3 class="card-title text-center mb-4">Register</h3>

				<?php if (!empty($error)): ?>
					<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
				<?php endif; ?>

				<form method="post" action="">
					<div class="mb-3">
						<label for="username" class="form-label">Username</label>
						<input type="text" class="form-control" id="username" name="username" required>
					</div>

					<div class="mb-3">
						<label for="name" class="form-label">Public name</label>
						<input type="text" class="form-control" id="name" name="name" required>
					</div>

					<div class="mb-3 position-relative">
						<label for="password" class="form-label">Password</label>
						<input type="password" class="form-control" id="password" name="password" required>
						<button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 me-2"
							onclick="togglePassword('password', this)">Show</button>
					</div>

					<div class="mb-3 position-relative">
						<label for="confirm_password" class="form-label">Confirm Password</label>
						<input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
						<button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 me-2"
							onclick="togglePassword('confirm_password', this)">Show</button>
					</div>

					<div class="d-grid mb-3">
						<button type="submit" class="btn btn-success">Register</button>
					</div>

					<div class="text-center">
						<a href="<?= Config::BASE_URL ?>login">Already have an account? Login</a>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script>
	function togglePassword(inputId, btn) {
		const input = document.getElementById(inputId);
		if (input.type === "password") {
			input.type = "text";
			btn.textContent = "Hide";
		} else {
			input.type = "password";
			btn.textContent = "Show";
		}
	}
</script>
