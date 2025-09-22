<?php
/**
 * Token Verification API Endpoint
 * ການກວດສອບ Token
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/user_utils.php';

// Handle CORS
handleCORS();

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['error' => 'ອະນຸຍາດແຕ່ POST method ເທົ່ານັ້ນ', 'success' => false], 405);
}

try {
    // Get and verify token
    $authData = requireAuth();
    
    // Get fresh user data
    $userAuth = new UserAuth();
    $user = $userAuth->getUserById($authData['user_id']);
    
    if (!$user) {
        sendJsonResponse(['error' => 'ບໍ່ພົບຜູ້ໃຊ້ນີ້ໃນລະບົບ', 'success' => false], 404);
    }
    
    if ($user['status'] !== 'active') {
        sendJsonResponse(['error' => 'ບັນຊີຜູ້ໃຊ້ຖືກປິດການນໍາໃຊ້', 'success' => false], 403);
    }
    
    // Return user data
    sendJsonResponse([
        'success' => true,
        'message' => 'Token ຖືກຕ້ອງ',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'last_login' => $user['last_login']
        ],
        'expires_at' => $authData['exp']
    ], 200);
    
} catch (Exception $e) {
    error_log("Verify token API error: " . $e->getMessage());
    sendJsonResponse([
        'error' => 'ເກີດຂໍ້ຜິດພາດໃນລະບົບ',
        'success' => false
    ], 500);
}
?>