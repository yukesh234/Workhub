<?php

require_once __DIR__ . '/../Models/Database.php';

class UserModel
{
    private PDO $db;
    private static bool $tableChecked = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();

        if (!self::$tableChecked) {
            $this->createUserTable();
            self::$tableChecked = true;
        }
    }

    private function createUserTable()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS user (
            user_id            INT AUTO_INCREMENT PRIMARY KEY,
            name               VARCHAR(255) NOT NULL,
            email              VARCHAR(255) NOT NULL UNIQUE,
            password           VARCHAR(255) NOT NULL,
            userProfile        VARCHAR(255),
            profile_public_id  VARCHAR(255),
            role               ENUM('manager', 'member') NOT NULL,
            organization_id    INT NOT NULL,
            created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organization(organization_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Error creating user table: " . $e->getMessage());
        }
    }

    public function createUser(
        $name,
        $email,
        $password,
        $organization_id,
        $role,
        $userprofile = null,
        $profile_public_id = null
    ) {
        try {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("
                INSERT INTO user 
                (name, email, password, userProfile, organization_id, profile_public_id, role) 
                VALUES 
                (:name, :email, :password, :userprofile, :organization_id, :profile_public_id, :role)
            ");

            $stmt->execute([
                ':name'              => $name,
                ':email'             => $email,
                ':password'          => $hashedPassword,
                ':userprofile'       => $userprofile,
                ':organization_id'   => $organization_id,
                ':profile_public_id' => $profile_public_id,
                ':role'              => $role
            ]);

            return [
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $this->db->lastInsertId()
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    public function updatePassword($newPassword, $user_id)
    {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("
                UPDATE user 
                SET password = :password 
                WHERE user_id = :user_id
            ");

            $stmt->execute([
                ':password' => $hashedPassword,
                ':user_id'  => $user_id
            ]);

            return [
                'success' => true,
                'message' => 'Password updated successfully'
            ];

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    public function getOrganizationMember($organization_id)
    {
        try {

            $stmt = $this->db->prepare("
                SELECT user_id, name, userProfile, role, created_at
                FROM user
                WHERE organization_id = :organization_id
                ORDER BY created_at DESC
            ");

            $stmt->execute([
                ':organization_id' => $organization_id
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

  
    public function getUserbyemail($email)
    {
        $stmt = $this->db->prepare("
            SELECT user_id, name, email, password, userProfile, role, organization_id
            FROM user
            WHERE email = ?
        ");

        $stmt->execute([$email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

   
    public function handle_member_login($email, $password)
    {
        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Email and password are required'
            ];
        }

        $member = $this->getUserbyemail($email);

        if (!$member || !password_verify($password, $member['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }

        unset($member['password']);

        return [
            'success' => true,
            'message' => 'Logged in successfully',
            'data'    => $member
        ];
    }
}