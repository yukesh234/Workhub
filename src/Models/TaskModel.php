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

    private function createTaskTable(): void
    {
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
            CREATE TABLE IF NOT EXISTS task (
                task_id     SERIAL PRIMARY KEY,
                project_id  INT NOT NULL,
                assigned_to INT DEFAULT NULL,
                title       VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                status      VARCHAR(20) NOT NULL DEFAULT 'pending'
                                CHECK (status IN ('pending','in_progress','in_review','completed')),
                priority    VARCHAR(10) NOT NULL DEFAULT 'medium'
                                CHECK (priority IN ('low','medium','high','critical')),
                due_date    DATE DEFAULT NULL,
                created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (project_id)  REFERENCES project(project_id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_to) REFERENCES \"user\"(user_id)   ON DELETE SET NULL
            );
        ");

        $this->db->exec("
            DROP TRIGGER IF EXISTS task_updated_at ON task;
            CREATE TRIGGER task_updated_at
                BEFORE UPDATE ON task
                FOR EACH ROW EXECUTE FUNCTION set_updated_at();
        ");
    }

    public function createTask(
        int     $project_id,
        ?int    $assigned_to,
        string  $title,
        ?string $description,
        string  $status,
        string  $priority,
        ?string $due_date
    ): array {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO task (project_id, assigned_to, title, description, status, priority, due_date)
                VALUES (:project_id, :assigned_to, :title, :description, :status, :priority, :due_date)
                RETURNING task_id
            ");

            $stmt->execute([
                ':project_id'  => $project_id,
                ':assigned_to' => $assigned_to,
                ':title'       => $title,
                ':description' => $description,
                ':status'      => $status,
                ':priority'    => $priority,
                ':due_date'    => $due_date,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'message' => 'Task created successfully',
                'task_id' => (int) $row['task_id'],
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    public function updateTask(
        int     $task_id,
        ?int    $assigned_to,
        string  $title,
        ?string $description,
        string  $priority,
        ?string $due_date
    ): array {
        try {
            $stmt = $this->db->prepare("
                UPDATE task
                SET assigned_to = :assigned_to,
                    title       = :title,
                    description = :description,
                    priority    = :priority,
                    due_date    = :due_date
                WHERE task_id = :task_id
            ");

            $stmt->execute([
                ':assigned_to' => $assigned_to,
                ':title'       => $title,
                ':description' => $description,
                ':priority'    => $priority,
                ':due_date'    => $due_date,
                ':task_id'     => $task_id,
            ]);

            return ['success' => true, 'message' => 'Task updated successfully', 'task_id' => $task_id];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    public function updateStatus(int $task_id, string $status): array
    {
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

    public function deleteTask(int $task_id): array
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM task WHERE task_id = ?");
            $stmt->execute([$task_id]);
            return ['success' => true, 'message' => 'Task deleted successfully'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    public function getTaskById(int $task_id, int $project_id): array|false
    {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*,
                       u.name  AS assigned_user_name,
                       u.email AS assigned_user_email
                FROM task t
                LEFT JOIN \"user\" u ON t.assigned_to = u.user_id
                WHERE t.task_id = :task_id AND t.project_id = :project_id
            ");
            $stmt->execute([':task_id' => $task_id, ':project_id' => $project_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return false;
        }
    }

    public function getAllTasks(int $project_id): array
    {
        try {
            // FIELD() doesn't exist in Postgres — use CASE WHEN for custom sort order
            $stmt = $this->db->prepare("
                SELECT t.*,
                       u.name           AS assigned_user_name,
                       u.email          AS assigned_user_email,
                       u.\"userProfile\" AS assigned_user_avatar
                FROM task t
                LEFT JOIN \"user\" u ON t.assigned_to = u.user_id
                WHERE t.project_id = :project_id
                ORDER BY
                    CASE t.priority
                        WHEN 'critical' THEN 1
                        WHEN 'high'     THEN 2
                        WHEN 'medium'   THEN 3
                        WHEN 'low'      THEN 4
                        ELSE 5
                    END,
                    t.due_date ASC NULLS LAST
            ");
            $stmt->execute([':project_id' => $project_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return [];
        }
    }
}