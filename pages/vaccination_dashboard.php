<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
}
require '../config/db.php';

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Get vaccination statistics
$stats = [];

if ($userRole === 'farmer') {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN date_administered IS NOT NULL THEN 1 END) as completed,
            COUNT(CASE WHEN date_administered IS NULL AND due_date < CURDATE() THEN 1 END) as overdue,
            COUNT(CASE WHEN date_administered IS NULL AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as urgent,
            COUNT(CASE WHEN date_administered IS NULL AND due_date > DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as upcoming
        FROM vaccinations v 
        JOIN livestock l ON v.livestock_id = l.id 
        WHERE l.owner_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN date_administered IS NOT NULL THEN 1 END) as completed,
            COUNT(CASE WHEN date_administered IS NULL AND due_date < CURDATE() THEN 1 END) as overdue,
            COUNT(CASE WHEN date_administered IS NULL AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as urgent,
            COUNT(CASE WHEN date_administered IS NULL AND due_date > DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as upcoming
        FROM vaccinations v
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Calculate compliance rate
$compliance_rate = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 1) : 0;

// Get vaccination types distribution
if ($userRole === 'farmer') {
    $stmt = $pdo->prepare("
        SELECT v.type, COUNT(*) as count 
        FROM vaccinations v 
        JOIN livestock l ON v.livestock_id = l.id 
        WHERE l.owner_id = ? 
        GROUP BY v.type 
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
} else {
    $stmt = $pdo->prepare("
        SELECT type, COUNT(*) as count 
        FROM vaccinations 
        GROUP BY type 
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute();
}
$vaccination_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly vaccination trends
if ($userRole === 'farmer') {
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(COALESCE(date_administered, due_date), '%Y-%m') as month,
            COUNT(CASE WHEN date_administered IS NOT NULL THEN 1 END) as administered,
            COUNT(CASE WHEN date_administered IS NULL THEN 1 END) as scheduled
        FROM vaccinations v 
        JOIN livestock l ON v.livestock_id = l.id 
        WHERE l.owner_id = ? 
        AND COALESCE(date_administered, due_date) >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month 
        ORDER BY month
    ");
    $stmt->execute([$userId]);
} else {
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(COALESCE(date_administered, due_date), '%Y-%m') as month,
            COUNT(CASE WHEN date_administered IS NOT NULL THEN 1 END) as administered,
            COUNT(CASE WHEN date_administered IS NULL THEN 1 END) as scheduled
        FROM vaccinations 
        WHERE COALESCE(date_administered, due_date) >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month 
        ORDER BY month
    ");
    $stmt->execute();
}
$monthly_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Dashboard - LHVTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2>💉 Vaccination Dashboard</h2>
                        <p class="text-muted">Overview of vaccination schedules and compliance</p>
                    </div>
                    <div>
                        <a href="/pages/record_vaccination.php" class="btn btn-primary">
                            📝 Manage Vaccinations
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Vaccinations</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-number text-success"><?php echo $stats['completed']; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-number text-danger"><?php echo $stats['overdue']; ?></div>
                        <div class="stat-label">Overdue</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-number text-warning"><?php echo $stats['urgent']; ?></div>
                        <div class="stat-label">Due Soon</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compliance Rate -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="card-title">Vaccination Compliance Rate</h5>
                                <div class="progress mb-2" style="height: 25px;">
                                    <div class="progress-bar <?php echo $compliance_rate >= 80 ? 'bg-success' : ($compliance_rate >= 60 ? 'bg-warning' : 'bg-danger'); ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo $compliance_rate; ?>%">
                                        <?php echo $compliance_rate; ?>%
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php if ($compliance_rate >= 90): ?>
                                        Excellent compliance! Keep up the great work.
                                    <?php elseif ($compliance_rate >= 80): ?>
                                        Good compliance. Consider improving overdue vaccinations.
                                    <?php elseif ($compliance_rate >= 60): ?>
                                        Moderate compliance. Focus on catching up with overdue vaccinations.
                                    <?php else: ?>
                                        Low compliance. Urgent attention needed for vaccination schedule.
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="display-6 <?php echo $compliance_rate >= 80 ? 'text-success' : ($compliance_rate >= 60 ? 'text-warning' : 'text-danger'); ?>">
                                    <?php echo $compliance_rate >= 80 ? '😊' : ($compliance_rate >= 60 ? '😐' : '😟'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Vaccination Types Chart -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Vaccination Types Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="typesChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Monthly Trends Chart -->
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Monthly Vaccination Trends</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trendsChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions and Alerts -->
        <div class="row">
            <!-- Quick Actions -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/pages/record_vaccination.php" class="btn btn-primary">
                                💉 Record New Vaccination
                            </a>
                            <button class="btn btn-outline-warning" onclick="showOverdueOnly()">
                                ⚠️ View Overdue (<?php echo $stats['overdue']; ?>)
                            </button>
                            <button class="btn btn-outline-info" onclick="showUpcomingOnly()">
                                📅 View Upcoming (<?php echo $stats['urgent']; ?>)
                            </button>
                            <button class="btn btn-outline-success" onclick="generateVaccinationReport()">
                                📊 Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Vaccination Activity</h5>
                    </div>
                    <div class="card-body">
                        <div id="recentActivity">
                            <div class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading recent activity...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vaccination Calendar Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Vaccination Schedule Calendar</h5>
                        <div>
                            <button class="btn btn-outline-primary btn-sm" onclick="previousMonth()">‹ Previous</button>
                            <span id="currentMonth" class="mx-2"></span>
                            <button class="btn btn-outline-primary btn-sm" onclick="nextMonth()">Next ›</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="calendarContainer">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading calendar...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading vaccination schedule...</p>
                            </div>
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
        let currentCalendarDate = new Date();

        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            loadRecentActivity();
            loadCalendar();
        });

        function initializeCharts() {
            // Vaccination Types Chart
            const typesData = <?php echo json_encode($vaccination_types); ?>;
            if (typesData.length > 0) {
                const typesCtx = document.getElementById('typesChart');
                new Chart(typesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: typesData.map(item => item.type),
                        datasets: [{
                            data: typesData.map(item => item.count),
                            backgroundColor: [
                                '#2c5530', '#6c757d', '#28a745', '#ffc107', '#dc3545',
                                '#17a2b8', '#6f42c1', '#e83e8c', '#fd7e14', '#20c997'
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
                                    usePointStyle: true,
                                    font: { size: 12 }
                                }
                            }
                        }
                    }
                });
            }

            // Monthly Trends Chart
            const trendsData = <?php echo json_encode($monthly_trends); ?>;
            if (trendsData.length > 0) {
                const trendsCtx = document.getElementById('trendsChart');
                new Chart(trendsCtx, {
                    type: 'line',
                    data: {
                        labels: trendsData.map(item => {
                            const date = new Date(item.month + '-01');
                            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                        }),
                        datasets: [
                            {
                                label: 'Administered',
                                data: trendsData.map(item => item.administered),
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                tension: 0.4
                            },
                            {
                                label: 'Scheduled',
                                data: trendsData.map(item => item.scheduled),
                                borderColor: '#17a2b8',
                                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                                tension: 0.4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        }
                    }
                });
            }
        }

        async function loadRecentActivity() {
            try {
                // This would call the vaccination API for recent activity
                // For now, we'll show a placeholder
                document.getElementById('recentActivity').innerHTML = `
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex align-items-center">
                            <div class="me-3">💉</div>
                            <div class="flex-grow-1">
                                <div class="fw-bold">Recent vaccinations will appear here</div>
                                <small class="text-muted">Connect to the vaccination API to see recent activity</small>
                            </div>
                        </div>
                        <div class="list-group-item text-center">
                            <a href="/pages/record_vaccination.php" class="btn btn-outline-primary btn-sm">
                                View All Vaccinations
                            </a>
                        </div>
                    </div>
                `;
            } catch (error) {
                console.error('Error loading recent activity:', error);
            }
        }

        function loadCalendar() {
            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"];
            
            document.getElementById('currentMonth').textContent = 
                monthNames[currentCalendarDate.getMonth()] + ' ' + currentCalendarDate.getFullYear();

            // Simple calendar placeholder - in a real implementation, this would load vaccination data
            document.getElementById('calendarContainer').innerHTML = `
                <div class="alert alert-info text-center">
                    <h6>📅 Vaccination Schedule Calendar</h6>
                    <p class="mb-2">Calendar view showing vaccination due dates will be implemented here.</p>
                    <p class="small text-muted">This will display:</p>
                    <div class="row text-center small">
                        <div class="col-3">
                            <span class="badge bg-danger">●</span> Overdue
                        </div>
                        <div class="col-3">
                            <span class="badge bg-warning">●</span> Due Soon
                        </div>
                        <div class="col-3">
                            <span class="badge bg-info">●</span> Scheduled
                        </div>
                        <div class="col-3">
                            <span class="badge bg-success">●</span> Completed
                        </div>
                    </div>
                </div>
            `;
        }

        function previousMonth() {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() - 1);
            loadCalendar();
        }

        function nextMonth() {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + 1);
            loadCalendar();
        }

        function showOverdueOnly() {
            window.location.href = '/pages/record_vaccination.php?filter=overdue';
        }

        function showUpcomingOnly() {
            window.location.href = '/pages/record_vaccination.php?filter=due_soon';
        }

        function generateVaccinationReport() {
            // Generate and download a vaccination report
            const reportData = {
                total: <?php echo $stats['total']; ?>,
                completed: <?php echo $stats['completed']; ?>,
                overdue: <?php echo $stats['overdue']; ?>,
                urgent: <?php echo $stats['urgent']; ?>,
                upcoming: <?php echo $stats['upcoming']; ?>,
                compliance_rate: <?php echo $compliance_rate; ?>,
                generated_date: new Date().toLocaleDateString(),
                generated_by: '<?php echo $_SESSION['role']; ?>'
            };

            // Simple text report
            const report = `
LIVESTOCK VACCINATION REPORT
Generated: ${reportData.generated_date}
Generated by: ${reportData.generated_by}
================================

SUMMARY STATISTICS:
- Total Vaccinations: ${reportData.total}
- Completed: ${reportData.completed}
- Overdue: ${reportData.overdue}  
- Due Soon: ${reportData.urgent}
- Upcoming: ${reportData.upcoming}
- Compliance Rate: ${reportData.compliance_rate}%

COMPLIANCE STATUS:
${reportData.compliance_rate >= 80 ? '✓ Good compliance' : 
  reportData.compliance_rate >= 60 ? '! Needs improvement' : '✗ Poor compliance - urgent attention needed'}

RECOMMENDATIONS:
${reportData.overdue > 0 ? `- Address ${reportData.overdue} overdue vaccinations immediately` : ''}
${reportData.urgent > 0 ? `- Schedule ${reportData.urgent} vaccinations due soon` : ''}
${reportData.compliance_rate < 80 ? '- Implement vaccination reminder system' : ''}
${reportData.compliance_rate >= 90 ? '- Excellent work! Maintain current schedule' : ''}
            `;

            // Download as text file
            const blob = new Blob([report], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `vaccination_report_${new Date().toISOString().split('T')[0]}.txt`;
            a.click();
            window.URL.revokeObjectURL(url);

            showAlert('success', 'Vaccination report generated and downloaded!');
        }

        // Auto-refresh data every 5 minutes
        setInterval(function() {
            if (document.visibilityState === 'visible') {
                loadRecentActivity();
            }
        }, 300000);
    </script>
</body>
</html>