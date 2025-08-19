<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /pages/dashboard.php');
    exit;
}

$logged_out = isset($_GET['logged_out']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LHVTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-md-6 col-lg-4">
                <div class="text-center mb-4">
                    <h1 class="display-4">🐄</h1>
                    <h2 class="text-primary">LHVTS</h2>
                    <p class="text-muted">Livestock Health & Vaccination Tracking System</p>
                </div>
                
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4 class="mb-0">Login</h4>
                    </div>
                    <div class="card-body p-4">
                        <div id="alertContainer">
                            <?php if ($logged_out): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                You have been logged out successfully.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <form id="loginForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control form-control-lg" id="username" name="username" required autofocus>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <span id="loginText">Login</span>
                                    <span id="loginSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                                </button>
                            </div>
                        </form>
                        
                        <hr>
                        
                        <div class="text-center">
                            <p class="mb-2">Don't have an account?</p>
                            <a href="/pages/register.php" class="btn btn-outline-primary">
                                Register Now
                            </a>
                        </div>
                        
                        
                
                <div class="text-center mt-4">
                    <div class="row text-center text-muted">
                        <div class="col-4">
                            <div class="h5 text-primary">📱</div>
                            <small>Mobile<br>Friendly</small>
                        </div>
                        <div class="col-4">
                            <div class="h5 text-success">🔒</div>
                            <small>Secure<br>Data</small>
                        </div>
                        <div class="col-4">
                            <div class="h5 text-warning">📊</div>
                            <small>Track<br>Health</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
        // Additional login page specific JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on username field
            document.getElementById('username').focus();
            
            // Demo account quick fill
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.shiftKey) {
                    const usernameField = document.getElementById('username');
                    const passwordField = document.getElementById('password');
                    
                    switch(e.key) {
                        case 'A':
                            e.preventDefault();
                            usernameField.value = 'admin';
                            passwordField.value = 'password';
                            showAlert('info', 'Demo admin account filled');
                            break;
                        case 'F':
                            e.preventDefault();
                            usernameField.value = 'farmer1';
                            passwordField.value = 'password';
                            showAlert('info', 'Demo farmer account filled');
                            break;
                        case 'V':
                            e.preventDefault();
                            usernameField.value = 'vet1';
                            passwordField.value = 'password';
                            showAlert('info', 'Demo vet account filled');
                            break;
                    }
                }
            });
        });
    </script>
</body>
</html>