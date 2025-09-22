<?php
/**
 * Check Registration Status API
 * ກວດສອບສະຖານະການລົງທະບຽນ
 */

require_once __DIR__ . '/../config/config.php';

// Handle CORS
handleCORS();

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['error' => 'ອະນຸຍາດແຕ່ GET method ເທົ່ານັ້ນ', 'success' => false], 405);
}

try {
    $pdo = getPDOConnection();
    
    // Get search parameters
    $student_code = $_GET['student_code'] ?? '';
    $email = $_GET['email'] ?? '';
    
    if (empty($student_code) && empty($email)) {
        sendJsonResponse([
            'error' => 'ກະລຸນາໃສ່ລະຫັດນັກສຶກສາ ຫຼື ອີເມລ',
            'success' => false
        ], 400);
    }
    
    // Build query
    $where_conditions = [];
    $params = [];
    
    if (!empty($student_code)) {
        $where_conditions[] = 'student_code = ?';
        $params[] = sanitizeInput($student_code);
    }
    
    if (!empty($email)) {
        $where_conditions[] = 'email = ?';
        $params[] = sanitizeInput($email);
    }
    
    $where_clause = implode(' OR ', $where_conditions);
    
    // Execute query
    $stmt = $pdo->prepare("
        SELECT 
            id, student_code, first_name, last_name, email, phone,
            payment_amount, status, registration_date,
            major, graduation_year, created_at, updated_at
        FROM registrations 
        WHERE {$where_clause}
        ORDER BY registration_date DESC
    ");
    
    $stmt->execute($params);
    $registrations = $stmt->fetchAll();
    
    if (empty($registrations)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'ບໍ່ພົບຂໍ້ມູນການລົງທະບຽນ'
        ], 404);
    }
    
    // Format status messages in Lao
    $status_messages = [
        'pending' => 'ກຳລັງລໍຖ້າການອະນຸມັດ',
        'approved' => 'ອະນຸມັດແລ້ວ',
        'rejected' => 'ຖືກປະຕິເສດ',
        'certificate_issued' => 'ອອກໃບປະກາດນິຍະບັດແລ້ວ'
    ];
    
    // Add formatted data
    foreach ($registrations as &$registration) {
        $registration['status_text'] = $status_messages[$registration['status']] ?? $registration['status'];
        $registration['full_name'] = $registration['first_name'] . ' ' . $registration['last_name'];
        
        // Format dates
        if ($registration['registration_date']) {
            $registration['registration_date_formatted'] = date('d/m/Y H:i', strtotime($registration['registration_date']));
        }
        if ($registration['created_at']) {
            $registration['created_at_formatted'] = date('d/m/Y H:i', strtotime($registration['created_at']));
        }
        if ($registration['updated_at']) {
            $registration['updated_at_formatted'] = date('d/m/Y H:i', strtotime($registration['updated_at']));
        }
    }
    
    sendJsonResponse([
        'success' => true,
        'message' => 'ພົບຂໍ້ມູນການລົງທະບຽນ',
        'registrations' => $registrations
    ], 200);
    
} catch (Exception $e) {
    error_log("Check status API error: " . $e->getMessage());
    sendJsonResponse([
        'error' => 'ເກີດຂໍ້ຜິດພາດໃນລະບົບ',
        'success' => false
    ], 500);
}
?>