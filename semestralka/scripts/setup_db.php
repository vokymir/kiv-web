<?php
// Run this from CLI: php scripts/setup_db.php
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

if (php_sapi_name() !== 'cli') {
	echo "Please run from CLI: php scripts/setup_db.php\n";
	exit;
}
// make sure you have pdo_mysql and mysqli extensions uncommented in php.ini file

$db = new Database();

$db->query("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role INT NOT NULL,
    blocked INT DEFAULT 0,
    username VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) DEFAULT NULL,
    passwordHash VARCHAR(255) NOT NULL
);")->execute();

$db->query("
CREATE TRIGGER IF NOT EXISTS set_default_name BEFORE INSERT ON users
FOR EACH ROW
BEGIN
    IF NEW.name IS NULL THEN
        SET NEW.name = NEW.username;
    END IF;
END;
;")->execute();

$db->query("
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT,
    title VARCHAR(255),
    abstract TEXT,
    pathPDF VARCHAR(255),
    status INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(userId) REFERENCES users(id) ON DELETE SET NULL
);")->execute();

$db->query("
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    postId INT,
    userId INT,
    ratingInteresting INT,
    ratingImportant INT,
    ratingInovative INT,
    ratingNote TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(userId) REFERENCES users(id),
    FOREIGN KEY(postId) REFERENCES posts(id)
);")->execute();

echo "Tables created.\n";

function h($p)
{
	return password_hash($p, PASSWORD_BCRYPT);
}

$users = [
	['super', 'superpass', 'Super Admin', 50],
	['admin', 'adminpass', 'Admin', 30],
	['author', 'authorpass', 'První autor', 10],
	['reviewer', 'reviewerpass', 'Klidný hodnotitel', 20]
];

foreach ($users as $u) {
	$db->query('INSERT IGNORE INTO users (username, passwordHash, name, role) VALUES (:username, :passwordHash, :name, :role)')
		->bind(':username', $u[0])
		->bind(':passwordHash', h($u[1]))
		->bind(':name', $u[2])
		->bind(':role', $u[3])
		->execute();
}

echo "Default users inserted.\n";
echo "Done.\n";
