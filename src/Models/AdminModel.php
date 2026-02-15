<?php 
require_once __DIR__ . '/Database.php';

class Admin {
    private PDO $db;
    private static bool $tableChecked = false;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Only check table once per request
        if (!self::$tableChecked) {
            $this->createAdminTable();
            self::$tableChecked = true;
        }
    }

    public function getAdminByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createAdmin($email, $password) {
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email and password cannot be empty'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }

        if ($this->getAdminByEmail($email)) {
            return [
                'success' => false,
                'message' => 'Email already exists'
            ];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->db->prepare("INSERT INTO admin (email, password) VALUES (?, ?)");
            $stmt->execute([$email, $hashedPassword]);
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'id' => $this->db->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    public function storeOTP($email, $otp) {
        try {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $stmt = $this->db->prepare("UPDATE admin SET otp = ?, otp_expires_at = ? WHERE email = ?");
            $stmt->execute([$otp, $expiresAt, $email]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function verifyOTP($email, $otp) {
        try {
            $stmt = $this->db->prepare("SELECT otp, otp_expires_at FROM admin WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                return [
                    'success' => false,
                    'message' => 'Admin not found'
                ];
            }

            if (strtotime($admin['otp_expires_at']) < time()) {
                return [
                    'success' => false,
                    'message' => 'OTP has expired'
                ];
            }

            if ($admin['otp'] !== $otp) {
                return [
                    'success' => false,
                    'message' => 'Invalid OTP'
                ];
            }

            $stmt = $this->db->prepare("UPDATE admin SET isverified = 1, otp = NULL, otp_expires_at = NULL WHERE email = ?");
            $stmt->execute([$email]);

            return [
                'success' => true,
                'message' => 'Admin verified successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    private function createAdminTable() {
        $sql = "CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            isverified BOOLEAN DEFAULT FALSE,
            otp VARCHAR(6) DEFAULT NULL,
            otp_expires_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP            
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $this->db->exec($sql);  
        } catch (PDOException $e) {
            error_log("Error creating admin table: " . $e->getMessage());
        }
    }

    public function handleLogin($email, $password) {
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email and password are required'
            ];
        }

        $admin = $this->getAdminByEmail($email);
        
        if (!$admin) {
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }

        if (!password_verify($password, $admin['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }

        if (!$admin['isverified']) {
            return [
                'success' => false,
                'message' => 'Please verify your email first',
                'redirect' => '/verify?email=' . urlencode($email)
            ];
        }

        return [
            'success' => true,
            'message' => 'Login successful',
            'admin' => $admin
        ];
    }
}