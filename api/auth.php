<?php
session_start();
require '../config/db.php';

function requireRole($role)
{
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header("Location: ../pages/login.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Get user from DB
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify credentials
    if ($user && password_verify($password, $user['password'])) {
        // Store session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        switch ($user['role']) {
            case 'farmer':
                $redirect = '/pages/farmer_dashboard.php';
                break;
            case 'vet':
                $redirect = '/pages/vet_dashboard.php';
                break;
            case 'admin':
                $redirect = '/pages/admin_dashboard.php';
                break;
            case 'extension_officer':
                $redirect = '/pages/extension_dashboard.php';
                break;
            default:
                $redirect = '/pages/login.php';
                break;
        }

        echo json_encode([
            'success' => true,
            'redirect' => $redirect
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
