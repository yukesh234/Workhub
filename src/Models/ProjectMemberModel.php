<?php

require_once __DIR__ . '/../Models/Database.php';

class ProjectMemberModel {

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
        CREATE TABLE IF NOT EXISTS project_members (
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            role ENUM('manager','member') DEFAULT 'member',
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (project_id, user_id),

            FOREIGN KEY (project_id)
                REFERENCES project(project_id)
                ON DELETE CASCADE,

            FOREIGN KEY (user_id)
                REFERENCES user(user_id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB
        DEFAULT CHARSET=utf8mb4
        COLLATE=utf8mb4_unicode_ci;
        ";

        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Error creating project_members table: " . $e->getMessage());
        }
    }

    public function addMember(int $project_id, int $user_id, string $role = 'member'): array
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO project_members (project_id, user_id, role)
                VALUES (:project_id, :user_id, :role)
            ");

            $stmt->execute([
                ':project_id' => $project_id,
                ':user_id'    => $user_id,
                ':role'       => $role
            ]);

            return [
                'success' => true,
                'message' => 'Member added successfully'
            ];

        } catch (PDOException $e) {
            throw new Exception("Error adding member: " . $e->getMessage());
        }
    }

    public function removeMember(int $project_id, int $user_id): array
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM project_members
                WHERE project_id = :project_id
                AND user_id = :user_id
            ");

            $stmt->execute([
                ':project_id' => $project_id,
                ':user_id'    => $user_id
            ]);

            return [
                'success' => true,
                'message' => 'Member removed successfully'
            ];

        } catch (PDOException $e) {
            throw new Exception("Error removing member: " . $e->getMessage());
        }
    }

    public function getMembers(int $project_id): array
    {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.name, u.email, u.userProfile, p.role, p.added_at
            FROM project_members p
            JOIN user u ON u.user_id = p.user_id
            WHERE p.project_id = ?
        ");

        $stmt->execute([$project_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isManager(int $project_id, int $user_id): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM project_members
            WHERE project_id = ?
            AND user_id = ?
            AND role = 'manager'
        ");

        $stmt->execute([$project_id, $user_id]);

        return (bool) $stmt->fetch();
    }

    public function isMember(int $project_id, int $user_id): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1
            FROM project_members
            WHERE project_id = ?
            AND user_id = ?
        ");

        $stmt->execute([$project_id, $user_id]);

        return (bool) $stmt->fetch();
    }

    public function changeRole(int $project_id, int $user_id, string $newRole): array
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE project_members
                SET role = :role
                WHERE project_id = :project_id
                AND user_id = :user_id
            ");

            $stmt->execute([
                ':role'       => $newRole,
                ':project_id' => $project_id,
                ':user_id'    => $user_id
            ]);

            return [
                'success' => true,
                'message' => 'Role updated successfully'
            ];

        } catch (PDOException $e) {
            throw new Exception("Error updating role: " . $e->getMessage());
        }
    }
}