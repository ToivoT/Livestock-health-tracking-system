<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disease Reporting - LHVTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2>🏥 Disease Reporting</h2>
                        <p class="text-muted">Report and track livestock health issues</p>
                    </div>
                    <div>
                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reportDiseaseModal">
                            <span>🚨</span> Report Disease
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4" id="statsSection">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="h3 text-danger" id="totalReports">0</div>
                        <div class="text-muted">Total Reports</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="h3 text-warning" id="affectedAnimals">0</div>
                        <div class="text-muted">Affected Animals</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="h3 text-info" id="diseaseTypes">0</div>
                        <div class="text-muted">Disease Types</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="h3 text-primary" id="recentReports">0</div>
                        <div class="text-muted">Recent (30 days)</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Section -->
        <div class="row mb-4" id="alertSection" style="display: none;">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>⚠️ Disease Alert!</strong> <span id="alertMessage"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Search</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search disease or animal...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Animal</label>
                                <select class="form-select" id="livestockFilter">
                                    <option value="">All Animals</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Date Range</label>
                                <input type="date" class="form-control" id="dateFrom">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">&nbsp;</label>
                                <div>
                                    <button class="btn btn-outline-primary btn-sm" onclick="applyFilters()">
                                        🔍 Apply Filters
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                                        ✖️ Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <!-- Disease Reports Table -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Disease Reports</h5>
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshReports()">
                            <span id="refreshIcon">🔄</span> Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="alertContainer"></div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Animal ID</th>
                                        <th>Disease</th>
                                        <th>Symptoms</th>
                                        <?php if ($_SESSION['role'] !== 'farmer'): ?><th>Owner</th><?php endif; ?>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="reportsTableBody">
                                    <tr>
                                        <td colspan="<?php echo ($_SESSION['role'] !== 'farmer') ? '6' : '5'; ?>" class="text-center">
                                            <div class="py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2 text-muted">Loading disease reports...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <nav aria-label="Reports pagination" id="paginationNav" style="display: none;">
                            <ul class="pagination justify-content-center">
                                <!-- Pagination will be generated by JavaScript -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Common Diseases Panel -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Common Diseases</h5>
                    </div>
                    <div class="card-body">
                        <div id="commonDiseasesList">
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reportDiseaseModal">
                                🚨 Report New Disease
                            </button>
                            <button class="btn btn-outline-warning" onclick="showEmergencyContacts()">
                                📞 Emergency Contacts
                            </button>
                            <button class="btn btn-outline-info" onclick="exportReports()">
                                📊 Export Reports
                            </button>
                            <button class="btn btn-outline-primary" onclick="showDiseaseGuide()">
                                📖 Disease Guide
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Disease Modal -->
    <div class="modal fade" id="reportDiseaseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">🚨 Report Disease</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="diseaseReportForm">
                        <input type="hidden" id="reportId" name="id">
                        
                        <div class="alert alert-warning">
                            <strong>Important:</strong> Report diseases immediately to prevent spread and ensure timely treatment.
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="livestock_id" class="form-label">Animal *</label>
                                <select class="form-select" id="livestock_id" name="livestock_id" required>
                                    <option value="">Select Animal</option>
                                </select>
                                <div class="form-text">Select the affected animal</div>
                            </div>
                            <div class="col-md-6">
                                <label for="date_reported" class="form-label">Date Observed *</label>
                                <input type="date" class="form-control" id="date_reported" name="date_reported" required>
                                <div class="form-text">When symptoms were first noticed</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="disease_name" class="form-label">Disease/Condition *</label>
                            <select class="form-select" id="disease_name" name="disease_name" required>
                                <option value="">Select Disease/Condition</option>
                                <optgroup label="Common Diseases">
                                    <option value="Foot and Mouth Disease">Foot and Mouth Disease</option>
                                    <option value="Anthrax">Anthrax</option>
                                    <option value="Lumpy Skin Disease">Lumpy Skin Disease</option>
                                    <option value="Newcastle Disease">Newcastle Disease</option>
                                    <option value="African Swine Fever">African Swine Fever</option>
                                </optgroup>
                                <optgroup label="Symptoms-Based">
                                    <option value="Fever">Fever</option>
                                    <option value="Diarrhea">Diarrhea</option>
                                    <option value="Respiratory Issues">Respiratory Issues</option>
                                    <option value="Skin Condition">Skin Condition</option>
                                    <option value="Loss of Appetite">Loss of Appetite</option>
                                    <option value="Lameness">Lameness</option>
                                </optgroup>
                                <option value="Other">Other (specify in symptoms)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="symptoms" class="form-label">Symptoms *</label>
                            <textarea class="form-control" id="symptoms" name="symptoms" rows="4" required placeholder="Describe the symptoms in detail..."></textarea>
                            <div class="form-text">Include: behavior changes, physical symptoms, duration, severity</div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Severity</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="severity" id="severity_mild" value="mild" checked>
                                        <label class="form-check-label" for="severity_mild">🟢 Mild</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="severity" id="severity_moderate" value="moderate">
                                        <label class="form-check-label" for="severity_moderate">🟡 Moderate</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="severity" id="severity_severe" value="severe">
                                        <label class="form-check-label" for="severity_severe">🔴 Severe</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_vet" name="notify_vet" checked>
                                    <label class="form-check-label" for="notify_vet">
                                        Notify veterinarian immediately
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="isolate_animal" name="isolate_animal">
                                    <label class="form-check-label" for="isolate_animal">
                                        Animal has been isolated
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="submitDiseaseReport()">
                        <span id="submitText">Submit Report</span>
                        <span id="submitSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Contacts Modal -->
    <div class="modal fade" id="emergencyContactsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">📞 Emergency Veterinary Contacts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <div class="list-group-item">
                            <h6>State Veterinary Office</h6>
                            <p class="mb-1">📱 +264 61 208 7111</p>
                            <small>Available 24/7 for disease outbreaks</small>
                        </div>
                        <div class="list-group-item">
                            <h6>Regional Veterinary Clinic</h6>
                            <p class="mb-1">📱 +264 61 123 4567</p>
                            <small>Mon-Fri: 8AM-5PM, Sat: 8AM-12PM</small>
                        </div>
                        <div class="list-group-item">
                            <h6>Emergency Animal Hospital</h6>
                            <p class="mb-1">📱 +264 81 234 5678</p>
                            <small>24/7 Emergency Services</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Disease Guide Modal -->
    <div class="modal fade" id="diseaseGuideModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">📖 Common Livestock Diseases Guide</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="accordion" id="diseaseAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#fmd">
                                    Foot and Mouth Disease
                                </button>
                            </h2>
                            <div id="fmd" class="accordion-collapse collapse show" data-bs-parent="#diseaseAccordion">
                                <div class="accordion-body">
                                    <strong>Symptoms:</strong> Blisters in mouth and on feet, lameness, drooling, fever<br>
                                    <strong>Prevention:</strong> Vaccination, quarantine new animals, control movement<br>
                                    <strong>Action:</strong> Isolate immediately, notify authorities, call vet
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#anthrax">
                                    Anthrax
                                </button>
                            </h2>
                            <div id="anthrax" class="accordion-collapse collapse" data-bs-parent="#diseaseAccordion">
                                <div class="accordion-body">
                                    <strong>Symptoms:</strong> Sudden death, bleeding from body openings, swelling<br>
                                    <strong>Prevention:</strong> Annual vaccination, proper carcass disposal<br>
                                    <strong>Action:</strong> Do NOT open carcass, notify authorities immediately
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#newcastle">
                                    Newcastle Disease (Poultry)
                                </button>
                            </h2>
                            <div id="newcastle" class="accordion-collapse collapse" data-bs-parent="#diseaseAccordion">
                                <div class="accordion-body">
                                    <strong>Symptoms:</strong> Respiratory distress, twisted neck, paralysis, green diarrhea<br>
                                    <strong>Prevention:</strong> Regular vaccination, biosecurity measures<br>
                                    <strong>Action:</strong> Isolate flock, cull infected birds, disinfect premises
                                </div>
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
        let currentReports = [];
        let currentPage = 1;
        let livestockList = [];

        document.addEventListener('DOMContentLoaded', function() {
            loadInitialData();
            setupEventListeners();
        });

        function loadInitialData() {
            loadStatistics();
            loadReports();
            loadLivestock();
            loadCommonDiseases();
        }

        function setupEventListeners() {
            // Set today as default date
            document.getElementById('date_reported').valueAsDate = new Date();
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', debounce(filterReports, 300));
            
            // Disease type change handler
            document.getElementById('disease_name').addEventListener('change', function() {
                if (this.value === 'Other') {
                    document.getElementById('symptoms').placeholder = 'Please specify the disease/condition and describe symptoms...';
                }
            });
        }

        async function loadStatistics() {
            try {
                const response = await fetch('/api/disease.php?action=statistics');
                const result = await response.json();
                
                if (result.success) {
                    const stats = result.statistics;
                    document.getElementById('totalReports').textContent = stats.total_reports || 0;
                    document.getElementById('affectedAnimals').textContent = stats.affected_animals || 0;
                    document.getElementById('diseaseTypes').textContent = stats.disease_types || 0;
                    document.getElementById('recentReports').textContent = stats.recent_reports || 0;
                    
                    // Show alert if there are recent reports
                    if (stats.recent_reports > 5) {
                        showDiseaseAlert(`${stats.recent_reports} disease cases reported in the last 30 days. Please monitor your livestock closely.`);
                    }
                }
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        async function loadReports(page = 1) {
            try {
                showLoading(true);
                
                const params = new URLSearchParams({
                    page: page,
                    limit: 10
                });
                
                // Add filters
                const livestockFilter = document.getElementById('livestockFilter').value;
                const dateFrom = document.getElementById('dateFrom').value;
                
                if (livestockFilter) params.append('livestock_id', livestockFilter);
                if (dateFrom) params.append('date_from', dateFrom);
                
                const response = await fetch(`/api/disease.php?${params}`);
                const result = await response.json();
                
                if (result.success) {
                    currentReports = result.data;
                    displayReports(result.data);
                    updatePagination(result.pagination);
                }
            } catch (error) {
                console.error('Error loading reports:', error);
                showAlert('danger', 'Error loading disease reports');
            } finally {
                showLoading(false);
            }
        }

        function displayReports(reports) {
            const tbody = document.getElementById('reportsTableBody');
            const userRole = <?php echo json_encode($_SESSION['role']); ?>;
            
            if (reports.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="${userRole !== 'farmer' ? '6' : '5'}" class="text-center py-4">
                            <div class="text-muted">
                                <div class="h2">🏥</div>
                                <p>No disease reports found</p>
                                <small>Healthy livestock - keep up the good work!</small>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = reports.map(report => {
                const daysAgo = report.days_ago;
                const dateClass = daysAgo === 0 ? 'text-danger' : (daysAgo <= 7 ? 'text-warning' : '');
                const ownerColumn = userRole !== 'farmer' ? `<td>${report.owner_name || '-'}</td>` : '';
                
                return `
                    <tr>
                        <td class="${dateClass}">
                            ${formatDate(report.date_reported)}
                            ${daysAgo === 0 ? '<span class="badge bg-danger ms-1">Today</span>' : ''}
                        </td>
                        <td><strong>${report.animal_id}</strong><br><small>${report.species}</small></td>
                        <td><span class="badge bg-danger">${report.disease_name}</span></td>
                        <td><small>${truncateText(report.symptoms, 50)}</small></td>
                        ${ownerColumn}
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="viewReport(${report.id})" title="View">
                                    👁️
                                </button>
                                <button class="btn btn-outline-secondary" onclick="editReport(${report.id})" title="Edit">
                                    ✏️
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteReport(${report.id})" title="Delete">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        async function loadLivestock() {
            try {
                const response = await fetch('/api/livestock.php');
                const result = await response.json();
                
                if (result.success) {
                    livestockList = result.data;
                    
                    const livestockSelect = document.getElementById('livestock_id');
                    const filterSelect = document.getElementById('livestockFilter');
                    
                    livestockSelect.innerHTML = '<option value="">Select Animal</option>';
                    filterSelect.innerHTML = '<option value="">All Animals</option>';
                    
                    livestockList.forEach(animal => {
                        const optionText = `${animal.animal_id} - ${animal.species} (${animal.breed})`;
                        livestockSelect.innerHTML += `<option value="${animal.id}">${optionText}</option>`;
                        filterSelect.innerHTML += `<option value="${animal.id}">${optionText}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error loading livestock:', error);
            }
        }

        async function loadCommonDiseases() {
            try {
                const response = await fetch('/api/disease.php?action=common_diseases');
                const result = await response.json();
                
                if (result.success && result.common_diseases.length > 0) {
                    const html = result.common_diseases.map((disease, index) => `
                        <div class="d-flex justify-content-between align-items-center py-2 ${index < result.common_diseases.length - 1 ? 'border-bottom' : ''}">
                            <span>${disease.disease_name}</span>
                            <span class="badge bg-secondary">${disease.count}</span>
                        </div>
                    `).join('');
                    
                    document.getElementById('commonDiseasesList').innerHTML = html;
                } else {
                    document.getElementById('commonDiseasesList').innerHTML = '<p class="text-muted text-center">No disease reports yet</p>';
                }
            } catch (error) {
                console.error('Error loading common diseases:', error);
            }
        }

        async function submitDiseaseReport() {
            const form = document.getElementById('diseaseReportForm');
            const submitBtn = document.querySelector('#reportDiseaseModal .btn-danger');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');
            
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            
            try {
                submitBtn.disabled = true;
                submitText.textContent = 'Submitting...';
                submitSpinner.classList.remove('d-none');
                
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                
                // Add severity to symptoms if specified
                const severity = document.querySelector('input[name="severity"]:checked').value;
                data.symptoms = `[${severity.toUpperCase()}] ${data.symptoms}`;
                
                const reportId = document.getElementById('reportId').value;
                const method = reportId ? 'PUT' : 'POST';
                
                const response = await fetch('/api/disease.php', {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    bootstrap.Modal.getInstance(document.getElementById('reportDiseaseModal')).hide();
                    form.reset();
                    form.classList.remove('was-validated');
                    loadReports(currentPage);
                    loadStatistics();
                    loadCommonDiseases();
                    
                    // Show additional alert for severe cases
                    if (severity === 'severe') {
                        setTimeout(() => {
                            showAlert('warning', 'Severe case reported. Please contact a veterinarian immediately!');
                        }, 1000);
                    }
                } else {
                    showAlert('danger', result.message);
                }
            } catch (error) {
                console.error('Error submitting report:', error);
                showAlert('danger', 'Error submitting disease report');
            } finally {
                submitBtn.disabled = false;
                submitText.textContent = 'Submit Report';
                submitSpinner.classList.add('d-none');
            }
        }

        function showDiseaseAlert(message) {
            const alertSection = document.getElementById('alertSection');
            const alertMessage = document.getElementById('alertMessage');
            
            alertMessage.textContent = message;
            alertSection.style.display = 'block';
        }

        function showEmergencyContacts() {
            new bootstrap.Modal(document.getElementById('emergencyContactsModal')).show();
        }

        function showDiseaseGuide() {
            new bootstrap.Modal(document.getElementById('diseaseGuideModal')).show();
        }

        function exportReports() {
            if (currentReports.length === 0) {
                showAlert('warning', 'No reports to export');
                return;
            }
            
            const csv = [
                ['Date', 'Animal ID', 'Species', 'Disease', 'Symptoms'],
                ...currentReports.map(r => [
                    r.date_reported,
                    r.animal_id,
                    r.species,
                    r.disease_name,
                    r.symptoms
                ])
            ].map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `disease_reports_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
            
            showAlert('success', 'Reports exported successfully');
        }

        function refreshReports() {
            const icon = document.getElementById('refreshIcon');
            icon.style.transform = 'rotate(360deg)';
            icon.style.transition = 'transform 0.5s';
            
            loadReports(currentPage);
            loadStatistics();
            
            setTimeout(() => {
                icon.style.transform = 'rotate(0deg)';
            }, 500);
        }

        function applyFilters() {
            loadReports(1);
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('livestockFilter').value = '';
            document.getElementById('dateFrom').value = '';
            loadReports(1);
        }

        function filterReports() {
            // Local filtering for search
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            if (!searchTerm) {
                loadReports(currentPage);
                return;
            }
            
            const filtered = currentReports.filter(report => 
                report.animal_id.toLowerCase().includes(searchTerm) ||
                report.disease_name.toLowerCase().includes(searchTerm) ||
                report.symptoms.toLowerCase().includes(searchTerm)
            );
            
            displayReports(filtered);
        }

        function updatePagination(pagination) {
            // Implement pagination UI update
            const nav = document.getElementById('paginationNav');
            if (pagination.total_pages <= 1) {
                nav.style.display = 'none';
                return;
            }
            
            nav.style.display = 'block';
            // Add pagination buttons...
        }

        // Placeholder functions
        function viewReport(id) { 
            const report = currentReports.find(r => r.id === id);
            if (report) {
                alert(`Disease: ${report.disease_name}\nAnimal: ${report.animal_id}\nDate: ${report.date_reported}\nSymptoms: ${report.symptoms}`);
            }
        }
        
        function editReport(id) { 
            const report = currentReports.find(r => r.id === id);
            if (report) {
                document.getElementById('reportId').value = report.id;
                document.getElementById('livestock_id').value = report.livestock_id;
                document.getElementById('disease_name').value = report.disease_name;
                document.getElementById('date_reported').value = report.date_reported;
                document.getElementById('symptoms').value = report.symptoms;
                new bootstrap.Modal(document.getElementById('reportDiseaseModal')).show();
            }
        }
        
        async function deleteReport(id) { 
            if (confirm('Are you sure you want to delete this disease report?')) {
                try {
                    const response = await fetch('/api/disease.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        showAlert('success', 'Report deleted successfully');
                        loadReports(currentPage);
                        loadStatistics();
                    } else {
                        showAlert('danger', result.message);
                    }
                } catch (error) {
                    showAlert('danger', 'Error deleting report');
                }
            }
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString();
        }

        function truncateText(text, maxLength) {
            if (!text) return '';
            return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>
</body>
</html>