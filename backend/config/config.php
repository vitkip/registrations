<?php
/**
 * Certificate Registration System - Configuration
 * ລະບົບລົງທະບຽນຮັບໃບປະກາດນິຍະບັດ
 * Created: September 21, 2025
 */

// ===== DATABASE CONFIGURATION =====
define('DB_HOST', $_ENV['DB_HOST'] ?? 'certificate_db');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'certificate_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'cert_user');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? 'cert_password_secure_123');
define('DB_CHARSET', 'utf8mb4');

// ===== JWT CONFIGURATION =====
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'certificate_jwt_secret_key_2025_secure');
define('JWT_EXPIRE_TIME', 24 * 60 * 60); // 24 hours

// ===== FILE UPLOAD CONFIGURATION =====
define('UPLOAD_DIR_PROFILES', __DIR__ . '/../../uploads/profiles/');
define('UPLOAD_DIR_PAYMENTS', __DIR__ . '/../../uploads/payments/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_PROFILE_TYPES', ['jpg', 'jpeg', 'png']);
define('ALLOWED_PAYMENT_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);

// ===== SECURITY CONFIGURATION =====
define('BCRYPT_COST', 12);
define('SESSION_LIFETIME', 24 * 60 * 60); // 24 hours
define('CSRF_TOKEN_LENGTH', 32);

// ===== API CONFIGURATION =====
header('Content-Type: application/json; charset=utf-8');

/**
 * Database Connection
 */
function getPDOConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci"
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            sendJsonResponse(['error' => 'Database connection failed', 'success' => false], 500);
            exit();
        }
    }
    
    return $pdo;
}

/**
 * JWT Functions
 */
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function generateJWT($payload) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode($payload);
    
    $headerEncoded = base64UrlEncode($header);
    $payloadEncoded = base64UrlEncode($payload);
    
    $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, JWT_SECRET, true);
    $signatureEncoded = base64UrlEncode($signature);
    
    return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
}

function verifyJWT($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    
    list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
    
    $signature = base64UrlDecode($signatureEncoded);
    $expectedSignature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, JWT_SECRET, true);
    
    if (!hash_equals($expectedSignature, $signature)) return false;
    
    $payload = json_decode(base64UrlDecode($payloadEncoded), true);
    
    if (isset($payload['exp']) && $payload['exp'] < time()) return false;
    
    return $payload;
}

/**
 * File Upload Functions
 */
function uploadFile($file, $uploadDir, $allowedTypes) {
    // Check upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການອັບໂຫລດໄຟລ໌'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'ຂະໜາດໄຟລ໌ໃຫຍ່ເກີນກຳນົດ'];
    }
    
    // Check file type
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'ປະເພດໄຟລ໌ບໍ່ຖືກຕ້ອງ'];
    }
    
    // Validate MIME type
    $validMimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg', 
        'png' => 'image/png',
        'pdf' => 'application/pdf'
    ];
    
    if (!isset($validMimeTypes[$extension]) || $mimeType !== $validMimeTypes[$extension]) {
        return ['success' => false, 'message' => 'ປະເພດໄຟລ໌ບໍ່ຖືກຕ້ອງ'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Create directory if not exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'ບໍ່ສາມາດບັນທຶກໄຟລ໌ໄດ້'];
    }
    
    return ['success' => true, 'filename' => 'uploads/' . basename($uploadDir) . '/' . $filename];
}

/**
 * Utility Functions
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function sanitizeInput($input) {
    return trim(stripslashes(htmlspecialchars($input)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    // ลาว phone number pattern: 020-XXXX-XXXX or similar
    return preg_match('/^[0-9\-\+\(\)\s]{8,20}$/', $phone);
}

function generateStudentCode($pdo) {
    $stmt = $pdo->query("SELECT generate_student_code() as code");
    $result = $stmt->fetch();
    return $result['code'];
}

/**
 * Authentication Functions
 */
function requireAuth() {
    $token = getBearerToken();
    if (!$token) {
        sendJsonResponse(['error' => 'ຕ້ອງເຂົ້າສູ່ລະບົບກ່ອນ', 'success' => false], 401);
    }
    
    $payload = verifyJWT($token);
    if (!$payload) {
        sendJsonResponse(['error' => 'Token ບໍ່ຖືກຕ້ອງ', 'success' => false], 401);
    }
    
    return $payload;
}

function getBearerToken() {
    $headers = apache_request_headers();
    
    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

/**
 * CORS Handler
 */
function handleCORS() {
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }
    
    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        
        exit(0);
    }
}

/**
 * Activity Logger
 */
function logActivity($pdo, $action, $details = []) {
    try {
        // Simple logging - you can expand this to a separate logs table
        $logEntry = json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        error_log("ACTIVITY: " . $logEntry);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Initialize PDO connection to test
$pdo = getPDOConnection();
?>