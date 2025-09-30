<?php
require_once "db.php";

$pdo = $pdo;

// Helper to get role_id
function getRoleId(PDO $pdo, string $roleName)
{
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->execute([$roleName]);
    return $stmt->fetchColumn();
}

// Default users
$users = [
    ['super', 'super@email.com', 'password', 'superadmin'],
    ['admin1', 'admin1@email.com', 'password', 'admin'],
    ['author1', 'author1@email.com', 'password', 'author'],
    ['reviewer1', 'reviewer1@email.com', 'password', 'reviewer'],
];

// Insert users
$stmt = $pdo->prepare("INSERT IGNORE INTO users (username,email,password_hash,role_id) VALUES (?,?,?,?)");

foreach ($users as $u) {
    [$username, $email, $password, $roleName] = $u;
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $role_id = getRoleId($pdo, $roleName);
    $stmt->execute([$username, $email, $hash, $role_id]);
}

echo "Default users inserted successfully.\n";
