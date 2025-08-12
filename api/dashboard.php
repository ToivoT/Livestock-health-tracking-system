<?php
session_start();
require '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'quick_stats') {
            getQuickStats();
        } elseif ($action === 'activity') {
            getRecentActivity();
        } elseif ($action === 'alerts') {
            getVaccinationAlerts();
        } else {
            getDashboardData();
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getDashboardData() {
    global $pdo;
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'];
    
    try {
        $data = [];
        
        if ($userRole === 'farmer') {
            // Farmer dashboard statistics
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM livestock WHERE owner_id = ?");
            $stmt->execute([$userId]);
            $data['my_livestock'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM vaccinations v JOIN livestock l ON v.livestock_id = l.id WHERE l.owner_id = ?");
            $stmt->execute([$userId]);
            $data['total_vaccinations'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM vaccinations v JOIN livestock l ON v.livestock_id = l.id WHERE l.owner_id = ? AND v.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
            $stmt->execute([$userId]);
            $data['upcoming_vaccinations'] = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM disease_reports dr JOIN livestock l ON dr.livestock_id = l.id WHERE l.owner_id = ? AND dr.date_reported >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
            $stmt->execute([$userId]);
            $data['recent_diseases'] = $stmt->fetchColumn();
            
        } elseif ($userRole === 'vet') {
            // Veterinarian dashboard statistics
            $data['total_livestock'] = $pdo->query("SELECT COUNT(*) FROM livestock")->fetchColumn();
            $data['total_farmers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetchColumn();
            $data['recent_vaccinations'] = $pdo->query("SELECT COUNT(*) FROM vaccinations WHERE date_administered >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
            $data['active_diseases'] = $pdo->query("SELECT COUNT(*) FROM disease_reports WHERE date_reported >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
            
        } elseif ($userRole === 'extension_officer') {
            // Extension officer dashboard statistics
            $data['total_livestock'] = $pdo->query("SELECT COUNT(*) FROM livestock")->fetchColumn();
            $data['total_farmers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetchColumn();
            $data['vaccination_coverage'] = $pdo->query("SELECT COUNT(DISTINCT livestock_id) FROM vaccinations WHERE date_administered >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)")->fetchColumn();
            $data['disease_reports'] = $pdo->query("SELECT COUNT(*) FROM disease_reports WHERE date_reported >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)")->fetchColumn();
            
        } elseif ($userRole === 'admin') {
            // Admin dashboard statistics
            $stmt = $pdo->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
            $stmt->execute();
            $data['user_stats'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $data['total_livestock'] = $pdo->query("SELECT COUNT(*) FROM livestock")->fetchColumn();
            $data['recent_vaccinations'] = $pdo->query("SELECT COUNT(*) FROM vaccinations WHERE date_administered >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
            $data['recent_disease_reports'] = $pdo->query("SELECT COUNT(*) FROM disease_reports WHERE date_reported >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function getQuickStats() {
    global $pdo;
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'];
    
    try {
        $stats = [];
        
        // Get basic counts based on role
        if ($userRole === 'farmer') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM livestock WHERE owner_id = ?");
            $stmt->execute([$userId]);
            $stats['livestock_count'] = $stmt->fetchColumn();
            
        } else {
            $stats['total_livestock'] = $pdo->query("SELECT COUNT(*) FROM livestock")->fetchColumn();
            $stats['total_farmers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'farmer'")->fetchColumn();
        }
        
        $stats['timestamp'] = date('Y-m-d H:i:s');
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function getRecentActivity() {
    global $pdo;
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'];
    
    try {
        $limit = $_GET['limit'] ?? 10;
        
        if ($userRole === 'farmer') {
            // Recent activity for farmer
            $stmt = $pdo->prepare("
                (SELECT 'livestock' as type, animal_id as title, species as description, created_at as date_time 
                 FROM livestock WHERE owner_id = ? ORDER BY created_at DESC LIMIT 5)
                UNION ALL
                (SELECT 'vaccination' as type, v.type as title, l.animal_id as description, v.date_administered as date_time
                 FROM vaccinations v JOIN livestock l ON v.livestock_id = l.id 
                 WHERE l.owner_id = ? ORDER BY v.date_administered DESC LIMIT 5)
                ORDER BY date_time DESC LIMIT ?
            ");
            $stmt->execute([$userId, $userId, $limit]);
            
        } else {
            // System-wide activity for vets and extension officers
            $stmt = $pdo->prepare("
                (SELECT 'livestock' as type, CONCAT(l.animal_id, ' by ', u.username) as title, l.species as description, l.created_at as date_time 
                 FROM livestock l JOIN users u ON l.owner_id = u.id ORDER BY l.created_at DESC LIMIT 5)
                UNION ALL
                (SELECT 'vaccination' as type, CONCAT(v.type, ' - ', l.animal_id) as title, u.username as description, v.date_administered as date_time
                 FROM vaccinations v JOIN livestock l ON v.livestock_id = l.id JOIN users u ON l.owner_id = u.id
                 ORDER BY v.date_administered DESC LIMIT 5)
                UNION ALL
                (SELECT 'disease' as type, CONCAT(dr.disease_name, ' - ', l.animal_id) as title, u.username as description, dr.date_reported as date_time
                 FROM disease_reports dr JOIN livestock l ON dr.livestock_id = l.id JOIN users u ON l.owner_id = u.id
                 ORDER BY dr.date_reported DESC LIMIT 3)
                ORDER BY date_time DESC LIMIT ?
            ");
            $stmt->execute([$limit]);
        }
        
        $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'activity' => $activity
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function getVaccinationAlerts() {
    global $pdo;
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'];
    
    try {
        $daysAhead = $_GET['days'] ?? 30;
        
        if ($userRole === 'farmer') {
            $stmt = $pdo->prepare("
                SELECT l.animal_id, v.type, v.due_date, 
                       DATEDIFF(v.due_date, CURDATE()) as days_until_due
                FROM vaccinations v 
                JOIN livestock l ON v.livestock_id = l.id 
                WHERE l.owner_id = ? 
                AND v.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY v.due_date ASC
                LIMIT 10
            ");
            $stmt->execute([$userId, $daysAhead]);
            
        } else {
            $stmt = $pdo->prepare("
                SELECT l.animal_id, v.type, v.due_date, u.username as owner,
                       DATEDIFF(v.due_date, CURDATE()) as days_until_due
                FROM vaccinations v 
                JOIN livestock l ON v.livestock_id = l.id 
                JOIN users u ON l.owner_id = u.id
                WHERE v.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY v.due_date ASC
                LIMIT 20
            ");
            $stmt->execute([$daysAhead]);
        }
        
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'alerts' => $alerts
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// Helper function to get livestock species distribution
function getSpeciesDistribution() {
    global $pdo;
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'];
    
    try {
        if ($userRole === 'farmer') {
            $stmt = $pdo->prepare("SELECT species, COUNT(*) as count FROM livestock WHERE owner_id = ? GROUP BY species");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->prepare("SELECT species, COUNT(*) as count FROM livestock GROUP BY species");
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        return [];
    }
}

// Helper function to get health summary
function getHealthSummary() {
    global $pdo;
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'];
    
    try {
        $summary = [];
        
        if ($userRole === 'farmer') {
            // Get farmer-specific health summary
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT l.id) as total_animals,
                    COUNT(DISTINCT v.livestock_id) as vaccinated_animals,
                    COUNT(DISTINCT dr.livestock_id) as animals_with_issues
                FROM livestock l
                LEFT JOIN vaccinations v ON l.id = v.livestock_id AND v.date_administered >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
                LEFT JOIN disease_reports dr ON l.id = dr.livestock_id AND dr.date_reported >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                WHERE l.owner_id = ?
            ");
            $stmt->execute([$userId]);
            
        } else {
            // Get system-wide health summary
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT l.id) as total_animals,
                    COUNT(DISTINCT v.livestock_id) as vaccinated_animals,
                    COUNT(DISTINCT dr.livestock_id) as animals_with_issues
                FROM livestock l
                LEFT JOIN vaccinations v ON l.id = v.livestock_id AND v.date_administered >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
                LEFT JOIN disease_reports dr ON l.id = dr.livestock_id AND dr.date_reported >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            ");
            $stmt->execute();
        }
        
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate percentages
        if ($summary['total_animals'] > 0) {
            $summary['vaccination_rate'] = round(($summary['vaccinated_animals'] / $summary['total_animals']) * 100);
            $summary['health_rate'] = round((($summary['total_animals'] - $summary['animals_with_issues']) / $summary['total_animals']) * 100);
        } else {
            $summary['vaccination_rate'] = 0;
            $summary['health_rate'] = 100;
        }
        
        return $summary;
        
    } catch (PDOException $e) {
        return [
            'total_animals' => 0,
            'vaccinated_animals' => 0,
            'animals_with_issues' => 0,
            'vaccination_rate' => 0,
            'health_rate' => 100
        ];
    }
}
?>