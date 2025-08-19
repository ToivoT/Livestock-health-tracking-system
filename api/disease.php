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

switch ($method) {
    case 'GET':
        getDiseaseReports();
        break;
    case 'POST':
        addDiseaseReport();
        break;
    case 'PUT':
        updateDiseaseReport();
        break;
    case 'DELETE':
        deleteDiseaseReport();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getDiseaseReports() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    $action = $_GET['action'] ?? '';
    
    try {
        if ($action === 'statistics') {
            // Get disease statistics
            if ($role === 'farmer') {
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_reports,
                        COUNT(DISTINCT livestock_id) as affected_animals,
                        COUNT(DISTINCT disease_name) as disease_types,
                        COUNT(CASE WHEN date_reported >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as recent_reports
                    FROM disease_reports dr
                    JOIN livestock l ON dr.livestock_id = l.id
                    WHERE l.owner_id = ?
                ");
                $stmt->execute([$userId]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_reports,
                        COUNT(DISTINCT livestock_id) as affected_animals,
                        COUNT(DISTINCT disease_name) as disease_types,
                        COUNT(CASE WHEN date_reported >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as recent_reports
                    FROM disease_reports
                ");
                $stmt->execute();
            }
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'statistics' => $stats
            ]);
            return;
        }
        
        if ($action === 'common_diseases') {
            // Get most common diseases
            if ($role === 'farmer') {
                $stmt = $pdo->prepare("
                    SELECT disease_name, COUNT(*) as count
                    FROM disease_reports dr
                    JOIN livestock l ON dr.livestock_id = l.id
                    WHERE l.owner_id = ?
                    GROUP BY disease_name
                    ORDER BY count DESC
                    LIMIT 10
                ");
                $stmt->execute([$userId]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT disease_name, COUNT(*) as count
                    FROM disease_reports
                    GROUP BY disease_name
                    ORDER BY count DESC
                    LIMIT 10
                ");
                $stmt->execute();
            }
            
            $diseases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'common_diseases' => $diseases
            ]);
            return;
        }
        
        // Default: Get all disease reports with pagination
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $offset = ($page - 1) * $limit;
        
        $livestock_id = $_GET['livestock_id'] ?? null;
        $date_from = $_GET['date_from'] ?? null;
        $date_to = $_GET['date_to'] ?? null;
        
        // Build query conditions
        $whereConditions = [];
        $params = [];
        
        if ($role === 'farmer') {
            $whereConditions[] = "l.owner_id = ?";
            $params[] = $userId;
        }
        
        if ($livestock_id) {
            $whereConditions[] = "dr.livestock_id = ?";
            $params[] = $livestock_id;
        }
        
        if ($date_from) {
            $whereConditions[] = "dr.date_reported >= ?";
            $params[] = $date_from;
        }
        
        if ($date_to) {
            $whereConditions[] = "dr.date_reported <= ?";
            $params[] = $date_to;
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Get disease reports
        if ($role === 'farmer') {
            $query = "
                SELECT dr.*, l.animal_id, l.species, l.breed,
                       DATEDIFF(CURDATE(), dr.date_reported) as days_ago
                FROM disease_reports dr
                JOIN livestock l ON dr.livestock_id = l.id
                $whereClause
                ORDER BY dr.date_reported DESC
                LIMIT $limit OFFSET $offset
            ";
        } else {
            $query = "
                SELECT dr.*, l.animal_id, l.species, l.breed, u.username as owner_name,
                       DATEDIFF(CURDATE(), dr.date_reported) as days_ago
                FROM disease_reports dr
                JOIN livestock l ON dr.livestock_id = l.id
                JOIN users u ON l.owner_id = u.id
                $whereClause
                ORDER BY dr.date_reported DESC
                LIMIT $limit OFFSET $offset
            ";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "
            SELECT COUNT(*)
            FROM disease_reports dr
            JOIN livestock l ON dr.livestock_id = l.id
        ";
        
        if ($role !== 'farmer') {
            $countQuery .= " JOIN users u ON l.owner_id = u.id";
        }
        
        $countQuery .= " $whereClause";
        
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'data' => $reports,
            'pagination' => [
                'current_page' => (int)$page,
                'total_pages' => ceil($totalCount / $limit),
                'total_records' => (int)$totalCount,
                'per_page' => (int)$limit
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function addDiseaseReport() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Validation
    $required_fields = ['livestock_id', 'disease_name', 'date_reported'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Field '$field' is required"
            ]);
            return;
        }
    }
    
    try {
        // Check permission
        if ($role === 'farmer') {
            $stmt = $pdo->prepare("SELECT id FROM livestock WHERE id = ? AND owner_id = ?");
            $stmt->execute([$input['livestock_id'], $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
        } else {
            // Verify livestock exists
            $stmt = $pdo->prepare("SELECT id FROM livestock WHERE id = ?");
            $stmt->execute([$input['livestock_id']]);
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Livestock not found']);
                return;
            }
        }
        
        // Validate date
        $reportDate = new DateTime($input['date_reported']);
        if ($reportDate > new DateTime()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Report date cannot be in the future']);
            return;
        }
        
        // Insert disease report
        $stmt = $pdo->prepare("
            INSERT INTO disease_reports (livestock_id, disease_name, date_reported, symptoms)
            VALUES (?, ?, ?, ?)
        ");
        
        $symptoms = !empty($input['symptoms']) ? $input['symptoms'] : null;
        
        $stmt->execute([
            $input['livestock_id'],
            $input['disease_name'],
            $input['date_reported'],
            $symptoms
        ]);
        
        $reportId = $pdo->lastInsertId();
        
        // Send alert to veterinarians (in a real system, this would send notifications)
        if ($role === 'farmer') {
            // Log alert for vets
            error_log("DISEASE ALERT: New disease report for livestock ID " . $input['livestock_id'] . " - " . $input['disease_name']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Disease report submitted successfully',
            'report_id' => $reportId
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function updateDiseaseReport() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Report ID is required']);
        return;
    }
    
    try {
        // Check permission
        if ($role === 'farmer') {
            $stmt = $pdo->prepare("
                SELECT dr.id FROM disease_reports dr
                JOIN livestock l ON dr.livestock_id = l.id
                WHERE dr.id = ? AND l.owner_id = ?
            ");
            $stmt->execute([$input['id'], $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
        }
        
        // Build update query
        $updateFields = [];
        $updateValues = [];
        
        $allowedFields = ['disease_name', 'date_reported', 'symptoms'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $updateValues[] = !empty($input[$field]) ? $input[$field] : null;
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
            return;
        }
        
        $updateValues[] = $input['id'];
        
        $stmt = $pdo->prepare("UPDATE disease_reports SET " . implode(', ', $updateFields) . " WHERE id = ?");
        $stmt->execute($updateValues);
        
        echo json_encode(['success' => true, 'message' => 'Disease report updated successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteDiseaseReport() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    $reportId = $input['id'] ?? $_GET['id'] ?? null;
    
    if (!$reportId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Report ID is required']);
        return;
    }
    
    try {
        // Check permission
        if ($role === 'farmer') {
            $stmt = $pdo->prepare("
                SELECT dr.id FROM disease_reports dr
                JOIN livestock l ON dr.livestock_id = l.id
                WHERE dr.id = ? AND l.owner_id = ?
            ");
            $stmt->execute([$reportId, $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
        }
        
        // Delete report
        $stmt = $pdo->prepare("DELETE FROM disease_reports WHERE id = ?");
        $stmt->execute([$reportId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Disease report deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Report not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Common livestock diseases in Namibia
function getCommonDiseases() {
    return [
        'Cattle' => [
            'Foot and Mouth Disease',
            'Anthrax',
            'Lumpy Skin Disease',
            'Black Quarter',
            'Brucellosis',
            'Mastitis',
            'Pneumonia',
            'Diarrhea',
            'Eye Infection',
            'Skin Parasites'
        ],
        'Sheep' => [
            'Blue Tongue',
            'Sheep Pox',
            'Pasteurellosis',
            'Internal Parasites',
            'External Parasites',
            'Foot Rot',
            'Pneumonia'
        ],
        'Goat' => [
            'Goat Pox',
            'Pasteurellosis',
            'Internal Parasites',
            'External Parasites',
            'Pneumonia',
            'Diarrhea',
            'Foot Rot'
        ],
        'Chicken' => [
            'Newcastle Disease',
            'Fowl Pox',
            'Avian Influenza',
            'Coccidiosis',
            'Infectious Bronchitis',
            'Mareks Disease'
        ],
        'Pig' => [
            'African Swine Fever',
            'Classical Swine Fever',
            'Foot and Mouth Disease',
            'Pneumonia',
            'Diarrhea',
            'Skin Conditions'
        ]
    ];
}
?>