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
            logo_public_id VARCHAR(255),  
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

    public function getOrganizationId(int $admin_id): ?int
    {
        $stmt = $this->db->prepare(
            "SELECT organization_id FROM Organization WHERE admin_id = ?"
        );
        $stmt->execute([$admin_id]);

        $id = $stmt->fetchColumn();

        return $id !== false ? (int)$id : null;
    }

  public function getOrganizationdetails(int $admin_id){
    $stmt = $this->db->prepare(
        'SELECT organization_id, name, slogan, organization_logo, created_at 
         FROM Organization
         WHERE admin_id = :admin_id'
    );

    $stmt->execute([
        ':admin_id' => $admin_id
    ]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    public function updateOrganization(int $org_id, string $name, string $slogan): array {
        try {
            $stmt = $this->db->prepare("
                UPDATE organization
                SET name = :name, slogan = :slogan
                WHERE organization_id = :id
            ");
            $stmt->execute([':name' => $name, ':slogan' => $slogan, ':id' => $org_id]);
            return ['success' => true, 'message' => 'Organization updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
 
    // ── Update logo URL + public_id (pass null to clear both) ─────────
    public function updateOrgLogo(int $org_id, ?string $logoUrl, ?string $publicId): array {
        try {
            $stmt = $this->db->prepare("
                UPDATE organization
                SET organization_logo = :url,
                    logo_public_id    = :pid
                WHERE organization_id = :id
            ");
            $stmt->execute([':url' => $logoUrl, ':pid' => $publicId, ':id' => $org_id]);
            return ['success' => true, 'message' => 'Logo updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
 
    // ── Delete org + cascade ──────────────────────────────────────────
    // Deletes the organization row. All related data (projects, tasks,
    // members, comments, attachments, activity_log) should be handled
    // by ON DELETE CASCADE on your foreign keys. If you haven't set
    // CASCADE, manually delete in order below.
    public function deleteOrganization(int $org_id): array {
        try {
            $this->db->beginTransaction();
 
            // If no FK cascades, delete in dependency order:
            $this->db->prepare("DELETE FROM activity_log  WHERE org_id            = ?")->execute([$org_id]);
            $this->db->prepare("DELETE FROM task_attachment WHERE task_id IN (
                SELECT task_id FROM task WHERE project_id IN (
                    SELECT project_id FROM project WHERE organization_id = ?
                ))")->execute([$org_id]);
            $this->db->prepare("DELETE FROM task_comment WHERE task_id IN (
                SELECT task_id FROM task WHERE project_id IN (
                    SELECT project_id FROM project WHERE organization_id = ?
                ))")->execute([$org_id]);
            $this->db->prepare("DELETE FROM task WHERE project_id IN (
                SELECT project_id FROM project WHERE organization_id = ?
            )")->execute([$org_id]);
            $this->db->prepare("DELETE FROM project_members WHERE project_id IN (
                SELECT project_id FROM project WHERE organization_id = ?
            )")->execute([$org_id]);
            $this->db->prepare("DELETE FROM project           WHERE organization_id = ?")->execute([$org_id]);
            $this->db->prepare("DELETE FROM user              WHERE organization_id = ?")->execute([$org_id]);
            $this->db->prepare("DELETE FROM meeting           WHERE project_id IN (
                SELECT project_id FROM project WHERE organization_id = ?
            )")->execute([$org_id]);
            $this->db->prepare("DELETE FROM organization      WHERE organization_id = ?")->execute([$org_id]);
 
            $this->db->commit();
            return ['success' => true, 'message' => 'Organization deleted'];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
}