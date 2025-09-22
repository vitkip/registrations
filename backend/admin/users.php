<?php
/**
 * Admin Users Management API
 * ການຈັດການຜູ້ໃຊ້ສຳລັບ Admin
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/user_utils.php';

// Handle CORS
handleCORS();

try {
    $pdo = getPDOConnection();
    
    // Require authentication
    $authData = requireAuth();
    
    // Check admin permissions
    $userAuth = new UserAuth();
    if (!$userAuth->hasPermission($authData['role'], 'admin')) {
        sendJsonResponse([
            'error' => 'ທ່ານບໍ່ມີສິດໃນການຈັດການຜູ້ໃຊ້',
            'success' => false
        ], 403);
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetUsers($userAuth);
            break;
            
        case 'POST':
            handleCreateUser($userAuth, $authData);
            break;
            
        case 'PUT':
            handleUpdateUser($userAuth, $authData);
            break;
            
        case 'DELETE':
            handleDeleteUser($pdo, $authData);
            break;
            
        default:
            sendJsonResponse(['error' => 'Method ບໍ່ຖືກຕ້ອງ', 'success' => false], 405);
    }
    
} catch (Exception $e) {
    error_log("Admin Users API error: " . $e->getMessage());
    sendJsonResponse([
        'error' => 'ເກີດຂໍ້ຜິດພາດໃນລະບົບ',
        'success' => false
    ], 500);
}

/**
 * Get all users
 */
function handleGetUsers($userAuth) {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
    $search = $_GET['search'] ?? '';
    
    $result = $userAuth->getAllUsers($page, $limit, $search);
    
    if ($result['success']) {
        sendJsonResponse($result, 200);
    } else {
        sendJsonResponse($result, 500);
    }
}

/**
 * Create new user
 */
function handleCreateUser($userAuth, $authData) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['error' => 'JSON format ບໍ່ຖືກຕ້ອງ', 'success' => false], 400);
    }
    
    // Validate input
    $required = ['username', 'password', 'full_name', 'email', 'role'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            sendJsonResponse([
                'error' => "ກະລຸນາໃສ່ {$field}",
                'success' => false
            ], 400);
        }
    }
    
    // Validate role
    if (!in_array($input['role'], ['admin', 'staff', 'viewer'])) {
        sendJsonResponse([
            'error' => 'ບົດບາດບໍ່ຖືກຕ້ອງ',
            'success' => false
        ], 400);
    }
    
    $result = $userAuth->createUser($input);
    
    if ($result['success']) {
        // Log activity
        $pdo = getPDOConnection();
        logActivity($pdo, 'user_created_by_admin', [
            'created_user_id' => $result['user_id'],
            'created_by' => $authData['user_id'],
            'role' => $input['role']
        ]);
        
        sendJsonResponse($result, 201);
    } else {
        sendJsonResponse($result, 400);
    }
}

/**
 * Update user
 */
function handleUpdateUser($userAuth, $authData) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['error' => 'JSON format ບໍ່ຖືກຕ້ອງ', 'success' => false], 400);
    }
    
    $user_id = intval($input['id'] ?? 0);
    
    if (!$user_id) {
        sendJsonResponse(['error' => 'ບໍ່ມີ ID ຜູ້ໃຊ້', 'success' => false], 400);
    }
    
    // Prevent self-deactivation
    if ($user_id === $authData['user_id'] && isset($input['status']) && $input['status'] !== 'active') {
        sendJsonResponse([
            'error' => 'ບໍ່ສາມາດປິດບັນຊີຕົນເອງໄດ້',
            'success' => false
        ], 400);
    }
    
    // Validate role if provided
    if (!empty($input['role']) && !in_array($input['role'], ['admin', 'staff', 'viewer'])) {
        sendJsonResponse([
            'error' => 'ບົດບາດບໍ່ຖືກຕ້ອງ',
            'success' => false
        ], 400);
    }
    
    $result = $userAuth->updateUser($user_id, $input);
    
    if ($result['success']) {
        // Log activity
        $pdo = getPDOConnection();
        logActivity($pdo, 'user_updated_by_admin', [
            'updated_user_id' => $user_id,
            'updated_by' => $authData['user_id'],
            'updated_fields' => array_keys($input)
        ]);
        
        sendJsonResponse($result, 200);
    } else {
        sendJsonResponse($result, 400);
    }
}

/**
 * Delete user
 */
function handleDeleteUser($pdo, $authData) {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $user_id = intval($input['id'] ?? 0);
    
    if (!$user_id) {
        sendJsonResponse(['error' => 'ບໍ່ມີ ID ຜູ້ໃຊ້', 'success' => false], 400);
    }
    
    // Prevent self-deletion
    if ($user_id === $authData['user_id']) {
        sendJsonResponse([
            'error' => 'ບໍ່ສາມາດລຶບບັນຊີຕົນເອງໄດ້',
            'success' => false
        ], 400);
    }
    
    try {
        // Get user info for logging
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendJsonResponse(['error' => 'ບໍ່ພົບຜູ້ໃຊ້ນີ້', 'success' => false], 404);
        }
        
        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Log activity
        logActivity($pdo, 'user_deleted_by_admin', [
            'deleted_user_id' => $user_id,
            'deleted_username' => $user['username'],
            'deleted_by' => $authData['user_id']
        ]);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'ລຶບຜູ້ໃຊ້ສຳເລັດ'
        ], 200);
        
    } catch (Exception $e) {
        error_log("Delete user error: " . $e->getMessage());
        sendJsonResponse([
            'error' => 'ເກີດຂໍ້ຜິດພາດໃນການລຶບຜູ້ໃຊ້',
            'success' => false
        ], 500);
    }
}
?>