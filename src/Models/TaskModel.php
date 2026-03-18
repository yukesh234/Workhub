<?php

require_once __DIR__ . '/Database.php';

class TaskModel {
    private PDO $db;
    private static bool $tableChecked = false;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (!self::$tableChecked) {
            $this->createTaskTable();
            self::$tableChecked = true;
        }
    }

    private function createTaskTable(): void {
        $sql = "
        CREATE TABLE IF NOT EXISTS task (
            task_id      INT AUTO_INCREMENT PRIMARY KEY,
            project_id   INT NOT NULL,
            assigned_to  INT DEFAULT NULL,
            title        VARCHAR(255) NOT NULL,
            description  TEXT DEFAULT NULL,
            status       ENUM('pending','in_progress','in_review','completed') NOT NULL DEFAULT 'pending',
            priority     ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
            due_date     DATE DEFAULT NULL,
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id)  REFERENCES project(project_id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to) REFERENCES user(user_id)       ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Error creating task table: " . $e->getMessage());
        }
    }

    public function createTask(
        $project_id,
        $assigned_to,
        $title,
        $description,
        $status,
        $priority,
        $due_date
    ): array {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO task (project_id, assigned_to, title, description, status, priority, due_date)
                VALUES (:project_id, :assigned_to, :title, :description, :status, :priority, :due_date)
            ");
            // ↑ Fixed: was `:title.:description` (dot) — must be comma
            $stmt->execute([
                ':project_id'  => $project_id,
                ':assigned_to' => $assigned_to,
                ':title'       => $title,
                ':description' => $description,
                ':status'      => $status,
                ':priority'    => $priority,
                ':due_date'    => $due_date,
            ]);
            return [
                'success' => true,
                'message' => 'Task created successfully',
                'task_id' => $this->db->lastInsertId(),
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    public function updateTask(
        $task_id,
        $assigned_to,
        $title,
        $description,
        $priority,
        $due_date
    ): array {
        try {
            $stmt = $this->db->prepare("
                UPDATE task SET
                    assigned_to = :assigned_to,
                    title       = :title,
                    description = :description,
                    priority    = :priority,
                    due_date    = :due_date
                WHERE task_id = :task_id
            ");
            // ↑ Fixed: was `asssigned_to` (3 s's), `:priorty` (missing i)
            $stmt->execute([
                ':assigned_to' => $assigned_to,
                ':title'       => $title,
                ':description' => $description,
                ':priority'    => $priority,
                ':due_date'    => $due_date,
                ':task_id'     => $task_id,
            ]);
            return [
                'success' => true,
                'message' => 'Task updated successfully',
                'task_id' => $task_id,
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    // Status update is separate — called by assigned member OR manager
    public function updateStatus($task_id, $status): array {
        try {
            $stmt = $this->db->prepare("
                UPDATE task SET status = :status WHERE task_id = :task_id
            ");
            $stmt->execute([':status' => $status, ':task_id' => $task_id]);
            return ['success' => true, 'message' => 'Status updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    public function deleteTask($task_id): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM task WHERE task_id = ?");
            $stmt->execute([$task_id]);
            return ['success' => true, 'message' => 'Task deleted successfully'];
            // ↑ Fixed: was 'messsage' (3 s's)
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    // Returns a task row — used for ownership/assignment checks
    public function getTaskById($task_id, $project_id): array|false {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*,
                       u.name  AS assigned_user_name,
                       u.email AS assigned_user_email
                FROM task t
                LEFT JOIN user u ON t.assigned_to = u.user_id
                WHERE t.task_id = :task_id AND t.project_id = :project_id
            ");
            $stmt->execute([':task_id' => $task_id, ':project_id' => $project_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getAllTasks($project_id): array {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*,
                       u.name        AS assigned_user_name,
                       u.email       AS assigned_user_email,
                       u.userProfile AS assigned_user_avatar
                FROM task t
                LEFT JOIN user u ON t.assigned_to = u.user_id
                WHERE t.project_id = :project_id
                ORDER BY
                    FIELD(t.priority, 'critical','high','medium','low'),
                    t.due_date ASC
            ");
            $stmt->execute([':project_id' => $project_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}