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
        getLivestock();
        break;
    case 'POST':
        addLivestock();
        break;
    case 'PUT':
        updateLivestock();
        break;
    case 'DELETE':
        deleteLivestock();
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function getLivestock() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    try {
        // Admins and vets can see all livestock, farmers only see their own
        if ($role === 'admin' || $role === 'vet' || $role === 'extension_officer') {
            $stmt = $pdo->prepare("
                SELECT l.*, u.username as owner_name 
                FROM livestock l 
                JOIN users u ON l.owner_id = u.id 
                ORDER BY l.id DESC
            ");
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("
                SELECT l.*, u.username as owner_name 
                FROM livestock l 
                JOIN users u ON l.owner_id = u.id 
                WHERE l.owner_id = ? 
                ORDER BY l.id DESC
            ");
            $stmt->execute([$userId]);
        }
        
        $livestock = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $livestock
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function addLivestock() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    
    // Get JSON input or form data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Validation
    $required_fields = ['animal_id', 'species', 'breed', 'birth_date', 'gender'];
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
        // Check if animal_id already exists for this user
        $stmt = $pdo->prepare("SELECT id FROM livestock WHERE animal_id = ? AND owner_id = ?");
        $stmt->execute([$input['animal_id'], $userId]);
        
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Animal ID already exists for your account'
            ]);
            return;
        }
        
        // Insert new livestock
        $stmt = $pdo->prepare("
            INSERT INTO livestock (animal_id, species, breed, birth_date, gender, color, weight, location, notes, owner_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $input['animal_id'],
            $input['species'],
            $input['breed'],
            $input['birth_date'],
            $input['gender'],
            $input['color'] ?? null,
            $input['weight'] ?? null,
            $input['location'] ?? null,
            $input['notes'] ?? null,
            $userId
        ]);
        
        $livestockId = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Livestock registered successfully',
            'livestock_id' => $livestockId
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function updateLivestock() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Livestock ID is required']);
        return;
    }
    
    try {
        // Check ownership (unless admin/vet)
        if ($role === 'farmer') {
            $stmt = $pdo->prepare("SELECT id FROM livestock WHERE id = ? AND owner_id = ?");
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
        
        $allowedFields = ['animal_id', 'species', 'breed', 'birth_date', 'gender', 'color', 'weight', 'location', 'notes'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
            return;
        }
        
        $updateValues[] = $input['id'];
        
        $stmt = $pdo->prepare("UPDATE livestock SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?");
        $stmt->execute($updateValues);
        
        echo json_encode(['success' => true, 'message' => 'Livestock updated successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteLivestock() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    $livestockId = $input['id'] ?? $_GET['id'] ?? null;
    
    if (!$livestockId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Livestock ID is required']);
        return;
    }
    
    try {
        // Check ownership (unless admin)
        if ($role !== 'admin') {
            $stmt = $pdo->prepare("SELECT id FROM livestock WHERE id = ? AND owner_id = ?");
            $stmt->execute([$livestockId, $userId]);
            
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
        }
        
        // Delete related records first (vaccinations, disease reports)
        $stmt = $pdo->prepare("DELETE FROM vaccinations WHERE livestock_id = ?");
        $stmt->execute([$livestockId]);
        
        $stmt = $pdo->prepare("DELETE FROM disease_reports WHERE livestock_id = ?");
        $stmt->execute([$livestockId]);
        
        // Delete livestock
        $stmt = $pdo->prepare("DELETE FROM livestock WHERE id = ?");
        $stmt->execute([$livestockId]);
        
        echo json_encode(['success' => true, 'message' => 'Livestock deleted successfully']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>