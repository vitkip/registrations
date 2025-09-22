<?php
/**
 * Student Registration API Endpoint
 * ການລົງທະບຽນນັກສຶກສາ
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
    $pdo = getPDOConnection();
    
    // Validate required fields
    $required_fields = [
        'first_name' => 'ຊື່',
        'last_name' => 'ນາມສະກຸນ', 
        'gender' => 'ເພດ',
        'date_of_birth' => 'ວັນເກີດ',
        'phone' => 'ເບີໂທລະສັບ',
        'email' => 'ອີເມລ',
        'payment_amount' => 'ຈຳນວນເງິນ',
        'major' => 'ສາຂາວິຊາ',
        'graduation_year' => 'ປີສຳເລັດການສຶກສາ'
    ];
    
    $data = [];
    $errors = [];
    
    // Check required fields
    foreach ($required_fields as $field => $label) {
        if (empty($_POST[$field])) {
            $errors[] = "ກະລຸນາໃສ່ {$label}";
        } else {
            $data[$field] = sanitizeInput($_POST[$field]);
        }
    }
    
    // Validate email format
    if (!empty($data['email']) && !validateEmail($data['email'])) {
        $errors[] = 'ຮູບແບບອີເມລບໍ່ຖືກຕ້ອງ';
    }
    
    // Validate phone format
    if (!empty($data['phone']) && !validatePhone($data['phone'])) {
        $errors[] = 'ຮູບແບບເບີໂທລະສັບບໍ່ຖືກຕ້ອງ';
    }
    
    // Check if email already exists
    if (!empty($data['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM registrations WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'ອີເມລນີ້ໄດ້ລົງທະບຽນແລ້ວ';
        }
    }
    
    // Return errors if any
    if (!empty($errors)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'ພົບຂໍ້ຜິດພາດ',
            'errors' => $errors
        ], 400);
    }
    
    // Handle file uploads
    $profile_image = null;
    $payment_proof = null;
    
    // Upload profile image
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadFile($_FILES['profile_image'], UPLOAD_DIR_PROFILES, ALLOWED_PROFILE_TYPES);
        if (!$uploadResult['success']) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ຮູບໂປຣໄຟລ໌: ' . $uploadResult['message']
            ], 400);
        }
        $profile_image = $uploadResult['filename'];
    }
    
    // Upload payment proof
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = uploadFile($_FILES['payment_proof'], UPLOAD_DIR_PAYMENTS, ALLOWED_PAYMENT_TYPES);
        if (!$uploadResult['success']) {
            sendJsonResponse([
                'success' => false,
                'message' => 'ຫຼັກຖານການຊຳລະ: ' . $uploadResult['message']
            ], 400);
        }
        $payment_proof = $uploadResult['filename'];
    }
    
    // Generate student code
    $student_code = generateStudentCode($pdo);
    
    // Insert registration
    $stmt = $pdo->prepare("
        INSERT INTO registrations (
            student_code, first_name, last_name, gender, date_of_birth, 
            email, phone, payment_amount, major, graduation_year,
            profile_image, payment_proof, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $student_code,
        $data['first_name'],
        $data['last_name'], 
        $data['gender'],
        $data['date_of_birth'],
        $data['email'],
        $data['phone'],
        $data['payment_amount'],
        $data['major'],
        $data['graduation_year'],
        $profile_image,
        $payment_proof
    ]);
    
    $registration_id = $pdo->lastInsertId();
    
    // Log activity
    logActivity($pdo, 'student_registration', [
        'registration_id' => $registration_id,
        'student_code' => $student_code,
        'email' => $data['email']
    ]);
    
    sendJsonResponse([
        'success' => true,
        'message' => 'ລົງທະບຽນສຳເລັດ',
        'data' => [
            'registration_id' => $registration_id,
            'student_code' => $student_code,
            'status' => 'pending'
        ]
    ], 201);
    
} catch (Exception $e) {
    error_log("Registration API error: " . $e->getMessage());
    sendJsonResponse([
        'error' => 'ເກີດຂໍ້ຜິດພາດໃນລະບົບ',
        'success' => false
    ], 500);
}
?>