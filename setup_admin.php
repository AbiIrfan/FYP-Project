<?php 
require_once 'config/db.php';

$username = 'admin';
$password = 'password123';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = 'owner';

$stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
$stmt->execute([$username, $hashedPassword, $role]);

echo "Admin user created successfully! You can now log in with username: 'admin' and password: 'password123'. Delete this file after testing.";
?>