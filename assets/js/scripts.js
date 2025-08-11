// Enhanced LHVTS JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize the application
function initializeApp() {
    setupOfflineDetection();
    registerServiceWorker();
    setupFormValidation();
}

// Offline detection and handling
function setupOfflineDetection() {
    const offlineIndicator = document.getElementById('offlineIndicator');
    const connectionStatus = document.getElementById('connectionStatus');
    
    function updateOnlineStatus() {
        if (navigator.onLine) {
            if (offlineIndicator) offlineIndicator.classList.remove('show');
            if (connectionStatus) {
                connectionStatus.textContent = 'Online';
                connectionStatus.className = 'badge bg-success';
            }
            // Sync any offline data
            syncOfflineData();
        } else {
            if (offlineIndicator) offlineIndicator.classList.add('show');
            if (connectionStatus) {
                connectionStatus.textContent = 'Offline';
                connectionStatus.className = 'badge bg-warning';
            }
        }
    }
    
    // Check initial status
    updateOnlineStatus();
    
    // Listen for online/offline events
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
}

// Register service worker for offline functionality
function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                console.log('Service Worker registered successfully:', registration);
            })
            .catch(error => {
                console.log('Service Worker registration failed:', error);
            });
    }
}

// Enhanced form validation
function setupFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', validateForm);
        
        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => clearValidation(input));
        });
    });
}

function validateForm(e) {
    const form = e.target;
    let isValid = true;
    
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        showAlert('warning', 'Please fill in all required fields correctly.');
    }
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let message = '';
    
    // Required field validation
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        message = 'This field is required';
    }
    
    // Email validation
    if (field.type === 'email' && value && !isValidEmail(value)) {
        isValid = false;
        message = 'Please enter a valid email address';
    }
    
    // Date validation
    if (field.type === 'date' && value) {
        const date = new Date(value);
        const today = new Date();
        
        if (field.name === 'birth_date' && date > today) {
            isValid = false;
            message = 'Birth date cannot be in the future';
        }
    }
    
    // Update field appearance
    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        clearFieldError(field);
    } else {
        field.classList.remove('is-valid');
        field.classList.add('is-invalid');
        showFieldError(field, message);
    }
    
    return isValid;
}

function clearValidation(field) {
    field.classList.remove('is-valid', 'is-invalid');
    clearFieldError(field);
}

function showFieldError(field, message) {
    clearFieldError(field);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    errorDiv.id = field.id + '_error';
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    const existingError = document.getElementById(field.id + '_error');
    if (existingError) {
        existingError.remove();
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Utility functions
function showAlert(type, message, duration = 5000) {
    const alertContainer = document.getElementById('alertContainer') || document.body;
    const alertId = 'alert_' + Date.now();
    
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    if (alertContainer.id === 'alertContainer') {
        alertContainer.innerHTML = alertHtml;
    } else {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = alertHtml;
        alertContainer.insertBefore(tempDiv.firstElementChild, alertContainer.firstChild);
    }
    
    // Auto-dismiss after duration
    if (duration > 0) {
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        }, duration);
    }
}

function showLoading(show = true) {
    let overlay = document.getElementById('loadingOverlay');
    
    if (show) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'loading-overlay';
            overlay.innerHTML = '<div class="loading-spinner"></div>';
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    } else {
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
}

// Offline data management
function saveOfflineData(key, data) {
    try {
        const offlineStore = JSON.parse(localStorage.getItem('lhvts_offline_data') || '{}');
        offlineStore[key] = {
            data: data,
            timestamp: Date.now(),
            synced: false
        };
        localStorage.setItem('lhvts_offline_data', JSON.stringify(offlineStore));
        return true;
    } catch (error) {
        console.error('Error saving offline data:', error);
        return false;
    }
}

function getOfflineData(key) {
    try {
        const offlineStore = JSON.parse(localStorage.getItem('lhvts_offline_data') || '{}');
        return offlineStore[key] || null;
    } catch (error) {
        console.error('Error getting offline data:', error);
        return null;
    }
}

function syncOfflineData() {
    if (!navigator.onLine) return;
    
    try {
        const offlineStore = JSON.parse(localStorage.getItem('lhvts_offline_data') || '{}');
        const pendingSync = Object.entries(offlineStore).filter(([key, item]) => !item.synced);
        
        if (pendingSync.length === 0) return;
        
        console.log(`Syncing ${pendingSync.length} offline items...`);
        
        pendingSync.forEach(async ([key, item]) => {
            try {
                // Determine the API endpoint based on the key
                let endpoint = '';
                if (key.startsWith('livestock_')) endpoint = '/api/livestock.php';
                else if (key.startsWith('vaccination_')) endpoint = '/api/vaccination.php';
                else if (key.startsWith('disease_')) endpoint = '/api/disease.php';
                
                if (endpoint) {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(item.data)
                    });
                    
                    if (response.ok) {
                        // Mark as synced
                        offlineStore[key].synced = true;
                        localStorage.setItem('lhvts_offline_data', JSON.stringify(offlineStore));
                        console.log(`Synced ${key} successfully`);
                    }
                }
            } catch (error) {
                console.error(`Error syncing ${key}:`, error);
            }
        });
        
        // Clean up synced data older than 24 hours
        const oneDayAgo = Date.now() - (24 * 60 * 60 * 1000);
        Object.keys(offlineStore).forEach(key => {
            if (offlineStore[key].synced && offlineStore[key].timestamp < oneDayAgo) {
                delete offlineStore[key];
            }
        });
        localStorage.setItem('lhvts_offline_data', JSON.stringify(offlineStore));
        
    } catch (error) {
        console.error('Error during offline sync:', error);
    }
}

// API helper functions
async function apiRequest(endpoint, options = {}) {
    const defaults = {
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    const config = { ...defaults, ...options };
    
    try {
        const response = await fetch(endpoint, config);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API request failed:', error);
        
        // If offline, save data locally
        if (!navigator.onLine && (config.method === 'POST' || config.method === 'PUT')) {
            const key = `${endpoint.replace('/api/', '')}_${Date.now()}`;
            const data = config.body ? JSON.parse(config.body) : {};
            
            if (saveOfflineData(key, data)) {
                showAlert('info', 'Data saved offline. Will sync when connection is restored.');
                return { success: true, offline: true };
            }
        }
        
        throw error;
    }
}

// Login form handler
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        try {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in...';
            
            const response = await fetch('/api/auth.php', { 
                method: 'POST', 
                body: formData 
            });
            const data = await response.json();
            
            if (data.success) {
                showAlert('success', 'Login successful! Redirecting...');
                setTimeout(() => {
                    window.location.href = '/pages/dashboard.php';
                }, 1000);
            } else {
                showAlert('danger', data.message || 'Login failed');
            }
        } catch (error) {
            console.error('Login error:', error);
            showAlert('danger', 'Connection error. Please try again.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}