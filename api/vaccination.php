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
        getVaccinations();
        break;
    case 'POST':
        addVaccination();
        break;
    case 'PUT':
        updateVaccination();
        break;
    case 'DELETE':
        deleteVaccination();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getVaccinations() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    $action = $_GET['action'] ?? '';
    
    try {
        if ($action === 'livestock_list') {
            // Get livestock list for dropdown
            if ($role === 'farmer') {
                $stmt = $pdo->prepare("
                    SELECT id, animal_id, species, breed 
                    FROM livestock 
                    WHERE owner_id = ? 
                    ORDER BY animal_id
                ");
                $stmt->execute([$userId]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT l.id, l.animal_id, l.species, l.breed, u.username as owner_name
                    FROM livestock l 
                    JOIN users u ON l.owner_id = u.id 
                    ORDER BY l.animal_id
                ");
                $stmt->execute();
            }
            
            $livestock = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'livestock' => $livestock
            ]);
            return;
        }
        
        if ($action === 'alerts') {
            // Get vaccination alerts (due soon or overdue)
            $days = $_GET['days'] ?? 30;
            
            if ($role === 'farmer') {
                $stmt = $pdo->prepare("
                    SELECT v.id, v.type, v.due_date, l.animal_id, l.species,
                           DATEDIFF(v.due_date, CURDATE()) as days_until_due,
                           CASE 
                               WHEN v.due_date < CURDATE() THEN 'overdue'
                               WHEN DATEDIFF(v.due_date, CURDATE()) <= 7 THEN 'urgent'
                               ELSE 'upcoming'
                           END as alert_level
                    FROM vaccinations v 
                    JOIN livestock l ON v.livestock_id = l.id 
                    WHERE l.owner_id = ? 
                    AND v.due_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                    ORDER BY v.due_date ASC
                ");
                $stmt->execute([$userId, $days]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT v.id, v.type, v.due_date, l.animal_id, l.species, u.username as owner,
                           DATEDIFF(v.due_date, CURDATE()) as days_until_due,
                           CASE 
                               WHEN v.due_date < CURDATE() THEN 'overdue'
                               WHEN DATEDIFF(v.due_date, CURDATE()) <= 7 THEN 'urgent'
                               ELSE 'upcoming'
                           END as alert_level
                    FROM vaccinations v 
                    JOIN livestock l ON v.livestock_id = l.id 
                    JOIN users u ON l.owner_id = u.id
                    WHERE v.due_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                    ORDER BY v.due_date ASC
                ");
                $stmt->execute([$days]);
            }
            
            $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'alerts' => $alerts
            ]);
            return;
        }
        
        if ($action === 'schedule') {
            // Get vaccination schedule for calendar view
            $start_date = $_GET['start'] ?? date('Y-m-01');
            $end_date = $_GET['end'] ?? date('Y-m-t');
            
            if ($role === 'farmer') {
                $stmt = $pdo->prepare("
                    SELECT v.id, v.type, v.due_date as date, l.animal_id, l.species,
                           CASE 
                               WHEN v.due_date < CURDATE() THEN 'danger'
                               WHEN DATEDIFF(v.due_date, CURDATE()) <= 7 THEN 'warning'
                               ELSE 'info'
                           END as color
                    FROM vaccinations v 
                    JOIN livestock l ON v.livestock_id = l.id 
                    WHERE l.owner_id = ? 
                    AND v.due_date BETWEEN ? AND ?
                    ORDER BY v.due_date
                ");
                $stmt->execute([$userId, $start_date, $end_date]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT v.id, v.type, v.due_date as date, l.animal_id, l.species, u.username as owner,
                           CASE 
                               WHEN v.due_date < CURDATE() THEN 'danger'
                               WHEN DATEDIFF(v.due_date, CURDATE()) <= 7 THEN 'warning'
                               ELSE 'info'
                           END as color
                    FROM vaccinations v 
                    JOIN livestock l ON v.livestock_id = l.id 
                    JOIN users u ON l.owner_id = u.id
                    WHERE v.due_date BETWEEN ? AND ?
                    ORDER BY v.due_date
                ");
                $stmt->execute([$start_date, $end_date]);
            }
            
            $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'schedule' => $schedule
            ]);
            return;
        }
        
        // Default: Get all vaccinations
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $offset = ($page - 1) * $limit;
        
        $livestock_id = $_GET['livestock_id'] ?? null;
        $status_filter = $_GET['status'] ?? '';
        
        // Base query
        $whereConditions = [];
        $params = [];
        
        if ($role === 'farmer') {
            $whereConditions[] = "l.owner_id = ?";
            $params[] = $userId;
        }
        
        if ($livestock_id) {
            $whereConditions[] = "v.livestock_id = ?";
            $params[] = $livestock_id;
        }
        
        if ($status_filter) {
            switch ($status_filter) {
                case 'overdue':
                    $whereConditions[] = "v.due_date < CURDATE()";
                    break;
                case 'due_soon':
                    $whereConditions[] = "v.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'upcoming':
                    $whereConditions[] = "v.due_date > DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'completed':
                    $whereConditions[] = "v.date_administered IS NOT NULL";
                    break;
            }
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Get vaccinations with pagination
        if ($role === 'farmer') {
            $query = "
                SELECT v.*, l.animal_id, l.species, l.breed,
                       CASE 
                           WHEN v.date_administered IS NOT NULL THEN 'completed'
                           WHEN v.due_date < CURDATE() THEN 'overdue'
                           WHEN DATEDIFF(v.due_date, CURDATE()) <= 7 THEN 'due_soon'
                           ELSE 'scheduled'
                       END as status,
                       DATEDIFF(v.due_date, CURDATE()) as days_until_due
                FROM vaccinations v 
                JOIN livestock l ON v.livestock_id = l.id 
                $whereClause
                ORDER BY v.due_date DESC, v.date_administered DESC
                LIMIT $limit OFFSET $offset
            ";
        } else {
            $query = "
                SELECT v.*, l.animal_id, l.species, l.breed, u.username as owner_name,
                       CASE 
                           WHEN v.date_administered IS NOT NULL THEN 'completed'
                           WHEN v.due_date < CURDATE() THEN 'overdue'
                           WHEN DATEDIFF(v.due_date, CURDATE()) <= 7 THEN 'due_soon'
                           ELSE 'scheduled'
                       END as status,
                       DATEDIFF(v.due_date, CURDATE()) as days_until_due
                FROM vaccinations v 
                JOIN livestock l ON v.livestock_id = l.id 
                JOIN users u ON l.owner_id = u.id
                $whereClause
                ORDER BY v.due_date DESC, v.date_administered DESC
                LIMIT $limit OFFSET $offset
            ";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $vaccinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $countQuery = "
            SELECT COUNT(*) 
            FROM vaccinations v 
            JOIN livestock l ON v.livestock_id = l.id
        ";
        
        if ($role === 'farmer') {
            $countQuery .= " $whereClause";
        } else {
            $countQuery .= " JOIN users u ON l.owner_id = u.id $whereClause";
        }
        
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalCount = $countStmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'data' => $vaccinations,
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

function addVaccination() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Validation
    $required_fields = ['livestock_id', 'type', 'due_date'];
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
        // Check if user has permission to add vaccination for this livestock
        if ($role === 'farmer') {
            $stmt = $pdo->prepare("SELECT id FROM livestock WHERE id = ? AND owner_id = ?");
            $stmt->execute([$input['livestock_id'], $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
        } else {
            // Vets and extension officers can add vaccinations for any livestock
            $stmt = $pdo->prepare("SELECT id FROM livestock WHERE id = ?");
            $stmt->execute([$input['livestock_id']]);
            
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Livestock not found']);
                return;
            }
        }
        
        // Validate dates
        if (!empty($input['date_administered'])) {
            $admin_date = new DateTime($input['date_administered']);
            $due_date = new DateTime($input['due_date']);
            
            if ($admin_date > new DateTime()) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Administration date cannot be in the future']);
                return;
            }
        }
        
        // Calculate next due date if not provided
        $next_due_date = null;
        if (!empty($input['next_due_date'])) {
            $next_due_date = $input['next_due_date'];
        } elseif (!empty($input['date_administered'])) {
            // Auto-calculate next due date based on vaccination type
            $interval_months = getVaccinationInterval($input['type']);
            if ($interval_months > 0) {
                $admin_date = new DateTime($input['date_administered']);
                $admin_date->add(new DateInterval("P{$interval_months}M"));
                $next_due_date = $admin_date->format('Y-m-d');
            }
        }
        
        // FIX: Convert empty strings to NULL for date fields
        $date_administered = !empty($input['date_administered']) ? $input['date_administered'] : null;
        $next_due_date = !empty($next_due_date) ? $next_due_date : null;
        $batch_number = !empty($input['batch_number']) ? $input['batch_number'] : null;
        $notes = !empty($input['notes']) ? $input['notes'] : null;
        
        // Insert vaccination record
        $stmt = $pdo->prepare("
            INSERT INTO vaccinations (livestock_id, type, date_administered, due_date, next_due_date, batch_number, notes, administered_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $input['livestock_id'],
            $input['type'],
            $date_administered,  // Now properly NULL if empty
            $input['due_date'],
            $next_due_date,      // Now properly NULL if empty
            $batch_number,       // Now properly NULL if empty
            $notes,              // Now properly NULL if empty
            $userId
        ]);
        
        $vaccinationId = $pdo->lastInsertId();
        
        // If next due date was calculated, create a follow-up vaccination record
        if ($next_due_date && !empty($input['date_administered'])) {
            $stmt = $pdo->prepare("
                INSERT INTO vaccinations (livestock_id, type, due_date, notes, created_at) 
                VALUES (?, ?, ?, 'Scheduled follow-up vaccination', NOW())
            ");
            
            $stmt->execute([
                $input['livestock_id'],
                $input['type'],
                $next_due_date
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Vaccination recorded successfully',
            'vaccination_id' => $vaccinationId,
            'next_due_date' => $next_due_date
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function updateVaccination() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vaccination ID is required']);
        return;
    }
    
    try {
        // Check if user has permission to update this vaccination
        if ($role === 'farmer') {
            $stmt = $pdo->prepare("
                SELECT v.id FROM vaccinations v 
                JOIN livestock l ON v.livestock_id = l.id 
                WHERE v.id = ? AND l.owner_id = ?
            ");
            $stmt->execute([$input['id'], $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
        }
        
        // Build dynamic update query
        $updateFields = [];
        $updateValues = [];
        
        $allowedFields = ['type', 'date_administered', 'due_date', 'next_due_date', 'batch_number', 'notes'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                // FIX: Convert empty strings to NULL for date and optional fields
                if (in_array($field, ['date_administered', 'next_due_date', 'batch_number', 'notes'])) {
                    $updateValues[] = !empty($input[$field]) ? $input[$field] : null;
                } else {
                    $updateValues[] = $input[$field];
                }
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
            return;
        }
        
        // Add administered_by and updated timestamp if date_administered is being set
        if (isset($input['date_administered']) && !empty($input['date_administered'])) {
            $updateFields[] = "administered_by = ?";
            $updateValues[] = $userId;
        }
        
        $updateFields[] = "updated_at = NOW()";
        $updateValues[] = $input['id'];
        
        $stmt = $pdo->prepare("UPDATE vaccinations SET " . implode(', ', $updateFields) . " WHERE id = ?");
        $stmt->execute($updateValues);
        
        echo json_encode(['success' => true, 'message' => 'Vaccination updated successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteVaccination() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    $vaccinationId = $input['id'] ?? $_GET['id'] ?? null;
    
    if (!$vaccinationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vaccination ID is required']);
        return;
    }
    
    try {
        // Check permissions
        if ($role === 'farmer') {
            $stmt = $pdo->prepare("
                SELECT v.id FROM vaccinations v 
                JOIN livestock l ON v.livestock_id = l.id 
                WHERE v.id = ? AND l.owner_id = ?
            ");
            $stmt->execute([$vaccinationId, $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
        }
        
        // Delete vaccination
        $stmt = $pdo->prepare("DELETE FROM vaccinations WHERE id = ?");
        $stmt->execute([$vaccinationId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Vaccination deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Vaccination not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Helper function to get vaccination interval based on type
function getVaccinationInterval($type) {
    $intervals = [
        'Foot and Mouth Disease' => 6,
        'Anthrax' => 12,
        'Black Quarter' => 12,
        'Lumpy Skin Disease' => 12,
        'Rift Valley Fever' => 36,
        'Brucellosis' => 12,
        'Rabies' => 12,
        'Newcastle Disease' => 6,
        'Fowl Pox' => 12,
        'Avian Influenza' => 6,
        'Swine Fever' => 12,
        'Pasteurellosis' => 6
    ];
    
    return $intervals[$type] ?? 0;
}

// Get common vaccination types
function getVaccinationTypes() {
    return [
        'Cattle' => [
            'Foot and Mouth Disease',
            'Anthrax',
            'Black Quarter',
            'Lumpy Skin Disease',
            'Rift Valley Fever',
            'Brucellosis',
            'Rabies'
        ],
        'Sheep' => [
            'Foot and Mouth Disease',
            'Anthrax',
            'Blue Tongue',
            'Rift Valley Fever',
            'Pasteurellosis'
        ],
        'Goat' => [
            'Foot and Mouth Disease',
            'Anthrax',
            'Pasteurellosis',
            'Rift Valley Fever'
        ],
        'Chicken' => [
            'Newcastle Disease',
            'Fowl Pox',
            'Avian Influenza',
            'Infectious Bronchitis'
        ],
        'Pig' => [
            'Swine Fever',
            'Foot and Mouth Disease',
            'Anthrax'
        ]
    ];
}
?>