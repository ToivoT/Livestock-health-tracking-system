<?php
require '../api/auth.php';
requireRole('vet');
require '../config/db.php';

$stmt = $pdo->query("SELECT l.id, l.species, l.birth_date, u.username AS owner
                     FROM livestock l
                     JOIN users u ON l.owner_id = u.id");
$livestock = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vet Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<h1>Welcome Veterinarian</h1>
<a href="add_vaccination.php" class="btn btn-primary">Record Vaccination</a>
<table class="table mt-3">
    <thead><tr><th>ID</th><th>Species</th><th>Birth Date</th><th>Owner</th></tr></thead>
    <tbody>
        <?php foreach ($livestock as $animal): ?>
        <tr>
            <td><?= htmlspecialchars($animal['id']) ?></td>
            <td><?= htmlspecialchars($animal['species']) ?></td>
            <td><?= htmlspecialchars($animal['birth_date']) ?></td>
            <td><?= htmlspecialchars($animal['owner']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
