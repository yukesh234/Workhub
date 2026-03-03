<?php

require_once __DIR__ . '/../Models/Database.php';

class ProjectModel{
    private PDO $db;
    private static bool $tablechecked = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        
         if (!self::$tablechecked) {
            $this->createTable();
            self::$tablechecked = true;
        }
    }

    private function createTable(){
        $sql = "
        CREATE TABLE if not exists project (
            project_id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('active','completed','archived') DEFAULT 'active',
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
            ON UPDATE CURRENT_TIMESTAMP,

            FOREIGN KEY (organization_id) 
            REFERENCES organization(organization_id)
            ON DELETE CASCADE,

            FOREIGN KEY (created_by) 
            REFERENCES user(user_id)
            ON DELETE CASCADE
            )ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        "; 
        try{
            $this->db->exec($sql);
        }catch(PDOException $e){ 
            throw new Exception("Error while creating project table", $e->getMessage());
        }
    }
    public function createProject($organization_id, $name, $description, $status,$created_by){
        try{
            $stmt = $this->db->prepare("
            insert into project (organization_id,name,description,status)
            value(:organization_id,:name,:description,:status,:created_by)
            ");
            $stmt->execute([
                ':organization_id' => $organization_id,
                ':name' => $name,
                ':description' => $description,
                ':status' => $status,
                ':created_by' => $created_by
            ]);

            return [
                'success' => true,
                'message' => 'User created successfully',
                'project_id' => $this->db->lastInsertId()
            ];
        }catch(PDOException $e){
            throw new Exception("Error creating project", $e->getMessage());
        }
    }

    public function updateProject($name,$description,$status, $project_id){
        try{
         $stmt = $this->db->prepare("
          update project 
          set name = :name,
          description = :description,
          status = :status
          where project_id = :project_id
         ");
         $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':status' => $status,
            ':project_id' => $project_id 
         ]);

         return[
            'success' => true,
            'message' => "successfully updated the project",
            'project_id' => $project_id
         ];
        }catch(PDOException $e){
            throw new Exception("Error updating project", $e->getMessage());
        }
    }

    public function deleteProject($project_id){
        try{
            $stmt = $this->db->prepare("
            delete from project where project_id = :project_id
            ");
            $stmt->execute([
                ':project_id' => $project_id
            ]);
        return[
            'success' =>true,
            'message' => "project deleted successfully"
        ];
        }catch(PDOException $e){
            throw new Exception("Error deleting the project", $e->getMessage());
        }
    }

    public function getProjectbyid($project_id){
        $stmt = $this->db->prepare("
          select project_id, organization_id, name,description,status,created_by
          where project_id =? 
        ");
        $stmt->execute([$project_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getProjectbyOrganization($organization_id){
         $stmt = $this->db->prepare("
          select project_id, organization_id, name,description,status,created_by
          where organization_id =? 
        ");
        $stmt->execute([$organization_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}