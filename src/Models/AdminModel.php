<?php

require_once __DIR__ . '/Database.php';

class Admin {
    private PDO $db;
    private static bool $tableChecked = false;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (!self::$tableChecked) {
            $this->createAdminTable();
            self::$tableChecked = true;
        }
    }

    private function createAdminTable(): void {
        // PostgreSQL: SERIAL for auto-increment, no ENGINE/CHARSET clause
        $sql = "
        CREATE TABLE IF NOT EXISTS admin (
            id             SERIAL PRIMARY KEY,
            email          VARCHAR(255) NOT NULL UNIQUE,
            password       VARCHAR(255) NOT NULL,
            isverified     BOOLEAN DEFAULT FALSE,
            otp            VARCHAR(6)   DEFAULT NULL,
            otp_expires_at TIMESTAMPTZ  DEFAULT NULL,
            created_at     TIMESTAMPTZ  DEFAULT CURRENT_TIMESTAMP
        )";
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            error_log("Error creating admin table: " . $e->getMessage());
        }
    }

    public function getAdminByEmail(string $email): array|false {
        $stmt = $this->db->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createAdmin(string $email, string $password): array {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password cannot be empty'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        if ($this->getAdminByEmail($email)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try {
            // RETURNING id is the PostgreSQL way to retrieve the new PK
            $stmt = $this->db->prepare("
                INSERT INTO admin (email, password) VALUES (?, ?)
                RETURNING id
            ");
            $stmt->execute([$email, $hashedPassword]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'success' => true,
                'message' => 'Registration successful',
                'id'      => $row['id'],
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function storeOTP(string $email, string $otp): bool {
        try {
            // PostgreSQL interval syntax
            $stmt = $this->db->prepare("
                UPDATE admin
                SET otp = ?, otp_expires_at = NOW() + INTERVAL '10 minutes'
                WHERE email = ?
            ");
            $stmt->execute([$otp, $email]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function verifyOTP(string $email, string $otp): array {
        try {
            $stmt = $this->db->prepare("SELECT otp, otp_expires_at FROM admin WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                return ['success' => false, 'message' => 'Admin not found'];
            }
            if (strtotime($admin['otp_expires_at']) < time()) {
                return ['success' => false, 'message' => 'OTP has expired'];
            }
            if ($admin['otp'] !== $otp) {
                return ['success' => false, 'message' => 'Invalid OTP'];
            }

            $this->db->prepare("
                UPDATE admin SET isverified = TRUE, otp = NULL, otp_expires_at = NULL
                WHERE email = ?
            ")->execute([$email]);

            return ['success' => true, 'message' => 'Admin verified successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function handleLogin(string $email, string $password): array {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $admin = $this->getAdminByEmail($email);
        if (!$admin) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        if (!password_verify($password, $admin['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        if (!$admin['isverified']) {
            return [
                'success'  => false,
                'message'  => 'Please verify your email first',
                'redirect' => '/verify?email=' . urlencode($email),
            ];
        }

        return ['success' => true, 'message' => 'Login successful', 'admin' => $admin];
    }

    public function changeAdminPassword(int $admin_id, string $currentPw, string $newPw): array {
        try {
            $stmt = $this->db->prepare("SELECT password FROM admin WHERE id = ?");
            $stmt->execute([$admin_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return ['success' => false, 'message' => 'Admin not found'];
            }
            if (!password_verify($currentPw, $row['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }

            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $this->db->prepare("UPDATE admin SET password = ? WHERE id = ?")->execute([$hash, $admin_id]);

            return ['success' => true, 'message' => 'Password updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function verifyOTPForReset(string $email, string $otp): array {
        try {
            $stmt = $this->db->prepare("SELECT otp, otp_expires_at FROM admin WHERE email = ?");
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row)         return ['success' => false, 'message' => 'Admin not found'];
            if (!$row['otp'])  return ['success' => false, 'message' => 'No OTP requested'];
            if (strtotime($row['otp_expires_at']) < time()) {
                return ['success' => false, 'message' => 'OTP has expired'];
            }
            if ($row['otp'] !== $otp) {
                return ['success' => false, 'message' => 'Invalid OTP'];
            }

            $this->db->prepare("
                UPDATE admin SET otp = NULL, otp_expires_at = NULL WHERE email = ?
            ")->execute([$email]);

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}