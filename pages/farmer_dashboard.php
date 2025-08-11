<?php
require '../api/auth.php';
requireRole('farmer');
require '../config/db.php';

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM livestock WHERE owner_id = ?");
$stmt->execute([$userId]);
$livestock = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Farmer Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<h1>Welcome Farmer</h1>
<a href="livestock_add.php" class="btn btn-success">Register New Livestock</a>
<table class="table mt-3">
    <thead><tr><th>ID</th><th>Species</th><th>Birth Date</th></tr></thead>
    <tbody>
        <?php foreach ($livestock as $animal): ?>
        <tr>
            <td><?= htmlspecialchars($animal['id']) ?></td>
            <td><?= htmlspecialchars($animal['species']) ?></td>
            <td><?= htmlspecialchars($animal['birth_date']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
