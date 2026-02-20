<?php

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
class UserModel{
     private PDO $db;
     private static bool $tableChecked = false;

     public function __construct()
     {
        $this->db = Database::getInstance()->getConnection();
       if(!self::$tableChecked){
        $this->createUserTable();
        self::$tableChecked = true;
       }
     }

     private function createUserTable() {
            $sql = "CREATE TABLE IF NOT EXISTS User (
                user_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                userProfile VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                organization_id INT NOT NULL,
                profile_public_id VARCHAR(255),
                role ENUM('manager', 'member') NOT NULL, 
                FOREIGN KEY (organization_id) REFERENCES Organization(organization_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                throw new Exception("Error creating User table: " . $e->getMessage());
            }
        }

        public function createUser($name, $password, $userprofile = null, $organization_id, $profile_public_id = null, $role) {
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $this->db->prepare("
                    INSERT INTO User (name, password, userProfile, organization_id, profile_public_id, role) 
                    VALUES (:name, :password, :userprofile, :organization_id, :profile_public_id, :role)
                ");
                
                $stmt->execute([
                    ':name'              => $name,
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

        }