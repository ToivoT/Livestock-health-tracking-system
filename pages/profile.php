<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
}
require '../config/db.php';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user statistics
if ($_SESSION['role'] === 'farmer') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM livestock WHERE owner_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $livestock_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vaccinations v JOIN livestock l ON v.livestock_id = l.id WHERE l.owner_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $vaccination_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM disease_reports dr JOIN livestock l ON dr.livestock_id = l.id WHERE l.owner_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $disease_count = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - LHVTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <!-- Profile Information -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="display-1 text-primary mb-3">👤</div>
                        <h4><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></h4>
                        <p class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></p>
                        <hr>
                        <div class="text-start">
                            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?: 'Not set'); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'Not set'); ?></p>
                            <p><strong>Member Since:</strong> <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                        </div>
                        
                        <?php if ($_SESSION['role'] === 'farmer'): ?>
                        <hr>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="h5"><?php echo $livestock_count; ?></div>
                                <small>Livestock</small>
                            </div>
                            <div class="col-4">
                                <div class="h5"><?php echo $vaccination_count; ?></div>
                                <small>Vaccinations</small>
                            </div>
                            <div class="col-4">
                                <div class="h5"><?php echo $disease_count; ?></div>
                                <small>Reports</small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="editProfile()">
                                ✏️ Edit Profile
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="changePassword()">
                                🔒 Change Password
                            </button>
                            <button class="btn btn-outline-info btn-sm" onclick="downloadData()">
                                📥 Download My Data
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="showDeleteAccount()">
                                🗑️ Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Settings -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Settings</h5>
                    </div>
                    <div class="card-body">
                        <div id="alertContainer"></div>
                        
                        <form id="profileForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                           value="<?php echo htmlspecialchars($user['full_name']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                    <small class="form-text text-muted">Username cannot be changed</small>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="role" class="form-label">Role</label>
                                    <input type="text" class="form-control" id="role" 
                                           value="<?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="created_at" class="form-label">Account Created</label>
                                    <input type="text" class="form-control" id="created_at" 
                                           value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h6>Notification Preferences</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" checked>
                                        <label class="form-check-label" for="email_notifications">
                                            Email Notifications
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="sms_notifications">
                                        <label class="form-check-label" for="sms_notifications">
                                            SMS Notifications
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="vaccination_reminders" checked>
                                        <label class="form-check-label" for="vaccination_reminders">
                                            Vaccination Reminders
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="disease_alerts" checked>
                                        <label class="form-check-label" for="disease_alerts">
                                            Disease Alerts
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <span id="saveText">Save Changes</span>
                                    <span id="saveSpinner" class="spinner-border spinner-border-sm d-none ms-2"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Activity Log -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <div id="activityLog">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="passwordForm">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" required>
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_new_password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updatePassword()">Update Password</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadActivityLog();
            
            // Profile form submission
            document.getElementById('profileForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                await saveProfile();
            });
        });
        
        async function saveProfile() {
            const form = document.getElementById('profileForm');
            const saveBtn = form.querySelector('button[type="submit"]');
            const saveText = document.getElementById('saveText');
            const saveSpinner = document.getElementById('saveSpinner');
            
            try {
                saveBtn.disabled = true;
                saveText.textContent = 'Saving...';
                saveSpinner.classList.remove('d-none');
                
                const formData = new FormData(form);
                const data = {
                    id: <?php echo $_SESSION['user_id']; ?>,
                    full_name: formData.get('full_name'),
                    email: formData.get('email'),
                    phone: formData.get('phone')
                };
                
                const response = await fetch('/api/register.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', 'Profile updated successfully');
                } else {
                    showAlert('danger', result.message);
                }
            } catch (error) {
                showAlert('danger', 'Error updating profile');
            } finally {
                saveBtn.disabled = false;
                saveText.textContent = 'Save Changes';
                saveSpinner.classList.add('d-none');
            }
        }
        
        function changePassword() {
            new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
        }
        
        async function updatePassword() {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_new_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Passwords do not match');
                return;
            }
            
            if (newPassword.length < 6) {
                alert('Password must be at least 6 characters');
                return;
            }
            
            // This would call an API to update the password
            showAlert('info', 'Password update functionality will be implemented');
            bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
        }
        
        async function loadActivityLog() {
            // Simulate loading activity
            setTimeout(() => {
                document.getElementById('activityLog').innerHTML = `
                    <div class="timeline">
                        <div class="timeline-item">
                            <small class="text-muted">Today, 10:30 AM</small>
                            <p class="mb-1">Logged in from Windhoek, Namibia</p>
                        </div>
                        <div class="timeline-item">
                            <small class="text-muted">Yesterday, 3:45 PM</small>
                            <p class="mb-1">Added new livestock record</p>
                        </div>
                        <div class="timeline-item">
                            <small class="text-muted">2 days ago</small>
                            <p class="mb-1">Updated vaccination schedule</p>
                        </div>
                    </div>
                `;
            }, 1000);
        }
        
        function downloadData() {
            showAlert('info', 'Preparing your data for download...');
            // This would generate and download user's data
        }
        
        function showDeleteAccount() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                showAlert('warning', 'Account deletion requires additional verification');
            }
        }
        
        function resetForm() {
            document.getElementById('profileForm').reset();
        }
        
        function editProfile() {
            document.getElementById('full_name').focus();
        }
    </script>
</body>
</html>