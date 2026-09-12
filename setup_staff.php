<?php 
require_once 'config/db.php';

$username = 'staff1';
$password = 'staff123';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$role = 'staff';

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
    $stmt->execute([$username, $hashedPassword, $role]);
    echo "Staff user created successfully! You can log in with username: 'staff1' and password: 'staff123'. Delete this file after testing.";
} catch (PDOException $e) {
    echo "Error creating user: " . $e->getMessage();
}
?>