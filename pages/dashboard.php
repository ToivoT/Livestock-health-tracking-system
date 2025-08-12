<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
}
require '../config/db.php';

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Role-specific data fetching
$dashboardData = [];

if ($userRole === 'farmer') {
    // Farmer dashboard data
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM livestock WHERE owner_id = ?");
    $stmt->execute([$userId]);
    $dashboardData['my_livestock'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vaccinations v JOIN livestock l ON v.livestock_id = l.id WHERE l.owner_id = ?");
    $stmt->execute([$userId]);
    $dashboardData['total_vaccinations'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vaccinations v JOIN livestock l ON v.livestock_id = l.id WHERE l.owner_id = ? AND v.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute([$userId]);
    $dashboardData['upcoming_vaccinations'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM disease_reports dr JOIN livestock l ON dr.livestock_id = l.id WHERE l.owner_id = ? AND dr.date_reported >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute([$userId]);
    $dashboardData['recent_diseases'] = $stmt->fetchColumn();
    
} elseif ($userRole === 'vet') {
    // Veterinarian dashboard data
    $dashboardData['total_livestock'] = $pdo->query("SELECT COUNT(*) FROM livestock")->fetchColumn();
    $dashboardData['total_farmers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetchColumn();
    $dashboardData['recent_vaccinations'] = $pdo->query("SELECT COUNT(*) FROM vaccinations WHERE date_administered >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
    $dashboardData['active_diseases'] = $pdo->query("SELECT COUNT(*) FROM disease_reports WHERE date_reported >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
    
} elseif ($userRole === 'extension_officer') {
    // Extension officer dashboard data
    $dashboardData['total_livestock'] = $pdo->query("SELECT COUNT(*) FROM livestock")->fetchColumn();
    $dashboardData['total_farmers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetchColumn();
    $dashboardData['vaccination_coverage'] = $pdo->query("SELECT COUNT(DISTINCT livestock_id) FROM vaccinations WHERE date_administered >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)")->fetchColumn();
    $dashboardData['disease_reports'] = $pdo->query("SELECT COUNT(*) FROM disease_reports WHERE date_reported >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)")->fetchColumn();
    
} elseif ($userRole === 'admin') {
    // Admin dashboard data - redirect to admin-specific dashboard
    header('Location: /pages/admin_dashboard.php');
    exit;
}

// Get recent activity for all non-admin users
$recentActivity = [];

if ($userRole === 'farmer') {
    // Recent livestock and vaccinations for farmer
    $stmt = $pdo->prepare("
        (SELECT 'livestock' as type, animal_id as title, species as description, created_at as date_time 
         FROM livestock WHERE owner_id = ? ORDER BY created_at DESC LIMIT 3)
        UNION ALL
        (SELECT 'vaccination' as type, v.type as title, l.animal_id as description, v.date_administered as date_time
         FROM vaccinations v JOIN livestock l ON v.livestock_id = l.id 
         WHERE l.owner_id = ? ORDER BY v.date_administered DESC LIMIT 3)
        ORDER BY date_time DESC LIMIT 5
    ");
    $stmt->execute([$userId, $userId]);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Recent system-wide activity for vets and extension officers
    $stmt = $pdo->prepare("
        (SELECT 'livestock' as type, CONCAT(l.animal_id, ' by ', u.username) as title, l.species as description, l.created_at as date_time 
         FROM livestock l JOIN users u ON l.owner_id = u.id ORDER BY l.created_at DESC LIMIT 3)
        UNION ALL
        (SELECT 'vaccination' as type, CONCAT(v.type, ' - ', l.animal_id) as title, u.username as description, v.date_administered as date_time
         FROM vaccinations v JOIN livestock l ON v.livestock_id = l.id JOIN users u ON l.owner_id = u.id
         ORDER BY v.date_administered DESC LIMIT 3)
        UNION ALL
        (SELECT 'disease' as type, CONCAT(dr.disease_name, ' - ', l.animal_id) as title, u.username as description, dr.date_reported as date_time
         FROM disease_reports dr JOIN livestock l ON dr.livestock_id = l.id JOIN users u ON l.owner_id = u.id
         ORDER BY dr.date_reported DESC LIMIT 2)
        ORDER BY date_time DESC LIMIT 8
    ");
    $stmt->execute();
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get vaccination alerts (upcoming due dates)
$vaccinationAlerts = [];
if ($userRole === 'farmer') {
    $stmt = $pdo->prepare("
        SELECT l.animal_id, v.type, v.due_date, 
               DATEDIFF(v.due_date, CURDATE()) as days_until_due
        FROM vaccinations v 
        JOIN livestock l ON v.livestock_id = l.id 
        WHERE l.owner_id = ? 
        AND v.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY v.due_date ASC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $vaccinationAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT l.animal_id, v.type, v.due_date, u.username as owner,
               DATEDIFF(v.due_date, CURDATE()) as days_until_due
        FROM vaccinations v 
        JOIN livestock l ON v.livestock_id = l.id 
        JOIN users u ON l.owner_id = u.id
        WHERE v.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY v.due_date ASC
        LIMIT 10
    ");
    $stmt->execute();
    $vaccinationAlerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get livestock by species for chart
$speciesData = [];
if ($userRole === 'farmer') {
    $stmt = $pdo->prepare("SELECT species, COUNT(*) as count FROM livestock WHERE owner_id = ? GROUP BY species");
    $stmt->execute([$userId]);
    $speciesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT species, COUNT(*) as count FROM livestock GROUP BY species");
    $stmt->execute();
    $speciesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LHVTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2>Welcome back, <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>!</h2>
                        <p class="text-muted">
                            <?php 
                            echo ucfirst(str_replace('_', ' ', $userRole)) . ' Dashboard';
                            if ($userRole === 'farmer') {
                                echo ' - Manage your livestock health records';
                            } elseif ($userRole === 'vet') {
                                echo ' - Monitor animal health across the region';
                            } elseif ($userRole === 'extension_officer') {
                                echo ' - Support farmers and track regional trends';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">
                            <?php echo date('l, F j, Y'); ?><br>
                            Last login: <?php echo date('M j, g:i A'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php if ($userRole === 'farmer'): ?>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card dashboard-card">
                        <div class="card-body text-center">
                            <div class="stat-number"><?php echo $dashboardData['my_livestock']; ?></div>
                            <div class="stat-label">My Livestock</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card dashboard-card">
                        <div class="card-body text-center">
                            <div class="stat-number"><?php echo $dashboardData['total_vaccinations']; ?></div>
                            <div class="stat-label">Total Vaccinations</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card dashboard-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-warning"><?php echo $dashboardData['upcoming_vaccinations']; ?></div>
                            <div class="stat-label">Due Soon</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card dashboard-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-danger"><?php echo $dashboardData['recent_diseases']; ?></div>
                            <div class="stat-label">Recent Issues</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card dashboard-card">
                        <div class="card-body text-center">
                            <div class="stat-number"><?php echo $dashboardData['total_livestock']; ?></div>
                            <div class="stat-label">Total Livestock</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card dashboard-card">
                        <div class="card-body text-center">
                            <div class="stat-number"><?php echo $dashboardData['total_farmers']; ?></div>
                            <div class="stat-label">Active Farmers</div>
                        </div>
                    </div>
                </div>
                <?php if ($userRole === 'vet'): ?>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card dashboard-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-success"><?php echo $dashboardData['recent_vaccinations']; ?></div>
                            <div class="stat-label">Recent Vaccinations</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card dashboard-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-warning"><?php echo $dashboardData['active_diseases']; ?></div>
                            <div class="stat-label">Active Cases</div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card dashboard-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-success"><?php echo $dashboardData['vaccination_coverage']; ?></div>
                            <div class="stat-label">Vaccinated (1yr)</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card stat-card dashboard-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-info"><?php echo $dashboardData['disease_reports']; ?></div>
                            <div class="stat-label">Reports (3mo)</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
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
                            <?php if ($userRole === 'farmer'): ?>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="/pages/register_livestock.php" class="quick-action">
                                    <div>🐄</div>
                                    <div>Register Livestock</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="/pages/record_vaccination.php" class="quick-action">
                                    <div>💉</div>
                                    <div>Record Vaccination</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="/pages/report_disease.php" class="quick-action">
                                    <div>🏥</div>
                                    <div>Report Disease</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="#" onclick="viewMyLivestock()" class="quick-action">
                                    <div>📋</div>
                                    <div>View My Animals</div>
                                </a>
                            </div>
                            <?php elseif ($userRole === 'vet'): ?>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="/pages/record_vaccination.php" class="quick-action">
                                    <div>💉</div>
                                    <div>Record Vaccination</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="#" onclick="viewAllLivestock()" class="quick-action">
                                    <div>🐄</div>
                                    <div>View All Livestock</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="/pages/report_disease.php" class="quick-action">
                                    <div>🏥</div>
                                    <div>Health Reports</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="#" onclick="generateReport()" class="quick-action">
                                    <div>📊</div>
                                    <div>Generate Report</div>
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="#" onclick="viewAllLivestock()" class="quick-action">
                                    <div>🐄</div>
                                    <div>View All Livestock</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="/pages/report_disease.php" class="quick-action">
                                    <div>📈</div>
                                    <div>Health Trends</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="#" onclick="farmerSupport()" class="quick-action">
                                    <div>🤝</div>
                                    <div>Farmer Support</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="#" onclick="generateReport()" class="quick-action">
                                    <div>📊</div>
                                    <div>Regional Report</div>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row">
            <!-- Left Column: Recent Activity & Alerts -->
            <div class="col-lg-8">
                <!-- Vaccination Alerts -->
                <?php if (!empty($vaccinationAlerts)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">⚠️ Vaccination Alerts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Animal</th>
                                        <th>Vaccination</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <?php if ($userRole !== 'farmer'): ?><th>Owner</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vaccinationAlerts as $alert): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($alert['animal_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($alert['type']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($alert['due_date'])); ?></td>
                                        <td>
                                            <?php 
                                            $days = (int)$alert['days_until_due'];
                                            if ($days < 0) {
                                                echo '<span class="badge bg-danger">Overdue</span>';
                                            } elseif ($days <= 7) {
                                                echo '<span class="badge bg-warning">Due Soon</span>';
                                            } else {
                                                echo '<span class="badge bg-info">Due in ' . $days . ' days</span>';
                                            }
                                            ?>
                                        </td>
                                        <?php if ($userRole !== 'farmer'): ?>
                                        <td><?php echo htmlspecialchars($alert['owner']); ?></td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentActivity)): ?>
                        <div class="text-center text-muted py-4">
                            <div class="h1">📝</div>
                            <p>No recent activity to show.</p>
                            <small>Activity will appear here as you use the system.</small>
                        </div>
                        <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recentActivity as $activity): ?>
                            <div class="timeline-item mb-3 pb-3 border-bottom">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <?php
                                        switch($activity['type']) {
                                            case 'livestock':
                                                echo '<span class="badge bg-success rounded-pill">🐄</span>';
                                                break;
                                            case 'vaccination':
                                                echo '<span class="badge bg-primary rounded-pill">💉</span>';
                                                break;
                                            case 'disease':
                                                echo '<span class="badge bg-danger rounded-pill">🏥</span>';
                                                break;
                                            default:
                                                echo '<span class="badge bg-secondary rounded-pill">📝</span>';
                                        }
                                        ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?php echo htmlspecialchars($activity['title']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($activity['description']); ?></div>
                                        <div class="text-muted small">
                                            <?php echo timeAgo($activity['date_time']); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Charts & Stats -->
            <div class="col-lg-4">
                <!-- Livestock by Species Chart -->
                <?php if (!empty($speciesData)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Livestock Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="speciesChart" width="300" height="300"></canvas>
                    </div>
                </div>
                <?php endif; ?>

                <!-- System Health Status -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">System Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Database</span>
                            <span class="badge bg-success">Online</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Last Sync</span>
                            <span class="badge bg-info"><?php echo date('H:i'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Connection</span>
                            <span class="badge bg-success" id="connectionBadge">Online</span>
                        </div>
                        <hr>
                        <div class="text-center">
                            <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()">
                                <span id="refreshIcon">🔄</span> Refresh Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            setupRealTimeUpdates();
        });

        // Initialize charts
        function initializeCharts() {
            <?php if (!empty($speciesData)): ?>
            const speciesCtx = document.getElementById('speciesChart');
            if (speciesCtx) {
                new Chart(speciesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode(array_column($speciesData, 'species')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($speciesData, 'count')); ?>,
                            backgroundColor: [
                                '#2c5530',
                                '#6c757d', 
                                '#28a745',
                                '#ffc107',
                                '#dc3545',
                                '#17a2b8'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>
        }

        // Setup real-time updates
        function setupRealTimeUpdates() {
            // Update connection status
            function updateConnectionStatus() {
                const badge = document.getElementById('connectionBadge');
                if (navigator.onLine) {
                    badge.textContent = 'Online';
                    badge.className = 'badge bg-success';
                } else {
                    badge.textContent = 'Offline';
                    badge.className = 'badge bg-danger';
                }
            }

            window.addEventListener('online', updateConnectionStatus);
            window.addEventListener('offline', updateConnectionStatus);
        }

        // Quick action functions
        function viewMyLivestock() {
            window.location.href = '/pages/my_livestock.php';
        }

        function viewAllLivestock() {
            window.location.href = '/pages/all_livestock.php';
        }

        function generateReport() {
            showAlert('info', 'Report generation feature coming soon!');
        }

        function farmerSupport() {
            showAlert('info', 'Farmer support tools are being developed!');
        }

        function refreshDashboard() {
            const icon = document.getElementById('refreshIcon');
            icon.style.transform = 'rotate(360deg)';
            icon.style.transition = 'transform 0.5s';
            
            // Simulate refresh
            setTimeout(() => {
                location.reload();
            }, 500);
        }

        // Auto-refresh dashboard data every 5 minutes
        setInterval(function() {
            if (navigator.onLine) {
                // Silently refresh some data without full page reload
                fetch('/api/dashboard.php?action=quick_stats')
                    .then(response => response.json())
                    .then(data => {
                        // Update statistics if needed
                        console.log('Dashboard data refreshed');
                    })
                    .catch(error => {
                        console.log('Auto-refresh failed:', error);
                    });
            }
        }, 300000); // 5 minutes
    </script>
</body>
</html>

<?php
// Helper function for time ago display
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}
?>