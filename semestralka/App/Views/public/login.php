<?php

use App\Config\Config;

?>

<div class="row justify-content-center">
	<div class="col-md-6 col-lg-4">
		<div class="card shadow-sm">
			<div class="card-body">
				<h3 class="card-title text-center mb-4">Login</h3>

				<form method="post" action="">
					<div class="mb-3">
						<label for="username" class="form-label">Username</label>
						<input type="text" class="form-control" id="username" name="username" required>
					</div>

					<div class="mb-3 position-relative">
						<label for="password" class="form-label">Password</label>
						<input type="password" class="form-control" id="password" name="password" required>
						<button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 me-2"
							onclick="togglePassword('password', this)">Show</button>
					</div>

					<div class="d-grid mb-3">
						<button type="submit" class="btn btn-primary">Login</button>
					</div>

					<div class="text-center">
						<a href="<?= Config::BASE_URL ?>register">Don't have an account? Register</a>
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
