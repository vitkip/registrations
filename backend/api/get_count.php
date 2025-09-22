<?php
/**
 * Registration Count API Endpoint
 * การนับจำนวนการลงทะเบียนทั้งหมด
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
    
    // นับจำนวนการลงทะเบียนทั้งหมด
    $query = "SELECT COUNT(*) as total FROM registrations";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = (int)$result['total'];
    
    // นับจำนวนตามสถานะ (เพิ่มข้อมูลสถิติ)
    $statusQuery = "SELECT 
                        status,
                        COUNT(*) as count
                    FROM registrations 
                    GROUP BY status";
    $statusStmt = $pdo->prepare($statusQuery);
    $statusStmt->execute();
    
    $statusStats = [];
    while ($row = $statusStmt->fetch(PDO::FETCH_ASSOC)) {
        $statusStats[$row['status']] = (int)$row['count'];
    }
    
    // ส่งผลลัพธ์
    sendJsonResponse([
        'success' => true,
        'count' => $count,
        'status_stats' => $statusStats,
        'message' => 'Registration count retrieved successfully'
    ]);
    
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'count' => 0,
        'message' => 'Error retrieving registration count: ' . $e->getMessage()
    ], 500);
}
?>