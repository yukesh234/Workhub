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
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS project_members (
                project_id INT NOT NULL,
                user_id    INT NOT NULL,
                role       VARCHAR(10) NOT NULL DEFAULT 'member'
                               CHECK (role IN ('manager','member')),
                added_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (project_id, user_id),

                FOREIGN KEY (project_id)
                    REFERENCES project(project_id)
                    ON DELETE CASCADE,

                FOREIGN KEY (user_id)
                    REFERENCES \"user\"(user_id)
                    ON DELETE CASCADE
            );
        ");
    }

    /**
     * Add a member to a project, inheriting the role they were created with.
     * Pass an explicit $role to override (e.g. when the admin wants to promote).
     */
    public function addMember(int $project_id, int $user_id, string $role = ''): array
    {
        try {
            // If no role supplied, inherit from the user table
            if ($role === '') {
                $stmt = $this->db->prepare(
                    "SELECT role FROM \"user\" WHERE user_id = ?"
                );
                $stmt->execute([$user_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    return ['success' => false, 'message' => 'User not found'];
                }
                $role = $row['role'];
            }

            $stmt = $this->db->prepare("
                INSERT INTO project_members (project_id, user_id, role)
                VALUES (:project_id, :user_id, :role)
                ON CONFLICT (project_id, user_id) DO NOTHING
            ");

            $stmt->execute([
                ':project_id' => $project_id,
                ':user_id'    => $user_id,
                ':role'       => $role,
            ]);

            return ['success' => true, 'message' => 'Member added successfully'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error adding member: ' . $e->getMessage()];
        }
    }

    public function removeMember(int $project_id, int $user_id): array
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM project_members
                WHERE project_id = :project_id
                  AND user_id    = :user_id
            ");

            $stmt->execute([
                ':project_id' => $project_id,
                ':user_id'    => $user_id,
            ]);

            return ['success' => true, 'message' => 'Member removed successfully'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error removing member: ' . $e->getMessage()];
        }
    }

    public function getMembers(int $project_id): array
    {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.name, u.email, u.\"userProfile\", pm.role, pm.added_at
            FROM project_members pm
            JOIN \"user\" u ON u.user_id = pm.user_id
            WHERE pm.project_id = ?
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
              AND user_id    = ?
              AND role       = 'manager'
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
              AND user_id    = ?
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
                  AND user_id    = :user_id
            ");

            $stmt->execute([
                ':role'       => $newRole,
                ':project_id' => $project_id,
                ':user_id'    => $user_id,
            ]);

            return ['success' => true, 'message' => 'Role updated successfully'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error updating role: ' . $e->getMessage()];
        }
    }
}