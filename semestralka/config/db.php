<?php

$host = 'localhost';
$dbname = 'konference';
$user = 'root';
$pass = '';

try {
    // Connect to MySQL (without specifying DB yet)
    $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $dbname");

    // Create tables
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL
    );

    CREATE TABLE IF NOT EXISTS status (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL
    );

    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role_id INT NOT NULL,
        active TINYINT(1) DEFAULT 1,
        FOREIGN KEY(role_id) REFERENCES roles(id)
    );

    CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        abstract TEXT,
        pdf_path VARCHAR(255),
        status_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(status_id) REFERENCES status(id)
    );

    CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comment TEXT,
        score_content INT,
        score_originality INT,
        score_format INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS user_submission (
        user_id INT NOT NULL,
        submission_id INT NOT NULL,
        PRIMARY KEY(user_id, submission_id),
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(submission_id) REFERENCES submissions(id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS submission_reviewer (
        submission_id INT NOT NULL,
        reviewer_id INT NOT NULL,
        PRIMARY KEY(submission_id, reviewer_id),
        FOREIGN KEY(submission_id) REFERENCES submissions(id) ON DELETE CASCADE,
        FOREIGN KEY(reviewer_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ");

    // Insert default roles if not exist
    $roles = ['author', 'reviewer', 'admin', 'superadmin'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO roles (name) VALUES (?)");
    foreach ($roles as $role) {
        $stmt->execute([$role]);
    }

    // Insert default statuses if not exist
    $statuses = ['pending', 'accepted', 'rejected'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO status (name) VALUES (?)");
    foreach ($statuses as $status) {
        $stmt->execute([$status]);
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
