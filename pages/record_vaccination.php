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
    <title>Vaccination Management - LHVTS</title>
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
                        <h2>Vaccination Management</h2>
                        <p class="text-muted">Record and track livestock vaccinations</p>
                    </div>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVaccinationModal">
                            <span>💉</span> Record Vaccination
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vaccination Alerts -->
        <div class="row mb-4" id="alertsSection" style="display: none;">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">⚠️ Vaccination Alerts</h5>
                    </div>
                    <div class="card-body" id="alertsContainer">
                        <!-- Alerts will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter and Search -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small">Search</label>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search by animal ID or type...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Animal</label>
                                <select class="form-select" id="livestockFilter">
                                    <option value="">All Animals</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Status</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="due_soon">Due Soon</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Actions</label>
                                <div>
                                    <button class="btn btn-outline-primary btn-sm" onclick="toggleView('table')">
                                        📋 Table
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="toggleView('calendar')">
                                        📅 Calendar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="row">
            <div class="col-12">
                <!-- Table View -->
                <div id="tableView" class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Vaccination Records</h5>
                        <div>
                            <button class="btn btn-outline-primary btn-sm" onclick="refreshData()">
                                <span id="refreshIcon">🔄</span> Refresh
                            </button>
                            <button class="btn btn-outline-success btn-sm" onclick="exportData()">
                                📊 Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="alertContainer"></div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Animal ID</th>
                                        <th>Species</th>
                                        <th>Vaccination Type</th>
                                        <th>Due Date</th>
                                        <th>Administered</th>
                                        <th>Status</th>
                                        <?php if ($_SESSION['role'] !== 'farmer'): ?><th>Owner</th><?php endif; ?>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="vaccinationsTableBody">
                                    <tr>
                                        <td colspan="<?php echo ($_SESSION['role'] !== 'farmer') ? '8' : '7'; ?>" class="text-center">
                                            <div class="py-4">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2 text-muted">Loading vaccination records...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <nav aria-label="Vaccinations pagination" id="paginationNav" style="display: none;">
                            <ul class="pagination justify-content-center">
                                <!-- Pagination will be generated by JavaScript -->
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Calendar View -->
                <div id="calendarView" class="card" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0">Vaccination Schedule Calendar</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div id="calendar" style="min-height: 500px;">
                                    <div class="text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading calendar...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading vaccination schedule...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Legend</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="badge bg-danger me-2" style="width: 20px; height: 20px;"></div>
                                            <small>Overdue</small>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="badge bg-warning me-2" style="width: 20px; height: 20px;"></div>
                                            <small>Due Soon</small>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="badge bg-info me-2" style="width: 20px; height: 20px;"></div>
                                            <small>Scheduled</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <div class="badge bg-success me-2" style="width: 20px; height: 20px;"></div>
                                            <small>Completed</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Quick Actions</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addVaccinationModal">
                                                💉 Record Vaccination
                                            </button>
                                            <button class="btn btn-outline-info btn-sm" onclick="showUpcoming()">
                                                📅 View Upcoming
                                            </button>
                                            <button class="btn btn-outline-warning btn-sm" onclick="showOverdue()">
                                                ⚠️ View Overdue
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Vaccination Modal -->
    <div class="modal fade" id="addVaccinationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Record Vaccination</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="vaccinationForm">
                        <input type="hidden" id="vaccinationId" name="id">
                        
                        <!-- Animal Selection -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="livestock_id" class="form-label">Animal *</label>
                                <select class="form-select" id="livestock_id" name="livestock_id" required>
                                    <option value="">Select Animal</option>
                                </select>
                                <div class="form-text">Choose the animal to vaccinate</div>
                            </div>
                            <div class="col-md-6">
                                <label for="type" class="form-label">Vaccination Type *</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Vaccination Type</option>
                                </select>
                                <div class="form-text">Type of vaccination being administered</div>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Due Date *</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" required>
                                <div class="form-text">When this vaccination is due</div>
                            </div>
                            <div class="col-md-6">
                                <label for="date_administered" class="form-label">Date Administered</label>
                                <input type="date" class="form-control" id="date_administered" name="date_administered">
                                <div class="form-text">Leave blank if not yet administered</div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="batch_number" class="form-label">Batch Number</label>
                                <input type="text" class="form-control" id="batch_number" name="batch_number" placeholder="e.g., BT2025001">
                                <div class="form-text">Vaccine batch number (optional)</div>
                            </div>
                            <div class="col-md-6">
                                <label for="next_due_date" class="form-label">Next Due Date</label>
                                <input type="date" class="form-control" id="next_due_date" name="next_due_date">
                                <div class="form-text">Auto-calculated if left blank</div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any additional notes about this vaccination..."></textarea>
                        </div>

                        <!-- Auto-schedule Follow-up -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_schedule" name="auto_schedule" checked>
                                <label class="form-check-label" for="auto_schedule">
                                    Automatically schedule follow-up vaccination
                                </label>
                                <div class="form-text">Creates a reminder for the next vaccination due date</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveVaccination()">
                        <span id="saveText">Save Vaccination</span>
                        <span id="saveSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Vaccination Details Modal -->
    <div class="modal fade" id="viewVaccinationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Vaccination Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="vaccinationDetailsBody">
                    <!-- Details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editFromViewBtn" onclick="editFromView()">
                        ✏️ Edit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteVaccinationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this vaccination record?</p>
                    <div id="deleteVaccinationDetails"></div>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
        let currentVaccinations = [];
        let currentPage = 1;
        let currentView = 'table';
        let vaccinationToDelete = null;
        let currentVaccinationForView = null;

        // Vaccination types by species
        const vaccinationTypes = {
            'cattle': [
                'Foot and Mouth Disease',
                'Anthrax',
                'Black Quarter',
                'Lumpy Skin Disease',
                'Rift Valley Fever',
                'Brucellosis',
                'Rabies'
            ],
            'sheep': [
                'Foot and Mouth Disease',
                'Anthrax',
                'Blue Tongue',
                'Rift Valley Fever',
                'Pasteurellosis'
            ],
            'goat': [
                'Foot and Mouth Disease',
                'Anthrax',
                'Pasteurellosis',
                'Rift Valley Fever'
            ],
            'chicken': [
                'Newcastle Disease',
                'Fowl Pox',
                'Avian Influenza',
                'Infectious Bronchitis'
            ],
            'pig': [
                'Swine Fever',
                'Foot and Mouth Disease',
                'Anthrax'
            ]
        };

        document.addEventListener('DOMContentLoaded', function() {
            loadInitialData();
            setupEventListeners();
        });

        function loadInitialData() {
            loadLivestock();
            loadVaccinations();
            loadAlerts();
        }

        function setupEventListeners() {
            // Search and filter handlers
            document.getElementById('searchInput').addEventListener('input', debounce(filterVaccinations, 300));
            document.getElementById('livestockFilter').addEventListener('change', filterVaccinations);
            document.getElementById('statusFilter').addEventListener('change', filterVaccinations);

            // Animal selection change handler
            document.getElementById('livestock_id').addEventListener('change', function() {
                updateVaccinationTypes(this.value);
            });

            // Date validation
            document.getElementById('date_administered').addEventListener('change', function() {
                validateDates();
            });

            document.getElementById('due_date').addEventListener('change', function() {
                validateDates();
                if (document.getElementById('date_administered').value) {
                    calculateNextDueDate();
                }
            });

            // Auto-calculate next due date when vaccination type changes
            document.getElementById('type').addEventListener('change', function() {
                if (document.getElementById('date_administered').value) {
                    calculateNextDueDate();
                }
            });
        }

        async function loadLivestock() {
            try {
                const response = await fetch('/api/vaccination.php?action=livestock_list');
                const result = await response.json();

                if (result.success) {
                    const livestockSelect = document.getElementById('livestock_id');
                    const filterSelect = document.getElementById('livestockFilter');
                    
                    // Clear existing options
                    livestockSelect.innerHTML = '<option value="">Select Animal</option>';
                    filterSelect.innerHTML = '<option value="">All Animals</option>';

                    result.livestock.forEach(animal => {
                        const ownerInfo = animal.owner_name ? ` (${animal.owner_name})` : '';
                        const optionText = `${animal.animal_id} - ${animal.species} (${animal.breed})${ownerInfo}`;
                        
                        livestockSelect.innerHTML += `<option value="${animal.id}">${optionText}</option>`;
                        filterSelect.innerHTML += `<option value="${animal.id}">${optionText}</option>`;
                    });
                } else {
                    showAlert('danger', result.message);
                }
            } catch (error) {
                console.error('Error loading livestock:', error);
                showAlert('danger', 'Error loading livestock data');
            }
        }

        function updateVaccinationTypes(livestockId) {
            const livestockSelect = document.getElementById('livestock_id');
            const typeSelect = document.getElementById('type');
            
            // Clear existing options
            typeSelect.innerHTML = '<option value="">Select Vaccination Type</option>';

            if (!livestockId) return;

            // Get selected animal's species
            const selectedOption = livestockSelect.querySelector(`option[value="${livestockId}"]`);
            if (!selectedOption) return;

            const optionText = selectedOption.textContent;
            let species = '';

            // Extract species from option text
            if (optionText.includes('cattle')) species = 'cattle';
            else if (optionText.includes('sheep')) species = 'sheep';
            else if (optionText.includes('goat')) species = 'goat';
            else if (optionText.includes('chicken')) species = 'chicken';
            else if (optionText.includes('pig')) species = 'pig';

            // Add appropriate vaccination types
            if (species && vaccinationTypes[species]) {
                vaccinationTypes[species].forEach(type => {
                    typeSelect.innerHTML += `<option value="${type}">${type}</option>`;
                });
            }

            // Add common types for all species
            const commonTypes = ['Deworming', 'Vitamin Supplement', 'Other'];
            commonTypes.forEach(type => {
                typeSelect.innerHTML += `<option value="${type}">${type}</option>`;
            });
        }

        async function loadVaccinations(page = 1) {
            try {
                showLoading(true);
                
                const params = new URLSearchParams({
                    page: page,
                    limit: 20
                });

                // Add filters
                const livestockFilter = document.getElementById('livestockFilter').value;
                const statusFilter = document.getElementById('statusFilter').value;

                if (livestockFilter) params.append('livestock_id', livestockFilter);
                if (statusFilter) params.append('status', statusFilter);

                const response = await fetch(`/api/vaccination.php?${params}`);
                const result = await response.json();

                if (result.success) {
                    currentVaccinations = result.data;
                    displayVaccinations(result.data);
                    updatePagination(result.pagination);
                } else {
                    showAlert('danger', result.message);
                }
            } catch (error) {
                console.error('Error loading vaccinations:', error);
                showAlert('danger', 'Error loading vaccination data');
            } finally {
                showLoading(false);
            }
        }

        function displayVaccinations(vaccinations) {
            const tbody = document.getElementById('vaccinationsTableBody');
            const userRole = <?php echo json_encode($_SESSION['role']); ?>;

            if (vaccinations.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="${userRole !== 'farmer' ? '8' : '7'}" class="text-center py-4">
                            <div class="text-muted">
                                <div class="h2">💉</div>
                                <p>No vaccination records found</p>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addVaccinationModal">
                                    Record First Vaccination
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = vaccinations.map(vaccination => {
                const statusBadge = getStatusBadge(vaccination.status, vaccination.days_until_due);
                const adminDate = vaccination.date_administered ? formatDate(vaccination.date_administered) : '-';
                const ownerColumn = userRole !== 'farmer' ? `<td>${vaccination.owner_name || '-'}</td>` : '';

                return `
                    <tr>
                        <td><strong>${vaccination.animal_id}</strong></td>
                        <td>${vaccination.species}</td>
                        <td>${vaccination.type}</td>
                        <td>${formatDate(vaccination.due_date)}</td>
                        <td>${adminDate}</td>
                        <td>${statusBadge}</td>
                        ${ownerColumn}
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button class="btn btn-outline-primary" onclick="viewVaccination(${vaccination.id})" title="View Details">
                                    👁️
                                </button>
                                <button class="btn btn-outline-secondary" onclick="editVaccination(${vaccination.id})" title="Edit">
                                    ✏️
                                </button>
                                ${vaccination.status !== 'completed' ? `
                                    <button class="btn btn-outline-success" onclick="markAsAdministered(${vaccination.id})" title="Mark as Administered">
                                        ✅
                                    </button>
                                ` : ''}
                                <button class="btn btn-outline-danger" onclick="deleteVaccination(${vaccination.id})" title="Delete">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function getStatusBadge(status, daysUntilDue) {
            switch (status) {
                case 'completed':
                    return '<span class="badge bg-success">Completed</span>';
                case 'overdue':
                    return `<span class="badge bg-danger">Overdue (${Math.abs(daysUntilDue)} days)</span>`;
                case 'due_soon':
                    return `<span class="badge bg-warning">Due in ${daysUntilDue} days</span>`;
                case 'scheduled':
                    return `<span class="badge bg-info">Scheduled (${daysUntilDue} days)</span>`;
                default:
                    return '<span class="badge bg-secondary">Unknown</span>';
            }
        }

        async function loadAlerts() {
            try {
                const response = await fetch('/api/vaccination.php?action=alerts&days=30');
                const result = await response.json();

                if (result.success && result.alerts.length > 0) {
                    displayAlerts(result.alerts);
                }
            } catch (error) {
                console.error('Error loading alerts:', error);
            }
        }

        function displayAlerts(alerts) {
            const alertsContainer = document.getElementById('alertsContainer');
            const alertsSection = document.getElementById('alertsSection');

            if (alerts.length === 0) {
                alertsSection.style.display = 'none';
                return;
            }

            alertsSection.style.display = 'block';

            const alertsByLevel = {
                'overdue': alerts.filter(a => a.alert_level === 'overdue'),
                'urgent': alerts.filter(a => a.alert_level === 'urgent'),
                'upcoming': alerts.filter(a => a.alert_level === 'upcoming')
            };

            let html = '';

            if (alertsByLevel.overdue.length > 0) {
                html += `
                    <div class="alert alert-danger mb-2">
                        <strong>🚨 ${alertsByLevel.overdue.length} Overdue Vaccinations</strong>
                        <ul class="mb-0 mt-2">
                            ${alertsByLevel.overdue.slice(0, 3).map(alert => `
                                <li>${alert.animal_id} - ${alert.type} (${Math.abs(alert.days_until_due)} days overdue)</li>
                            `).join('')}
                            ${alertsByLevel.overdue.length > 3 ? `<li>... and ${alertsByLevel.overdue.length - 3} more</li>` : ''}
                        </ul>
                    </div>
                `;
            }

            if (alertsByLevel.urgent.length > 0) {
                html += `
                    <div class="alert alert-warning mb-2">
                        <strong>⚠️ ${alertsByLevel.urgent.length} Urgent Vaccinations</strong>
                        <ul class="mb-0 mt-2">
                            ${alertsByLevel.urgent.slice(0, 3).map(alert => `
                                <li>${alert.animal_id} - ${alert.type} (due in ${alert.days_until_due} days)</li>
                            `).join('')}
                            ${alertsByLevel.urgent.length > 3 ? `<li>... and ${alertsByLevel.urgent.length - 3} more</li>` : ''}
                        </ul>
                    </div>
                `;
            }

            if (alertsByLevel.upcoming.length > 0) {
                html += `
                    <div class="alert alert-info">
                        <strong>📅 ${alertsByLevel.upcoming.length} Upcoming Vaccinations</strong>
                        <small class="d-block mt-1">Next 30 days</small>
                    </div>
                `;
            }

            alertsContainer.innerHTML = html;
        }

        async function saveVaccination() {
            const form = document.getElementById('vaccinationForm');
            const saveBtn = document.querySelector('#addVaccinationModal .btn-primary');
            const saveText = document.getElementById('saveText');
            const saveSpinner = document.getElementById('saveSpinner');

            // Validate form
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            try {
                saveBtn.disabled = true;
                saveText.textContent = 'Saving...';
                saveSpinner.classList.remove('d-none');

                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());

                const vaccinationId = document.getElementById('vaccinationId').value;
                const method = vaccinationId ? 'PUT' : 'POST';
                const url = '/api/vaccination.php';

                const response = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', result.message);
                    bootstrap.Modal.getInstance(document.getElementById('addVaccinationModal')).hide();
                    loadVaccinations(currentPage);
                    loadAlerts();
                    form.reset();
                    form.classList.remove('was-validated');
                } else {
                    showAlert('danger', result.message);
                }
            } catch (error) {
                console.error('Error saving vaccination:', error);
                showAlert('danger', 'Error saving vaccination record');
            } finally {
                saveBtn.disabled = false;
                saveText.textContent = 'Save Vaccination';
                saveSpinner.classList.add('d-none');
            }
        }

        async function markAsAdministered(vaccinationId) {
            const vaccination = currentVaccinations.find(v => v.id == vaccinationId);
            if (!vaccination) return;

            const today = new Date().toISOString().split('T')[0];
            
            try {
                const response = await fetch('/api/vaccination.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: vaccinationId,
                        date_administered: today
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert('success', 'Vaccination marked as administered');
                    loadVaccinations(currentPage);
                    loadAlerts();
                } else {
                    showAlert('danger', result.message);
                }
            } catch (error) {
                console.error('Error updating vaccination:', error);
                showAlert('danger', 'Error updating vaccination record');
            }
        }

        function validateDates() {
            const dueDate = document.getElementById('due_date').value;
            const adminDate = document.getElementById('date_administered').value;

            if (adminDate && dueDate && new Date(adminDate) > new Date(dueDate)) {
                document.getElementById('date_administered').setCustomValidity('Administration date cannot be after due date');
            } else {
                document.getElementById('date_administered').setCustomValidity('');
            }
        }

        function calculateNextDueDate() {
            const type = document.getElementById('type').value;
            const adminDate = document.getElementById('date_administered').value;

            if (!type || !adminDate) return;

            // Vaccination intervals in months
            const intervals = {
                'Foot and Mouth Disease': 6,
                'Anthrax': 12,
                'Black Quarter': 12,
                'Lumpy Skin Disease': 12,
                'Rift Valley Fever': 36,
                'Brucellosis': 12,
                'Rabies': 12,
                'Newcastle Disease': 6,
                'Fowl Pox': 12,
                'Avian Influenza': 6,
                'Swine Fever': 12,
                'Pasteurellosis': 6,
                'Deworming': 3,
                'Vitamin Supplement': 6
            };

            const monthsToAdd = intervals[type] || 6;
            const nextDate = new Date(adminDate);
            nextDate.setMonth(nextDate.getMonth() + monthsToAdd);

            document.getElementById('next_due_date').value = nextDate.toISOString().split('T')[0];
        }

        function toggleView(view) {
            currentView = view;
            
            if (view === 'table') {
                document.getElementById('tableView').style.display = 'block';
                document.getElementById('calendarView').style.display = 'none';
            } else {
                document.getElementById('tableView').style.display = 'none';
                document.getElementById('calendarView').style.display = 'block';
                loadCalendar();
            }
        }

        function filterVaccinations() {
            loadVaccinations(1);
        }

        function refreshData() {
            const icon = document.getElementById('refreshIcon');
            icon.style.transform = 'rotate(360deg)';
            icon.style.transition = 'transform 0.5s';
            
            loadVaccinations(currentPage);
            loadAlerts();
            
            setTimeout(() => {
                icon.style.transform = 'rotate(0deg)';
            }, 500);
        }

        function exportData() {
            // Simple CSV export
            if (currentVaccinations.length === 0) {
                showAlert('warning', 'No data to export');
                return;
            }

            const headers = ['Animal ID', 'Species', 'Vaccination Type', 'Due Date', 'Date Administered', 'Status', 'Notes'];
            const csvContent = [
                headers.join(','),
                ...currentVaccinations.map(v => [
                    v.animal_id,
                    v.species,
                    v.type,
                    v.due_date,
                    v.date_administered || '',
                    v.status,
                    (v.notes || '').replace(/,/g, ';')
                ].map(field => `"${field}"`).join(','))
            ].join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `vaccinations_${new Date().toISOString().split('T')[0]}.csv`;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString();
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

        // Placeholder functions for additional features
        function viewVaccination(id) { showAlert('info', 'View details feature coming soon!'); }
        function editVaccination(id) { showAlert('info', 'Edit feature coming soon!'); }
        function deleteVaccination(id) { showAlert('info', 'Delete feature coming soon!'); }
        function loadCalendar() { showAlert('info', 'Calendar view coming soon!'); }
        function showUpcoming() { document.getElementById('statusFilter').value = 'upcoming'; filterVaccinations(); }
        function showOverdue() { document.getElementById('statusFilter').value = 'overdue'; filterVaccinations(); }
        function updatePagination(pagination) { console.log('Pagination:', pagination); }
    </script>
</body>
</html>