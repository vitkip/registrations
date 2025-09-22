<?php
/**
 * Logout API Endpoint
 * ການອອກຈາກລະບົບ
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
    
    // Log activity
    $pdo = getPDOConnection();
    logActivity($pdo, 'user_logout', [
        'user_id' => $authData['user_id'],
        'username' => $authData['username']
    ]);
    
    // In a more sophisticated system, you might add the token to a blacklist
    // For now, we'll just return success and let the client handle token removal
    
    sendJsonResponse([
        'success' => true,
        'message' => 'ອອກຈາກລະບົບສຳເລັດ'
    ], 200);
    
} catch (Exception $e) {
    error_log("Logout API error: " . $e->getMessage());
    sendJsonResponse([
        'error' => 'ເກີດຂໍ້ຜິດພາດໃນລະບົບ',
        'success' => false
    ], 500);
}
?>