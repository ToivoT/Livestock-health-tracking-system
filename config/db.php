<?php
$host = 'localhost';
$dbname = 'livestock_db';
$username = 'root';  // Change for production
$password = 'Junior21038@';      // Change for production

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>