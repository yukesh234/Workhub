<?php

require_once __DIR__ . '/../Models/Database.php';

class ProjectModel {

    private PDO $db;
    private static bool $tableChecked = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();

        if (!self::$tableChecked) {
            $this->createTable();
            self::$tableChecked = true;
        }
    }

    private function createTable(): void
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS project (
            project_id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            status ENUM('active','completed','archived') DEFAULT 'active',
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            FOREIGN KEY (organization_id)
                REFERENCES organization(organization_id)
                ON DELETE CASCADE,

            FOREIGN KEY (created_by)
                REFERENCES admin(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci;
        ";

        $this->db->exec($sql);
    }

    public function createProject(
        int $organization_id,
        string $name,
        ?string $description,
        int $created_by,
        string $status = 'active'
    ): array {

        $stmt = $this->db->prepare("
            INSERT INTO project
            (organization_id,name,description,status,created_by)
            VALUES
            (:organization_id,:name,:description,:status,:created_by)
        ");

        $stmt->execute([
            ':organization_id'=>$organization_id,
            ':name'=>$name,
            ':description'=>$description,
            ':status'=>$status,
            ':created_by'=>$created_by
        ]);

        return [
            'success'=>true,
            'message'=>'Project created successfully',
            'project_id'=>$this->db->lastInsertId()
        ];
    }

    public function updateProject(
        int $project_id,
        string $name,
        ?string $description,
        string $status
    ): array {

        $stmt = $this->db->prepare("
            UPDATE project
            SET name=:name,
                description=:description,
                status=:status
            WHERE project_id=:project_id
        ");

        $stmt->execute([
            ':project_id'=>$project_id,
            ':name'=>$name,
            ':description'=>$description,
            ':status'=>$status
        ]);

        return [
            'success'=>true,
            'message'=>'Project updated successfully',
            'project_id'=>$project_id
        ];
    }

    public function deleteProject(int $project_id): array {

        $stmt = $this->db->prepare("
            DELETE FROM project
            WHERE project_id=:project_id
        ");

        $stmt->execute([
            ':project_id'=>$project_id
        ]);

        return [
            'success'=>true,
            'message'=>'Project deleted successfully'
        ];
    }

    public function getProjectById(int $project_id): ?array {

        $stmt=$this->db->prepare("
            SELECT *
            FROM project
            WHERE project_id=?
        ");

        $stmt->execute([$project_id]);

        $result=$stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function getProjectsByOrganization(int $organization_id): array {

        $stmt=$this->db->prepare("
            SELECT *
            FROM project
            WHERE organization_id=?
            ORDER BY created_at DESC
        ");

        $stmt->execute([$organization_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}