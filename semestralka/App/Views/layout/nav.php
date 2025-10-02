<?php

use App\Config\Config;
use App\Core\Auth;
use App\Models\Role;
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
	<div class="container">
		<a class="navbar-brand fw-bold" href="<?= Config::BASE_URL ?>">
			<?= htmlspecialchars($sitename) ?>
		</a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
			aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="navbarNav">
			<ul class="navbar-nav ms-auto">
				<li class="nav-item">
					<a class="nav-link" href="<?= Config::BASE_URL ?>program">Program</a>
				</li>

				<?php if (!empty($_SESSION['user']) && Auth::isRole(Role::Author)): ?>
					<li class="nav-item">
						<a class="nav-link" href="<?= Config::BASE_URL ?>posts">My posts</a>
					</li>
				<?php endif; ?>

				<?php if (!empty($_SESSION['user']) && Auth::isRole(Role::Reviewer)): ?>
					<li class="nav-item">
						<a class="nav-link" href="<?= Config::BASE_URL ?>reviews">Reviews</a>
					</li>
				<?php endif; ?>

				<?php if (!empty($_SESSION['user']) && (Auth::isRole(Role::Admin) || Auth::isRole(Role::Superadmin))): ?>
					<li class="nav-item">
						<a class="nav-link" href="<?= Config::BASE_URL ?>users">Users</a>
					</li>
				<?php endif; ?>

				<li class="nav-item">
					<?php if (!empty($_SESSION['user'])): ?>
						<a class="nav-link text-warning" href="<?= Config::BASE_URL ?>logout">Logout</a>
					<?php else: ?>
						<a class="nav-link text-success" href="<?= Config::BASE_URL ?>login">Login</a>
					<?php endif; ?>
				</li>
			</ul>
		</div>
	</div>
</nav>
