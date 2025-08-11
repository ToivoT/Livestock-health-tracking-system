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
    <title>Register Livestock - LHVTS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Register New Livestock</h4>
                    </div>
                    <div class="card-body">
                        <div id="alertContainer"></div>
                        
                        <form id="livestockForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="animal_id" class="form-label">Animal ID *</label>
                                        <input type="text" class="form-control" id="animal_id" name="animal_id" required>
                                        <div class="form-text">Unique identifier for this animal (e.g., ear tag number)</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="species" class="form-label">Species *</label>
                                        <select class="form-select" id="species" name="species" required>
                                            <option value="">Select Species</option>
                                            <option value="cattle">Cattle</option>
                                            <option value="sheep">Sheep</option>
                                            <option value="goat">Goat</option>
                                            <option value="pig">Pig</option>
                                            <option value="chicken">Chicken</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="breed" class="form-label">Breed *</label>
                                        <input type="text" class="form-control" id="breed" name="breed" required>
                                        <div class="form-text">E.g., Brahman, Nguni, Dorper</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="gender" class="form-label">Gender *</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="birth_date" class="form-label">Birth Date *</label>
                                        <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="color" class="form-label">Color</label>
                                        <input type="text" class="form-control" id="color" name="color">
                                        <div class="form-text">Optional: Animal's color or markings</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="weight" class="form-label">Weight (kg)</label>
                                        <input type="number" class="form-control" id="weight" name="weight" min="0" step="0.1">
                                        <div class="form-text">Optional: Current weight</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="location" class="form-label">Location</label>
                                        <input type="text" class="form-control" id="location" name="location">
                                        <div class="form-text">Optional: Paddock, farm section, etc.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                                <div class="form-text">Optional: Additional information about this animal</div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="/pages/dashboard.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <span id="submitText">Register Livestock</span>
                                    <span id="submitSpinner" class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Quick Add Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Registrations</h5>
                    </div>
                    <div class="card-body">
                        <div id="recentLivestock">
                            <p class="text-muted">Loading recent registrations...</p>
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
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('livestockForm');
            const submitBtn = form.querySelector('button[type="submit"]');
            const submitText = document.getElementById('submitText');
            const submitSpinner = document.getElementById('submitSpinner');
            
            // Load recent livestock
            loadRecentLivestock();
            
            // Form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Show loading state
                submitBtn.disabled = true;
                submitText.textContent = 'Registering...';
                submitSpinner.classList.remove('d-none');
                
                try {
                    const formData = new FormData(form);
                    const data = Object.fromEntries(formData.entries());
                    
                    const response = await fetch('/api/livestock.php', {
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
                        loadRecentLivestock(); // Refresh the list
                        
                        // Ask if user wants to add another
                        setTimeout(() => {
                            if (confirm('Livestock registered successfully! Would you like to register another animal?')) {
                                document.getElementById('animal_id').focus();
                            } else {
                                window.location.href = '/pages/dashboard.php';
                            }
                        }, 1000);
                    } else {
                        showAlert('danger', result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred. Please try again.');
                } finally {
                    // Reset button state
                    submitBtn.disabled = false;
                    submitText.textContent = 'Register Livestock';
                    submitSpinner.classList.add('d-none');
                }
            });
            
            // Auto-generate animal ID if empty
            document.getElementById('species').addEventListener('change', function() {
                const animalIdField = document.getElementById('animal_id');
                if (!animalIdField.value) {
                    const species = this.value;
                    if (species) {
                        const prefix = species.substring(0, 2).toUpperCase();
                        const timestamp = Date.now().toString().slice(-6);
                        animalIdField.value = `${prefix}${timestamp}`;
                    }
                }
            });
        });
        
        async function loadRecentLivestock() {
            try {
                const response = await fetch('/api/livestock.php');
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    const recentItems = result.data.slice(0, 5); // Show last 5
                    const html = recentItems.map(animal => `
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <strong>${animal.animal_id}</strong> - ${animal.species} (${animal.breed})
                                <small class="text-muted d-block">Registered: ${formatDate(animal.created_at)}</small>
                            </div>
                            <span class="badge bg-secondary">${animal.gender}</span>
                        </div>
                    `).join('');
                    
                    document.getElementById('recentLivestock').innerHTML = html;
                } else {
                    document.getElementById('recentLivestock').innerHTML = '<p class="text-muted">No livestock registered yet.</p>';
                }
            } catch (error) {
                console.error('Error loading recent livestock:', error);
                document.getElementById('recentLivestock').innerHTML = '<p class="text-danger">Error loading data.</p>';
            }
        }
        
        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            document.getElementById('alertContainer').innerHTML = alertHtml;
        }
        
        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString();
        }
    </script>
</body>
</html>