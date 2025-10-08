<?php
// Run from CLI: php scripts/drop_db.php
require __DIR__ . '/../vendor/autoload.php';

use App\Config\Config; // assuming Config defines DB_NAME, DB_USER, DB_PASS, DB_HOST

if (php_sapi_name() !== 'cli') {
	echo "Please run from CLI: php scripts/drop_db.php\n";
	exit;
}

try {
	$dsn = "mysql:host=" . Config::DB_HOST . ";charset=utf8mb4";
	$pdo = new PDO($dsn, Config::DB_USER, Config::DB_PASS, [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
	]);

	$pdo->exec("DROP DATABASE IF EXISTS `" . Config::DB_NAME . "`;");
	echo "Database '" . Config::DB_NAME . "' has been dropped.\n";
} catch (PDOException $e) {
	die("Database connection failed: " . $e->getMessage() . "\n");
}
