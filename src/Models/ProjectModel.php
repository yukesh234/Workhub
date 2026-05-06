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
        // Shared updated_at trigger function (safe to run multiple times)
        $this->db->exec("
            CREATE OR REPLACE FUNCTION set_updated_at()
            RETURNS TRIGGER AS \$\$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS project (
                project_id      SERIAL PRIMARY KEY,
                organization_id INT NOT NULL,
                name            VARCHAR(255) NOT NULL,
                description     TEXT,
                status          VARCHAR(20) NOT NULL DEFAULT 'active'
                                    CHECK (status IN ('active','completed','archived')),
                created_by      INT NOT NULL,
                created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (organization_id)
                    REFERENCES organization(organization_id)
                    ON DELETE CASCADE,

                FOREIGN KEY (created_by)
                    REFERENCES admin(id)
                    ON DELETE CASCADE
            );
        ");

        $this->db->exec("
            DROP TRIGGER IF EXISTS project_updated_at ON project;
            CREATE TRIGGER project_updated_at
                BEFORE UPDATE ON project
                FOR EACH ROW EXECUTE FUNCTION set_updated_at();
        ");
    }

    public function createProject(
        int $organization_id,
        string $name,
        ?string $description,
        int $created_by,
        string $status = 'active'
    ): array {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO project (organization_id, name, description, status, created_by)
                VALUES (:organization_id, :name, :description, :status, :created_by)
                RETURNING project_id
            ");

            $stmt->execute([
                ':organization_id' => $organization_id,
                ':name'            => $name,
                ':description'     => $description,
                ':status'          => $status,
                ':created_by'      => $created_by,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success'    => true,
                'message'    => 'Project created successfully',
                'project_id' => (int) $row['project_id'],
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateProject(
        int $project_id,
        string $name,
        ?string $description,
        string $status
    ): array {
        try {
            $stmt = $this->db->prepare("
                UPDATE project
                SET name        = :name,
                    description = :description,
                    status      = :status
                WHERE project_id = :project_id
            ");

            $stmt->execute([
                ':project_id'  => $project_id,
                ':name'        => $name,
                ':description' => $description,
                ':status'      => $status,
            ]);

            return [
                'success'    => true,
                'message'    => 'Project updated successfully',
                'project_id' => $project_id,
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteProject(int $project_id): array {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM project WHERE project_id = :project_id
            ");
            $stmt->execute([':project_id' => $project_id]);

            return ['success' => true, 'message' => 'Project deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getProjectById(int $project_id): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM project WHERE project_id = ?
        ");
        $stmt->execute([$project_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getProjectsByOrganization(int $organization_id): array {
        $stmt = $this->db->prepare("
            SELECT * FROM project
            WHERE organization_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$organization_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}