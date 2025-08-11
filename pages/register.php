<?php
session_start();
// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // If admin, allow access to create new users
    if ($_SESSION['role'] !== 'admin') {
        header('Location: /pages/dashboard.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_SESSION['user_id']) ? 'Add New User' : 'Register'; ?> - LHVTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php include '../includes/header.php'; ?>
    <?php endif; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <?php echo isset($_SESSION['user_id']) ? 'Add New User' : 'Register for LHVTS'; ?>
                        </h4>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                        <small class="text-light">Join the Livestock Health and Vaccination Tracking System</small>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div id="alertContainer"></div>
                        
                        <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="alert alert-info">
                            <strong>Welcome!</strong> Register to start tracking your livestock health records digitally.
                            Already have an account? <a href="/pages/login.php" class="alert-link">Login here</a>
                        </div>
                        <?php endif; ?>
                        
                        <form id="registerForm" data-validate>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                                        <div class="form-text">Your complete name as it should appear in records</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username *</label>
                                        <input type="text" class="form-control" id="username" name="username" required>
                                        <div class="form-text">Unique username for login (letters, numbers, underscore only)</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                        <div class="form-text">Used for system notifications and password recovery</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone">
                                        <div class="form-text">Optional: For SMS notifications</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select your role</option>
                                    <option value="farmer">Farmer</option>
                                    <option value="vet">Veterinarian</option>
                                    <option value="extension_officer">Extension Officer</option>
                                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <option value="admin">Administrator</option>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">
                                    <small>
                                        <strong>Farmer:</strong> Register and track your own livestock<br>
                                        <strong>Veterinarian:</strong> View livestock records and provide health services<br>
                                        <strong>Extension Officer:</strong> Support farmers and monitor regional health trends
                                    </small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="form-text">Minimum 6 characters</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <div class="form-text">Re-enter your password</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Password strength indicator -->
                            <div class="mb-3">
                                <div class="password-strength" id="passwordStrength" style="display: none;">
                                    <small>Password strength: <span id="strengthLevel">Weak</span></small>
                                    <div class="progress mt-1" style="height: 5px;">
                                        <div class="progress-bar" id="strengthBar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of Use</a> and Privacy Policy
                                </label>
                            </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="/pages/admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                                <?php else: ?>
                                    <a href="/pages/login.php" class="btn btn-outline-primary">Already have an account?</a>
                                <?php endif; ?>
                                <button type="submit" class="btn btn-primary">
                                    <span id="submitText"><?php echo isset($_SESSION['user_id']) ? 'Create User' : 'Register'; ?></span>
                                    <span id="submitSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                <!-- System Information -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h6 class="card-title">🐄 About LHVTS</h6>
                        <p class="card-text small text-muted">
                            The Livestock Health and Vaccination Tracking System helps Namibian farmers 
                            digitally manage their livestock health records, track vaccinations, and 
                            report diseases quickly to prevent outbreaks.
                        </p>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-primary h5">📱</div>
                                <small>Mobile Friendly</small>
                            </div>
                            <div class="col-4">
                                <div class="text-success h5">📊</div>
                                <small>Track Health</small>
                            </div>
                            <div class="col-4">
                                <div class="text-warning h5">🔔</div>
                                <small>Get Alerts</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Terms of Use Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms of Use</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By using the Livestock Health and Vaccination Tracking System (LHVTS), you agree to these terms.</p>
                    
                    <h6>2. Purpose</h6>
                    <p>LHVTS is designed to help farmers track livestock health, vaccinations, and disease reporting for improved animal welfare and food safety.</p>
                    
                    <h6>3. User Responsibilities</h6>
                    <ul>
                        <li>Provide accurate information about your livestock</li>
                        <li>Report diseases promptly and accurately</li>
                        <li>Keep your login credentials secure</li>
                        <li>Use the system responsibly and legally</li>
                    </ul>
                    
                    <h6>4. Data Privacy</h6>
                    <p>We protect your data and only share aggregated, anonymous information for disease monitoring and research purposes.</p>
                    
                    <h6>5. Disclaimer</h6>
                    <p>LHVTS is a tracking tool. Always consult qualified veterinarians for health decisions.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Accept</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <?php include '../includes/footer.php'; ?>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const submitBtn = form.querySelector('button[type="submit"]');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');
            const passwordField = document.getElementById('password');
            const confirmPasswordField = document.getElementById('confirm_password');
            
            // Password strength checker
            passwordField.addEventListener('input', checkPasswordStrength);
            
            // Password matching validation
            confirmPasswordField.addEventListener('input', validatePasswordMatch);
            
            // Username validation (real-time)
            document.getElementById('username').addEventListener('input', function() {
                const username = this.value;
                const regex = /^[a-zA-Z0-9_]+$/;
                
                if (username && !regex.test(username)) {
                    this.setCustomValidity('Username can only contain letters, numbers, and underscores');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Final validation
                if (!validatePasswordMatch()) {
                    showAlert('danger', 'Passwords do not match');
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                submitText.textContent = 'Creating Account...';
                submitSpinner.classList.remove('d-none');
                
                try {
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());
                    
                    const response = await fetch('/api/register.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showAlert('success', result.message);
                        form.reset();
                        
                        // Redirect based on context
                        setTimeout(() => {
                            if (result.auto_login) {
                                // New user registration - redirect to dashboard
                                window.location.href = '/pages/dashboard.php';
                            } else {
                                // Admin creating user - redirect to admin panel
                                window.location.href = '/pages/admin_dashboard.php';
                            }
                        }, 2000);
                    } else {
                        showAlert('danger', result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred. Please try again.');
                } finally {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitText.textContent = <?php echo json_encode(isset($_SESSION['user_id']) ? 'Create User' : 'Register'); ?>;
                    submitSpinner.classList.add('d-none');
                }
            });
        });
        
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthIndicator = document.getElementById('passwordStrength');
            const strengthLevel = document.getElementById('strengthLevel');
            const strengthBar = document.getElementById('strengthBar');
            
            if (password.length === 0) {
                strengthIndicator.style.display = 'none';
                return;
            }
            
            strengthIndicator.style.display = 'block';
            
            let strength = 0;
            let level = 'Very Weak';
            let color = 'danger';
            
            // Length check
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            
            // Character variety checks
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
            
            // Determine level and color
            if (strength <= 2) {
                level = 'Weak';
                color = 'danger';
            } else if (strength <= 4) {
                level = 'Medium';
                color = 'warning';
            } else {
                level = 'Strong';
                color = 'success';
            }
            
            strengthLevel.textContent = level;
            strengthBar.style.width = (strength / 6 * 100) + '%';
            strengthBar.className = `progress-bar bg-${color}`;
        }
        
        function validatePasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const confirmField = document.getElementById('confirm_password');
            
            if (confirmPassword && password !== confirmPassword) {
                confirmField.setCustomValidity('Passwords do not match');
                confirmField.classList.add('is-invalid');
                return false;
            } else {
                confirmField.setCustomValidity('');
                confirmField.classList.remove('is-invalid');
                if (confirmPassword) confirmField.classList.add('is-valid');
                return true;
            }
        }
    </script>
</body>
</html>