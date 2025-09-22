<?php
/**
 * User Management Utilities
 * ຍູທິລິຕີການຈັດການຜູ້ໃຊ້
 */

require_once __DIR__ . '/config.php';

/**
 * User Authentication Class
 */
class UserAuth {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getPDOConnection();
    }
    
    /**
     * Authenticate user login
     */
    public function login($username, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, password_hash, full_name, email, role, status, last_login 
                FROM users 
                WHERE username = ? OR email = ?
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'ບໍ່ພົບຜູ້ໃຊ້ນີ້ໃນລະບົບ'];
            }
            
            if ($user['status'] !== 'active') {
                return ['success' => false, 'message' => 'ບັນຊີຜູ້ໃຊ້ຖືກປິດການນໍາໃຊ້'];
            }
            
            if (!password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ'];
            }
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Generate JWT token
            $payload = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'iat' => time(),
                'exp' => time() + JWT_EXPIRE_TIME
            ];
            
            $token = generateJWT($payload);
            
            // Log activity
            logActivity($this->pdo, 'user_login', [
                'user_id' => $user['id'],
                'username' => $user['username']
            ]);
            
            return [
                'success' => true,
                'message' => 'ເຂົ້າສູ່ລະບົບສຳເລັດ',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ],
                'token' => $token,
                'expires_at' => time() + JWT_EXPIRE_TIME
            ];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນລະບົບ'];
        }
    }
    
    /**
     * Update last login time
     */
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, full_name, email, role, status, created_at, last_login 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create new user
     */
    public function createUser($userData) {
        try {
            // Validate required fields
            $required = ['username', 'password', 'full_name', 'email', 'role'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    return ['success' => false, 'message' => "ຟິລ {$field} ບໍ່ສາມາດຫວ່າງເປົ່າໄດ້"];
                }
            }
            
            // Check if username/email exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$userData['username'], $userData['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'ຊື່ຜູ້ໃຊ້ ຫຼື ອີເມລນີ້ມີຢູ່ແລ້ວ'];
            }
            
            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password_hash, full_name, email, role, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            
            $stmt->execute([
                $userData['username'],
                $passwordHash,
                $userData['full_name'],
                $userData['email'],
                $userData['role']
            ]);
            
            $userId = $this->pdo->lastInsertId();
            
            // Log activity
            logActivity($this->pdo, 'user_created', [
                'new_user_id' => $userId,
                'username' => $userData['username'],
                'role' => $userData['role']
            ]);
            
            return [
                'success' => true,
                'message' => 'ສ້າງຜູ້ໃຊ້ສຳເລັດ',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            error_log("Create user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການສ້າງຜູ້ໃຊ້'];
        }
    }
    
    /**
     * Update user
     */
    public function updateUser($userId, $userData) {
        try {
            $updateFields = [];
            $params = [];
            
            // Build dynamic update query
            if (!empty($userData['full_name'])) {
                $updateFields[] = 'full_name = ?';
                $params[] = $userData['full_name'];
            }
            
            if (!empty($userData['email'])) {
                // Check if email exists for other users
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$userData['email'], $userId]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'ອີເມລນີ້ມີຜູ້ໃຊ້ແລ້ວ'];
                }
                
                $updateFields[] = 'email = ?';
                $params[] = $userData['email'];
            }
            
            if (!empty($userData['password'])) {
                $updateFields[] = 'password_hash = ?';
                $params[] = password_hash($userData['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            }
            
            if (!empty($userData['role'])) {
                $updateFields[] = 'role = ?';
                $params[] = $userData['role'];
            }
            
            if (isset($userData['status'])) {
                $updateFields[] = 'status = ?';
                $params[] = $userData['status'];
            }
            
            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'ບໍ່ມີຂໍ້ມູນທີ່ຕ້ອງປັບປຸງ'];
            }
            
            $updateFields[] = 'updated_at = NOW()';
            $params[] = $userId;
            
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // Log activity
            logActivity($this->pdo, 'user_updated', [
                'updated_user_id' => $userId,
                'updated_fields' => array_keys($userData)
            ]);
            
            return ['success' => true, 'message' => 'ປັບປຸງຂໍ້ມູນຜູ້ໃຊ້ສຳເລັດ'];
            
        } catch (Exception $e) {
            error_log("Update user error: " . $e->getMessage());
            return ['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການປັບປຸງຂໍ້ມູນ'];
        }
    }
    
    /**
     * Get all users (admin only)
     */
    public function getAllUsers($page = 1, $limit = 20, $search = '') {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build search condition
            $whereClause = '';
            $params = [];
            
            if (!empty($search)) {
                $whereClause = "WHERE username LIKE ? OR full_name LIKE ? OR email LIKE ?";
                $searchTerm = "%{$search}%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM users {$whereClause}";
            $stmt = $this->pdo->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Get users
            $sql = "
                SELECT id, username, full_name, email, role, status, created_at, last_login 
                FROM users 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT {$limit} OFFSET {$offset}
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $users = $stmt->fetchAll();
            
            return [
                'success' => true,
                'users' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_records' => $total,
                    'per_page' => $limit
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return ['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດໃນການດຶງຂໍ້ມູນຜູ້ໃຊ້'];
        }
    }
    
    /**
     * Check user permissions
     */
    public function hasPermission($userRole, $requiredRole) {
        $roleHierarchy = [
            'viewer' => 1,
            'staff' => 2,
            'admin' => 3
        ];
        
        $userLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredLevel = $roleHierarchy[$requiredRole] ?? 999;
        
        return $userLevel >= $requiredLevel;
    }
}
?>