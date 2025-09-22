<?php
/**
 * Login API Endpoint
 * ການເຂົ້າສູ່ລະບົບ
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
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['error' => 'JSON format ບໍ່ຖືກຕ້ອງ', 'success' => false], 400);
    }
    
    // Validate input
    if (empty($input['username']) || empty($input['password'])) {
        sendJsonResponse([
            'error' => 'ກະລຸນາໃສ່ຊື່ຜູ້ໃຊ້ ແລະ ລະຫັດຜ່ານ',
            'success' => false
        ], 400);
    }
    
    $username = sanitizeInput($input['username']);
    $password = $input['password'];
    
    // Attempt login
    $userAuth = new UserAuth();
    $result = $userAuth->login($username, $password);
    
    if ($result['success']) {
        sendJsonResponse($result, 200);
    } else {
        sendJsonResponse($result, 401);
    }
    
} catch (Exception $e) {
    error_log("Login API error: " . $e->getMessage());
    sendJsonResponse([
        'error' => 'ເກີດຂໍ້ຜິດພາດໃນລະບົບ',
        'success' => false
    ], 500);
}
?>