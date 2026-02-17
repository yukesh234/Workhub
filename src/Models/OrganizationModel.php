<?php

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
    private function createOrganizationTable(){
         $sql = "CREATE TABLE IF NOT EXISTS Organization (
         organization_id int auto_increment primary key,
         admin _id int not null unique,
         name varchar(255) not null,
         slogan varhcar(255),
         organization_logo varchar(255),
         created_at timestamp default current_timestamp,
         updated_at timestamp default current_timestamp on update current_timestamp,
         foreign key (admin_id) references admin(id)           
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try{
            $this->db->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Error creating Organization table: " . $e->getMessage());
        }
    }

    public function createrOrganization($admin_id, $name, $slogan = null, $logoUrl = null): array {
        try {
            $stmt = $this->db->prepare("INSERT INTO Organization (admin_id, name, slogan, organization_logo) VALUES (:admin_id, :name, :slogan, :logo)");
            $stmt->execute([
                ':admin_id' => $admin_id,
                ':name' => $name,
                ':slogan' => $slogan,
                ':logo' => $logoUrl
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
}