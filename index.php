<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to appropriate dashboard based on role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: /pages/admin_dashboard.php');
            break;
        case 'farmer':
        case 'vet':
        case 'extension_officer':
            header('Location: /pages/dashboard.php');
            break;
        default:
            header('Location: /pages/login.php');
            break;
    }
    exit;
}

// If not logged in, show the landing page
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LHVTS - Livestock Health & Vaccination Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <meta name="description" content="Digital livestock health management system for Namibian farmers">
    <meta name="keywords" content="livestock, health, vaccination, tracking, Namibia, farming">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                🐄 LHVTS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-primary px-3 ms-2" href="/pages/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white px-3 ms-2" href="/pages/register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section py-5" style="background: linear-gradient(135deg, var(--primary-color), #3a6b3f); color: white;">
        <div class="container">
            <div class="row align-items-center min-vh-75">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        Digital Livestock Health Management
                    </h1>
                    <p class="lead mb-4">
                        Empowering Namibian farmers with modern tools to track livestock health, 
                        manage vaccinations, and prevent disease outbreaks.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <a href="/pages/register.php" class="btn btn-light btn-lg px-4">
                            Get Started Free
                        </a>
                        <a href="#features" class="btn btn-outline-light btn-lg px-4">
                            Learn More
                        </a>
                    </div>
                    <div class="mt-4">
                        <small class="opacity-75">
                            ✅ Free to use &nbsp;&nbsp; ✅ Works offline &nbsp;&nbsp; ✅ Mobile friendly
                        </small>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <div class="hero-animation">
                        <div class="display-1 mb-3">🐄</div>
                        <div class="h3 mb-3">📱</div>
                        <p class="lead">Track • Manage • Protect</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Everything You Need</h2>
                    <p class="lead text-muted">Comprehensive livestock management in one system</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 text-center border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="display-4 text-primary mb-3">🐄</div>
                            <h5 class="card-title">Animal Registration</h5>
                            <p class="card-text">
                                Register and track individual animals with detailed profiles, 
                                including breed, birth date, and identification tags.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="display-4 text-success mb-3">💉</div>
                            <h5 class="card-title">Vaccination Tracking</h5>
                            <p class="card-text">
                                Schedule and track vaccinations with automatic reminders 
                                to ensure your livestock stays healthy and protected.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="display-4 text-danger mb-3">🏥</div>
                            <h5 class="card-title">Disease Reporting</h5>
                            <p class="card-text">
                                Quick disease reporting system to prevent outbreaks 
                                and maintain regional livestock health standards.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="display-4 text-warning mb-3">📊</div>
                            <h5 class="card-title">Health Analytics</h5>
                            <p class="card-text">
                                Visual dashboards and reports to understand your 
                                livestock health trends and make informed decisions.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="display-4 text-info mb-3">📱</div>
                            <h5 class="card-title">Mobile Access</h5>
                            <p class="card-text">
                                Access your livestock data anywhere, anytime. 
                                Works on smartphones, tablets, and computers.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 text-center border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="display-4 text-secondary mb-3">🌐</div>
                            <h5 class="card-title">Offline Support</h5>
                            <p class="card-text">
                                Continue working even without internet connection. 
                                Data syncs automatically when you're back online.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-4">Built for Namibian Farmers</h2>
                    <p class="lead mb-4">
                        LHVTS was designed specifically to address the unique challenges 
                        of livestock management in rural Namibia.
                    </p>
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="text-primary me-2">✅</div>
                                <small>Works offline</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="text-primary me-2">✅</div>
                                <small>Multi-language support</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="text-primary me-2">✅</div>
                                <small>Low data usage</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="text-primary me-2">✅</div>
                                <small>Secure & private</small>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <strong>🎯 Our Mission:</strong> To improve livestock health outcomes and food security 
                        in Namibia through accessible digital technology.
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="bg-light p-5 rounded-3 text-center">
                        <h3 class="h4 mb-3">Quick Stats</h3>
                        <div class="row g-3">
                            <div class="col-4">
                                <div class="h2 text-primary">1000+</div>
                                <small class="text-muted">Animals Tracked</small>
                            </div>
                            <div class="col-4">
                                <div class="h2 text-success">250+</div>
                                <small class="text-muted">Active Farmers</small>
                            </div>
                            <div class="col-4">
                                <div class="h2 text-warning">500+</div>
                                <small class="text-muted">Vaccinations</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- User Types Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Who Can Use LHVTS?</h2>
                    <p class="lead text-muted">Different roles, same powerful system</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="display-4 mb-3">👨‍🌾</div>
                            <h5 class="card-title">Farmers</h5>
                            <p class="card-text small">
                                Register your livestock, track vaccinations, and monitor health records.
                            </p>
                            <a href="/pages/register.php?role=farmer" class="btn btn-outline-primary btn-sm">Register as Farmer</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="display-4 mb-3">👩‍⚕️</div>
                            <h5 class="card-title">Veterinarians</h5>
                            <p class="card-text small">
                                Access livestock records, provide treatments, and monitor regional health.
                            </p>
                            <a href="/pages/register.php?role=vet" class="btn btn-outline-primary btn-sm">Register as Vet</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="display-4 mb-3">👨‍💼</div>
                            <h5 class="card-title">Extension Officers</h5>
                            <p class="card-text small">
                                Support farmers, analyze trends, and coordinate regional health programs.
                            </p>
                            <a href="/pages/register.php?role=extension_officer" class="btn btn-outline-primary btn-sm">Register as Officer</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="display-4 mb-3">👨‍💻</div>
                            <h5 class="card-title">Administrators</h5>
                            <p class="card-text small">
                                Manage users, oversee system operations, and generate comprehensive reports.
                            </p>
                            <a href="/pages/login.php" class="btn btn-outline-secondary btn-sm">Admin Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-5" style="background: var(--primary-color); color: white;">
        <div class="container text-center">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <h2 class="display-5 fw-bold mb-4">Ready to Get Started?</h2>
                    <p class="lead mb-4">
                        Join hundreds of Namibian farmers already using LHVTS to manage their livestock health.
                    </p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                        <a href="/pages/register.php" class="btn btn-light btn-lg px-5">
                            Create Free Account
                        </a>
                        <a href="/pages/login.php" class="btn btn-outline-light btn-lg px-5">
                            Login to Existing Account
                        </a>
                    </div>
                    <p class="mt-4 small opacity-75">
                        No credit card required • Always free for farmers • Secure & private
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3">🐄 LHVTS</h5>
                    <p>
                        Livestock Health & Vaccination Tracking System for Namibia.
                        Improving animal health through digital technology.
                    </p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="/pages/login.php" class="text-light text-decoration-none">Login</a></li>
                        <li><a href="/pages/register.php" class="text-light text-decoration-none">Register</a></li>
                        <li><a href="#features" class="text-light text-decoration-none">Features</a></li>
                        <li><a href="#about" class="text-light text-decoration-none">About</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Support</h6>
                    <ul class="list-unstyled">
                        <li><a href="mailto:support@lhvts.na" class="text-light text-decoration-none">Help Center</a></li>
                        <li><a href="#" class="text-light text-decoration-none">User Guide</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Contact Us</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small>&copy; 2025 LHVTS - Livestock Health & Vaccination Tracking System</small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small>Made with ❤️ for Namibian farmers</small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Add animation to cards on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all cards
            document.querySelectorAll('.card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });

            // Auto-fill demo account info on certain key combinations
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                    e.preventDefault();
                    alert('Demo mode: Visit /pages/login.php and use:\n\nFarmer: farmer1 / password\nVet: vet1 / password\nAdmin: admin1 / password');
                }
            });

            // Add dynamic stats counter animation
            const statsElements = document.querySelectorAll('.h2');
            statsElements.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                if (!isNaN(finalValue) && finalValue > 0) {
                    let currentValue = 0;
                    const increment = Math.ceil(finalValue / 50);
                    const timer = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= finalValue) {
                            currentValue = finalValue;
                            clearInterval(timer);
                        }
                        stat.textContent = currentValue + (stat.textContent.includes('+') ? '+' : '');
                    }, 30);
                }
            });
        });

        // Check online status
        function updateConnectionStatus() {
            if (!navigator.onLine) {
                const offlineAlert = document.createElement('div');
                offlineAlert.className = 'alert alert-warning position-fixed top-0 start-50 translate-middle-x mt-2';
                offlineAlert.style.zIndex = '9999';
                offlineAlert.innerHTML = '⚠️ You are currently offline. Some features may not be available.';
                document.body.appendChild(offlineAlert);
                
                setTimeout(() => {
                    if (offlineAlert.parentNode) {
                        offlineAlert.remove();
                    }
                }, 5000);
            }
        }

        window.addEventListener('load', updateConnectionStatus);
        window.addEventListener('online', () => {
            console.log('Back online');
        });
        window.addEventListener('offline', updateConnectionStatus);
    </script>

    <style>
        .hero-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .min-vh-75 {
            min-height: 75vh;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            transition: all 0.2s ease;
        }

        /* Custom scrollbar for webkit browsers */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #1e3a21;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .display-4 {
                font-size: 2.5rem;
            }
            
            .display-5 {
                font-size: 2rem;
            }

            .hero-section {
                padding: 2rem 0;
            }

            .card-body {
                padding: 1.5rem !important;
            }
        }
    </style>
</body>
</html>