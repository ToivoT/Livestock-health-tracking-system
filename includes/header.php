<nav class="navbar navbar-expand-lg navbar-light bg-white">
    <div class="container">
        <a class="navbar-brand" href="/pages/dashboard.php">
            🐄 LHVTS
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/pages/dashboard.php">Dashboard</a>
                </li>
                <?php if ($_SESSION['role'] === 'farmer' || $_SESSION['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/pages/register_livestock.php">Register Livestock</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="/pages/record_vaccination.php">Vaccinations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/pages/report_disease.php">Disease Reports</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <?php echo ucfirst($_SESSION['role']); ?> Account
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/pages/profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/api/logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Offline indicator -->
<div class="offline-indicator" id="offlineIndicator">
    ⚠️ You are currently offline. Changes will be saved locally and synced when connection is restored.
</div>