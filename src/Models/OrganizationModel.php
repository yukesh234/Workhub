<?php
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
class OrganizationModel{
     private PDO $db;
     private static bool $tableChecked = false;
    public function __construct()
    {
       $this->db = Database::getInstance()->getConnection();
       if(!self::$tableChecked){
        $this->createOrganizationTable();
        self::$tableChecked = true;
       }
    }

    // property of oraganization 
    /*
     organization_id
     admin_id references to adin table should be unique and not null
     name string not null
     slogan string 
     oragniaztion logo string (url of cloudinary)
     created_at timestamp default current_timestamp
     updated_at timestamp default current_timestamp on update current_timestamp
    */
    private function createOrganizationTable() {
        $sql = "CREATE TABLE IF NOT EXISTS Organization (
            organization_id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL UNIQUE,
            slogan VARCHAR(255),
            organization_logo VARCHAR(255),
            logo_public_id VARCHAR(255),  -- ← Add this for deletion
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Error creating Organization table: " . $e->getMessage());
        }
    }

    public function createOrganization($admin_id, $name, $slogan = null, $logoUrl = null, $publicId = null): array {
    try {
        $stmt = $this->db->prepare("
            INSERT INTO Organization (admin_id, name, slogan, organization_logo, logo_public_id) 
            VALUES (:admin_id, :name, :slogan, :logo, :public_id)
        ");
        
        $stmt->execute([
            ':admin_id'  => $admin_id,
            ':name'      => $name,
            ':slogan'    => $slogan,
            ':logo'      => $logoUrl,
            ':public_id' => $publicId  // ← Store for future deletion
        ]);

        return [
            'success' => true,
            'message' => 'Organization created successfully',
            'organization_id' => $this->db->lastInsertId()
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
    }

    public function checkOrganization(){
        
    }


}