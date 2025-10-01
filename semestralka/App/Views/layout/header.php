<?php

namespace App\Views;

?>

<!doctype html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Conference MVC</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
	<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
		<div class="container">
			<a class="navbar-brand" href="/">Conference</a>
			<div class="collapse navbar-collapse">
				<ul class="navbar-nav me-auto">
					<li class="nav-item"><a class="nav-link" href="/posts">Papers</a></li>
					<li class="nav-item"><a class="nav-link" href="/posts/upload">Upload</a></li>
					<li class="nav-item"><a class="nav-link" href="/reviews">Reviews</a></li>
				</ul>
				<ul class="navbar-nav">
					<!-- <?php if (\Core\Auth::check()): ?> -->
					<!-- <li class="nav-item"><span class="nav-link">Hello, <?= htmlspecialchars(\Core\Auth::user()['username']) ?></span></li> -->
					<li class="nav-item"><a class="nav-link" href="/logout">Logout</a></li>
					<!-- <?php else: ?> -->
					<li class="nav-item"><a class="nav-link" href="/login">Login</a></li>
					<li class="nav-item"><a class="nav-link" href="/register">Register</a></li>
					<!-- <?php endif; ?> -->
				</ul>
			</div>
		</div>
	</nav>
	<div class="container">
