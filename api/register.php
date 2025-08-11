<?php
session_start();
require '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        registerUser();
        break;
    case 'GET':
        if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
            getUsers();
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        }
        break;
    case 'PUT':
        if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
            updateUser();
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        }
        break;
    case 'DELETE':
        if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
            deleteUser();
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

function registerUser() {
    global $pdo;
    
    // Get JSON input or form data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Validation
    $required_fields = ['username', 'password', 'confirm_password', 'role', 'full_name', 'email'];
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
    
    // Validate role
    $allowed_roles = ['farmer', 'vet', 'extension_officer'];
    
    // Only allow admin registration if current user is admin
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $allowed_roles[] = 'admin';
    }
    
    if (!in_array($input['role'], $allowed_roles)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid role selected'
        ]);
        return;
    }
    
    // Password validation
    if ($input['password'] !== $input['confirm_password']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Passwords do not match'
        ]);
        return;
    }
    
    if (strlen($input['password']) < 6) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Password must be at least 6 characters long'
        ]);
        return;
    }
    
    // Email validation
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address'
        ]);
        return;
    }
    
    // Phone validation (optional but if provided, should be valid)
    if (!empty($input['phone']) && !preg_match('/^[\+]?[0-9\s\-\(\)]{8,15}$/', $input['phone'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid phone number format'
        ]);
        return;
    }
    
    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$input['username']]);
        
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Username already exists'
            ]);
            return;
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$input['email']]);
        
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Email already registered'
            ]);
            return;
        }
        
        // Hash password
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role, email, phone, full_name, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $input['username'],
            $hashedPassword,
            $input['role'],
            $input['email'],
            $input['phone'] ?? null,
            $input['full_name']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // If this is a self-registration (not admin creating user), log them in
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['role'] = $input['role'];
            $_SESSION['username'] = $input['username'];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'User registered successfully',
            'user_id' => $userId,
            'auto_login' => !isset($_SESSION['user_id'])
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function getUsers() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, username, role, email, phone, full_name, created_at, 
                   (SELECT COUNT(*) FROM livestock WHERE owner_id = users.id) as livestock_count
            FROM users 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $users
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function updateUser() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$input['id']]);
        
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        // Build dynamic update query
        $updateFields = [];
        $updateValues = [];
        
        $allowedFields = ['username', 'role', 'email', 'phone', 'full_name'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field]) && $input[$field] !== '') {
                // Additional validation for specific fields
                if ($field === 'email' && !filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                    return;
                }
                
                if ($field === 'role' && !in_array($input[$field], ['farmer', 'vet', 'extension_officer', 'admin'])) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid role']);
                    return;
                }
                
                $updateFields[] = "$field = ?";
                $updateValues[] = $input[$field];
            }
        }
        
        // Handle password update separately
        if (!empty($input['new_password'])) {
            if (strlen($input['new_password']) < 6) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
                return;
            }
            $updateFields[] = "password = ?";
            $updateValues[] = password_hash($input['new_password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
            return;
        }
        
        $updateValues[] = $input['id'];
        
        $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?");
        $stmt->execute($updateValues);
        
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint violation
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

function deleteUser() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['id'] ?? $_GET['id'] ?? null;
    
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    // Prevent self-deletion
    if ($userId == $_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        return;
    }
    
    try {
        // Check if user has livestock
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM livestock WHERE owner_id = ?");
        $stmt->execute([$userId]);
        $livestockCount = $stmt->fetchColumn();
        
        if ($livestockCount > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false, 
                'message' => "Cannot delete user with $livestockCount livestock records. Transfer or delete livestock first."
            ]);
            return;
        }
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>