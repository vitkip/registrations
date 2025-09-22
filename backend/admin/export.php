<?php
/**
 * Export Data API (Excel/PDF)
 * ການສົ່ງອອກຂໍ້ມູນ
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/user_utils.php';

// Handle CORS
handleCORS();

// Only allow GET method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse(['error' => 'ອະນຸຍາດແຕ່ GET method ເທົ່ານັ້ນ', 'success' => false], 405);
}

try {
    $pdo = getPDOConnection();
    
    // Require authentication
    $authData = requireAuth();
    
    // Check admin/staff permissions
    $userAuth = new UserAuth();
    if (!$userAuth->hasPermission($authData['role'], 'staff')) {
        sendJsonResponse([
            'error' => 'ທ່ານບໍ່ມີສິດໃນການສົ່ງອອກຂໍ້ມູນ',
            'success' => false
        ], 403);
    }
    
    $format = $_GET['format'] ?? 'excel';
    $status = $_GET['status'] ?? '';
    $course_type = $_GET['course_type'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    // Build WHERE conditions
    $where_conditions = [];
    $params = [];
    
    if (!empty($status)) {
        $where_conditions[] = 'status = ?';
        $params[] = $status;
    }
    
    // Removed course_type filter as it no longer exists
    
    if (!empty($date_from)) {
        $where_conditions[] = 'DATE(registration_date) >= ?';
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = 'DATE(registration_date) <= ?';
        $params[] = $date_to;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Get registrations
    $sql = "
        SELECT 
            student_code, first_name, last_name, gender, date_of_birth,
            phone, email, major, graduation_year, payment_amount, 
            status, registration_date, created_at, updated_at
        FROM registrations 
        {$where_clause}
        ORDER BY registration_date DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $registrations = $stmt->fetchAll();
    
    if (empty($registrations)) {
        sendJsonResponse([
            'error' => 'ບໍ່ມີຂໍ້ມູນທີ່ຈະສົ່ງອອກ',
            'success' => false
        ], 404);
    }
    
    // Process export based on format
    if ($format === 'excel') {
        exportToExcel($registrations, $authData);
    } elseif ($format === 'csv') {
        exportToCSV($registrations, $authData);
    } else {
        sendJsonResponse([
            'error' => 'ຮູບແບບການສົ່ງອອກບໍ່ຖືກຕ້ອງ',
            'success' => false
        ], 400);
    }
    
} catch (Exception $e) {
    error_log("Export API error: " . $e->getMessage());
    sendJsonResponse([
        'error' => 'ເກີດຂໍ້ຜິດພາດໃນການສົ່ງອອກຂໍ້ມູນ',
        'success' => false
    ], 500);
}

/**
 * Export to Excel (HTML Table format)
 */
function exportToExcel($registrations, $authData) {
    $filename = 'registrations_' . date('Y-m-d_H-i-s') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Log activity
    $pdo = getPDOConnection();
    logActivity($pdo, 'data_export', [
        'format' => 'excel',
        'record_count' => count($registrations),
        'exported_by' => $authData['user_id']
    ]);
    
    echo '<html><head><meta charset="utf-8"></head><body>';
    echo '<table border="1">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ລະຫັດນັກສຶກສາ</th>';
    echo '<th>ຊື່</th>';
    echo '<th>ນາມສະກຸນ</th>';
    echo '<th>ເພດ</th>';
    echo '<th>ວັນເກີດ</th>';
    echo '<th>ເບີໂທ</th>';
    echo '<th>ອີເມລ</th>';
    echo '<th>ສາຂາວິຊາ</th>';
    echo '<th>ປີສຳເລັດ</th>';
    echo '<th>ຄ່າທຳນຽມ</th>';
    echo '<th>ສະຖານະ</th>';
    echo '<th>ວັນທີລົງທະບຽນ</th>';
    echo '<th>ວັນທີສ້າງ</th>';
    echo '<th>ວັນທີອັບເດດ</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $status_text = [
        'pending' => 'ລໍຖ້າການອະນຸມັດ',
        'approved' => 'ອະນຸມັດແລ້ວ',
        'rejected' => 'ປະຕິເສດ',
        'certificate_issued' => 'ອອກໃບຢັ້ງຢືນແລ້ວ'
    ];
    
    foreach ($registrations as $reg) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($reg['student_code']) . '</td>';
        echo '<td>' . htmlspecialchars($reg['first_name']) . '</td>';
        echo '<td>' . htmlspecialchars($reg['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($reg['gender']) . '</td>';
        echo '<td>' . htmlspecialchars($reg['date_of_birth']) . '</td>';
        echo '<td>' . htmlspecialchars($reg['phone']) . '</td>';
        echo '<td>' . htmlspecialchars($reg['email']) . '</td>';
        echo '<td>' . htmlspecialchars($reg['major'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($reg['graduation_year'] ?? '') . '</td>';
        echo '<td>' . number_format($reg['payment_amount']) . '</td>';
        echo '<td>' . htmlspecialchars($status_text[$reg['status']] ?? $reg['status']) . '</td>';
        echo '<td>' . ($reg['registration_date'] ? date('d/m/Y H:i', strtotime($reg['registration_date'])) : '') . '</td>';
        echo '<td>' . ($reg['created_at'] ? date('d/m/Y H:i', strtotime($reg['created_at'])) : '') . '</td>';
        echo '<td>' . ($reg['updated_at'] ? date('d/m/Y H:i', strtotime($reg['updated_at'])) : '') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</body></html>';
    exit;
}

/**
 * Export to CSV
 */
function exportToCSV($registrations, $authData) {
    $filename = 'registrations_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    // Log activity
    $pdo = getPDOConnection();
    logActivity($pdo, 'data_export', [
        'format' => 'csv',
        'record_count' => count($registrations),
        'exported_by' => $authData['user_id']
    ]);
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    // Headers
    $headers = [
        'ລະຫັດນັກສຶກສາ', 'ຊື່', 'ນາມສະກຸນ', 'ເພດ', 'ວັນເກີດ', 'ເບີໂທ', 'ອີເມລ',
        'ສາຂາວິຊາ', 'ປີສຳເລັດ', 'ຄ່າທຳນຽມ', 'ສະຖານະ',
        'ວັນທີລົງທະບຽນ', 'ວັນທີສ້າງ', 'ວັນທີອັບເດດ'
    ];
    
    fputcsv($output, $headers);
    
    $status_text = [
        'pending' => 'ລໍຖ້າການອະນຸມັດ',
        'approved' => 'ອະນຸມັດແລ້ວ',
        'rejected' => 'ປະຕິເສດ',
        'certificate_issued' => 'ອອກໃບຢັ້ງຢືນແລ້ວ'
    ];
    
    foreach ($registrations as $reg) {
        $row = [
            $reg['student_code'],
            $reg['first_name'],
            $reg['last_name'],
            $reg['gender'],
            $reg['date_of_birth'],
            $reg['phone'],
            $reg['email'],
            $reg['major'] ?? '',
            $reg['graduation_year'] ?? '',
            $reg['payment_amount'],
            $status_text[$reg['status']] ?? $reg['status'],
            $reg['registration_date'] ? date('d/m/Y H:i', strtotime($reg['registration_date'])) : '',
            $reg['created_at'] ? date('d/m/Y H:i', strtotime($reg['created_at'])) : '',
            $reg['updated_at'] ? date('d/m/Y H:i', strtotime($reg['updated_at'])) : ''
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>