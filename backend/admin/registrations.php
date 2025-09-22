<?php
/**
 * Admin Registration Management API
 * ການຈັດການການລົງທະບຽນສຳລັບ Admin
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/user_utils.php';

// Handle CORS
handleCORS();

try {
    $pdo = getPDOConnection();
    
    // Require authentication
    $authData = requireAuth();
    
    // Check admin/staff permissions
    $userAuth = new UserAuth();
    if (!$userAuth->hasPermission($authData['role'], 'staff')) {
        sendJsonResponse([
            'error' => 'ທ່ານບໍ່ມີສິດໃນການເຂົ້າເຖິງຂໍ້ມູນນີ້',
            'success' => false
        ], 403);
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRegistrations($pdo, $authData);
            break;
            
        case 'PUT':
            handleUpdateRegistration($pdo, $authData, $userAuth);
            break;
            
        case 'DELETE':
            handleDeleteRegistration($pdo, $authData, $userAuth);
            break;
            
        default:
            sendJsonResponse(['error' => 'Method ບໍ່ຖືກຕ້ອງ', 'success' => false], 405);
    }
    
} catch (Exception $e) {
    error_log("Admin API error: " . $e->getMessage());
    sendJsonResponse([
        'error' => 'ເກີດຂໍ້ຜິດພາດໃນລະບົບ',
        'success' => false
    ], 500);
}

/**
 * Get registrations with pagination and filtering
 */
function handleGetRegistrations($pdo, $authData) {
    try {
        // Get query parameters
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = max(1, min(100, intval($_GET['limit'] ?? 20)));
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        $id = intval($_GET['id'] ?? 0);
        
        $offset = ($page - 1) * $limit;
        
        // Build WHERE conditions
        $where_conditions = [];
        $params = [];
        
        // Filter by specific ID (for detail view)
        if ($id > 0) {
            $where_conditions[] = 'id = ?';
            $params[] = $id;
        }
        
        if (!empty($status)) {
            $where_conditions[] = 'status = ?';
            $params[] = $status;
        }
        
        if (!empty($search)) {
            $where_conditions[] = '(first_name LIKE ? OR last_name LIKE ? OR student_code LIKE ? OR email LIKE ?)';
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($date_from)) {
            $where_conditions[] = 'DATE(registration_date) >= ?';
            $params[] = $date_from;
        }
        
        if (!empty($date_to)) {
            $where_conditions[] = 'DATE(registration_date) <= ?';
            $params[] = $date_to;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM registrations {$where_clause}";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];
        
        // Get registrations
        $sql = "
            SELECT 
                id, student_code, first_name, last_name, gender, date_of_birth,
                phone, email, payment_amount, major, graduation_year,
                profile_image, payment_proof, status, registration_date,
                notes, created_at, updated_at
            FROM registrations 
            {$where_clause}
            ORDER BY registration_date DESC 
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $registrations = $stmt->fetchAll();
        
        // Add formatted data
        $status_messages = [
            'pending' => 'ກຳລັງລໍຖ້າການອະນຸມັດ',
            'approved' => 'ອະນຸມັດແລ້ວ',
            'rejected' => 'ຖືກປະຕິເສດ',
            'certificate_issued' => 'ອອກໃບປະກາດນິຍະບັດແລ້ວ'
        ];
        
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
        
        // Get summary statistics
        $statsSql = "
            SELECT 
                status,
                COUNT(*) as count
            FROM registrations 
            GROUP BY status
        ";
        $stmt = $pdo->query($statsSql);
        $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        sendJsonResponse([
            'success' => true,
            'registrations' => $registrations,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'per_page' => $limit
            ],
            'statistics' => $stats
        ], 200);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Update registration status
 */
function handleUpdateRegistration($pdo, $authData, $userAuth) {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            sendJsonResponse(['error' => 'JSON format ບໍ່ຖືກຕ້ອງ', 'success' => false], 400);
        }
        
        $registration_id = intval($input['id'] ?? 0);
        $new_status = $input['status'] ?? '';
        $notes = $input['notes'] ?? '';
        
        if (!$registration_id) {
            sendJsonResponse(['error' => 'ບໍ່ມີ ID ການລົງທະບຽນ', 'success' => false], 400);
        }
        
        if (!in_array($new_status, ['pending', 'approved', 'rejected', 'certificate_issued'])) {
            sendJsonResponse(['error' => 'ສະຖານະບໍ່ຖືກຕ້ອງ', 'success' => false], 400);
        }
        
        // Check permissions for certificate issuance
        if ($new_status === 'certificate_issued' && !$userAuth->hasPermission($authData['role'], 'admin')) {
            sendJsonResponse([
                'error' => 'ເຈົ້າບໍ່ມີສິດໃນການອອກໃບປະກາດນິຍະບັດ',
                'success' => false
            ], 403);
        }
        
        // Get current registration
        $stmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ?");
        $stmt->execute([$registration_id]);
        $registration = $stmt->fetch();
        
        if (!$registration) {
            sendJsonResponse(['error' => 'ບໍ່ພົບການລົງທະບຽນ', 'success' => false], 404);
        }
        
        // Update registration
        $updateFields = ['status = ?', 'notes = ?', 'updated_at = NOW()'];
        $params = [$new_status, $notes];
        
        $params[] = $registration_id;
        
        $sql = "UPDATE registrations SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Log activity
        logActivity($pdo, 'registration_status_updated', [
            'registration_id' => $registration_id,
            'student_code' => $registration['student_code'],
            'old_status' => $registration['status'],
            'new_status' => $new_status,
            'updated_by' => $authData['user_id']
        ]);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'ປັບປຸງສະຖານະສຳເລັດ'
        ], 200);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Delete registration (admin only)
 */
function handleDeleteRegistration($pdo, $authData, $userAuth) {
    try {
        // Only admin can delete
        if (!$userAuth->hasPermission($authData['role'], 'admin')) {
            sendJsonResponse([
                'error' => 'ເຈົ້າບໍ່ມີສິດໃນການລຶບຂໍ້ມູນ',
                'success' => false
            ], 403);
        }
        
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        $registration_id = intval($input['id'] ?? 0);
        
        if (!$registration_id) {
            sendJsonResponse(['error' => 'ບໍ່ມີ ID ການລົງທະບຽນ', 'success' => false], 400);
        }
        
        // Get registration for logging
        $stmt = $pdo->prepare("SELECT student_code, profile_image, payment_proof FROM registrations WHERE id = ?");
        $stmt->execute([$registration_id]);
        $registration = $stmt->fetch();
        
        if (!$registration) {
            sendJsonResponse(['error' => 'ບໍ່ພົບການລົງທະບຽນ', 'success' => false], 404);
        }
        
        // Delete files if they exist
        if ($registration['profile_image']) {
            $profile_path = __DIR__ . '/../../' . $registration['profile_image'];
            if (file_exists($profile_path)) {
                unlink($profile_path);
            }
        }
        
        if ($registration['payment_proof']) {
            $payment_path = __DIR__ . '/../../' . $registration['payment_proof'];
            if (file_exists($payment_path)) {
                unlink($payment_path);
            }
        }
        
        // Delete registration
        $stmt = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
        $stmt->execute([$registration_id]);
        
        // Log activity
        logActivity($pdo, 'registration_deleted', [
            'registration_id' => $registration_id,
            'student_code' => $registration['student_code'],
            'deleted_by' => $authData['user_id']
        ]);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'ລຶບການລົງທະບຽນສຳເລັດ'
        ], 200);
        
    } catch (Exception $e) {
        throw $e;
    }
}
?>