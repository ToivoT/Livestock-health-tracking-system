<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /pages/login.php');
    exit;
}
require '../config/db.php';

// Get summary statistics
$stmt = $pdo->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$stmt->execute();
$userStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM livestock");
$stmt->execute();
$totalLivestock = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM vaccinations WHERE date_administered >= CURDATE() - INTERVAL 30 DAY");
$stmt->execute();
$recentVaccinations = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM disease_reports WHERE date_reported >= CURDATE() - INTERVAL 7 DAY");
$stmt->execute();
$recentDiseaseReports = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LHVTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>Admin Dashboard</h2>
                <p class="text-muted">Manage users and monitor system activity</p>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-number"><?php echo array_sum($userStats); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-number"><?php echo $userStats['farmer'] ?? 0; ?></div>
                        <div class="stat-label">Farmers</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-number"><?php echo $totalLivestock; ?></div>
                        <div class="stat-label">Total Livestock</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-number"><?php echo $recentDiseaseReports; ?></div>
                        <div class="stat-label">Recent Reports (7d)</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="/pages/register.php" class="quick-action">
                                    <div>👤</div>
                                    <div>Add New User</div>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="quick-action" onclick="exportData('users')">
                                    <div>📊</div>
                                    <div>Export User Data</div>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="quick-action" onclick="exportData('livestock')">
                                    <div>🐄</div>
                                    <div>Export Livestock</div>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="quick-action" onclick="showSystemInfo()">
                                    <div>⚙️</div>
                                    <div>System Info</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Management -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">User Management</h5>
                        <button class="btn btn-primary btn-sm" onclick="refreshUserTable()">
                            <span id="refreshIcon">🔄</span> Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="alertContainer"></div>
                        
                        <!-- Search and Filter -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" id="userSearch" placeholder="Search users..." onkeyup="filterUsers()">
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="roleFilter" onchange="filterUsers()">
                                    <option value="">All Roles</option>
                                    <option value="farmer">Farmers</option>
                                    <option value="vet">Veterinarians</option>
                                    <option value="extension_officer">Extension Officers</option>
                                    <option value="admin">Administrators</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="sortBy" onchange="sortUsers()">
                                    <option value="created_at_desc">Newest First</option>
                                    <option value="created_at_asc">Oldest First</option>
                                    <option value="username_asc">Username A-Z</option>
                                    <option value="role_asc">Role A-Z</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Users Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Role</th>
                                        <th>Email</th>
                                        <th>Livestock</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center">Loading users...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <nav aria-label="Users pagination" id="usersPagination" style="display: none;">
                            <ul class="pagination justify-content-center">
                                <!-- Pagination will be generated by JavaScript -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="id">
                        
                        <div class="mb-3">
                            <label for="editUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editUsername" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editFullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="editFullName" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editPhone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="editPhone" name="phone">
                        </div>
                        
                        <div class="mb-3">
                            <label for="editRole" class="form-label">Role</label>
                            <select class="form-select" id="editRole" name="role" required>
                                <option value="farmer">Farmer</option>
                                <option value="vet">Veterinarian</option>
                                <option value="extension_officer">Extension Officer</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="editNewPassword" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="editNewPassword" name="new_password">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveUserChanges()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete user <strong id="deleteUserName"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDeleteUser()">Delete User</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
        let allUsers = [];
        let filteredUsers = [];
        let currentPage = 1;
        const usersPerPage = 10;
        let userToDelete = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
        });
        
        async function loadUsers() {
            try {
                showLoading(true);
                const response = await fetch('/api/register.php');
                const result = await response.json();
                
                if (result.success) {
                    allUsers = result.data;
                    filteredUsers = [...allUsers];
                    displayUsers();
                } else {
                    showAlert('danger', result.message);
                }
            } catch (error) {
                console.error('Error loading users:', error);
                showAlert('danger', 'Error loading users');
            } finally {
                showLoading(false);
            }
        }
        
        function displayUsers() {
            const tbody = document.getElementById('usersTableBody');
            const startIndex = (currentPage - 1) * usersPerPage;
            const endIndex = startIndex + usersPerPage;
            const pageUsers = filteredUsers.slice(startIndex, endIndex);
            
            if (pageUsers.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No users found</td></tr>';
                return;
            }
            
            tbody.innerHTML = pageUsers.map(user => `
                <tr>
                    <td>
                        <strong>${user.username}</strong>
                        ${user.username === <?php echo json_encode($_SESSION['username']); ?> ? '<span class="badge bg-info ms-1">You</span>' : ''}
                    </td>
                    <td>${user.full_name || '-'}</td>
                    <td><span class="badge bg-secondary">${user.role.replace('_', ' ')}</span></td>
                    <td>${user.email || '-'}</td>
                    <td class="text-center">${user.livestock_count || 0}</td>
                    <td>${formatDate(user.created_at)}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editUser(${user.id})" title="Edit">
                                ✏️
                            </button>
                            ${user.id !== <?php echo $_SESSION['user_id']; ?> ? `
                                <button class="btn btn-outline-danger" onclick="deleteUser(${user.id}, '${user.username}')" title="Delete">
                                    🗑️
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `).join('');
            
            updatePagination();
        }
        
        function updatePagination() {
            const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
            const pagination = document.getElementById('usersPagination');
            
            if (totalPages <= 1) {
                pagination.style.display = 'none';
                return;
            }
            
            pagination.style.display = 'block';
            const paginationList = pagination.querySelector('.pagination');
            
            let paginationHTML = '';
            
            // Previous button
            paginationHTML += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Previous</a>
                </li>
            `;
            
            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                paginationHTML += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                    </li>
                `;
            }
            
            // Next button
            paginationHTML += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Next</a>
                </li>
            `;
            
            paginationList.innerHTML = paginationHTML;
        }
        
        function changePage(page) {
            const totalPages = Math.ceil(filteredUsers.length / usersPerPage);
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                displayUsers();
            }
        }
        
        function filterUsers() {
            const searchTerm = document.getElementById('userSearch').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter').value;
            
            filteredUsers = allUsers.filter(user => {
                const matchesSearch = !searchTerm || 
                    user.username.toLowerCase().includes(searchTerm) ||
                    (user.full_name && user.full_name.toLowerCase().includes(searchTerm)) ||
                    (user.email && user.email.toLowerCase().includes(searchTerm));
                
                const matchesRole = !roleFilter || user.role === roleFilter;
                
                return matchesSearch && matchesRole;
            });
            
            currentPage = 1;
            displayUsers();
        }
        
        function sortUsers() {
            const sortBy = document.getElementById('sortBy').value;
            
            filteredUsers.sort((a, b) => {
                switch (sortBy) {
                    case 'created_at_asc':
                        return new Date(a.created_at) - new Date(b.created_at);
                    case 'created_at_desc':
                        return new Date(b.created_at) - new Date(a.created_at);
                    case 'username_asc':
                        return a.username.localeCompare(b.username);
                    case 'role_asc':
                        return a.role.localeCompare(b.role);
                    default:
                        return 0;
                }
            });
            
            currentPage = 1;
            displayUsers();
        }
        
        function refreshUserTable() {
            const icon = document.getElementById('refreshIcon');
            icon.style.transform = 'rotate(360deg)';
            icon.style.transition = 'transform 0.5s';
            
            loadUsers();
            
            setTimeout(() => {
                icon.style.transform = 'rotate(0deg)';
            }, 500);
        }
        
        function editUser(userId) {
            const user = allUsers.find(u => u.id == userId);
            if (!user) return;
            
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editFullName').value = user.full_name || '';
            document.getElementById('editEmail').value = user.email || '';
            document.getElementById('editPhone').value = user.phone || '';
            document.getElementById('editRole').value = user.role;
            document.getElementById('editNewPassword').value = '';
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }
        
        async function saveUserChanges() {
            const form = document.getElementById('editUserForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('/api/register.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
                    loadUsers();
                } else {
                    showAlert('danger', result.message);
                }
            } catch (error) {
                console.error('Error updating user:', error);
                showAlert('danger', 'Error updating user');
            }
        }
        
        function deleteUser(userId, username) {
            userToDelete = userId;
            document.getElementById('deleteUserName').textContent = username;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }
        
        async function confirmDeleteUser() {
            if (!userToDelete) return;
            
            try {
                const response = await fetch('/api/register.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: userToDelete })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    bootstrap.Modal.getInstance(document.getElementById('deleteUserModal')).hide();
                    loadUsers();
                } else {
                    showAlert('danger', result.message);
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                showAlert('danger', 'Error deleting user');
            } finally {
                userToDelete = null;
            }
        }
        
        function exportData(type) {
            // Simple CSV export
            let data, filename;
            
            if (type === 'users') {
                data = allUsers;
                filename = 'lhvts_users.csv';
            }
            
            if (data) {
                const csv = convertToCSV(data);
                downloadCSV(csv, filename);
            }
        }
        
        function convertToCSV(objArray) {
            if (!objArray.length) return '';
            
            const headers = Object.keys(objArray[0]);
            const csvContent = [
                headers.join(','),
                ...objArray.map(obj => headers.map(header => `"${obj[header] || ''}"`).join(','))
            ].join('\n');
            
            return csvContent;
        }
        
        function downloadCSV(csv, filename) {
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', filename);
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        function showSystemInfo() {
            const info = `
                System Information:
                - PHP Version: <?php echo PHP_VERSION; ?>
                - MySQL Version: <?php 
                    $stmt = $pdo->query('SELECT VERSION()');
                    echo $stmt->fetchColumn(); 
                ?>
                - Total Users: ${allUsers.length}
                - Database: lhvts_db
                - Last Updated: ${new Date().toLocaleString()}
            `;
            
            alert(info);
        }
        
        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString();
        }
    </script>
</body>
</html>