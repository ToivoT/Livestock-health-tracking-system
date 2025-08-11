<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
}
require '../config/db.php';

// Fetch data based on role
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM livestock WHERE owner_id = ?");
$stmt->execute([$userId]);
$livestock = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard - LHVTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo $_SESSION['role']; ?></h1>
        <canvas id="healthChart"></canvas>
    </div>
    <script>
        const ctx = document.getElementById('healthChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: { /* Populate from API */ }
        });
    </script>
</body>
</html>