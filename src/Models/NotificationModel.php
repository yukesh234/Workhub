<?php

require_once __DIR__ . '/Database.php';

class NotificationModel {
    private PDO $db;
    private static bool $tableChecked = false;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (!self::$tableChecked) {
            $this->createTable();
            self::$tableChecked = true;
        }
    }

    private function createTable(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS notification (
                notification_id  SERIAL PRIMARY KEY,
                user_id          INT NOT NULL,
                task_id          INT NOT NULL,
                type             VARCHAR(30) NOT NULL DEFAULT 'due_soon'
                                     CHECK (type IN ('due_soon','overdue','assigned','status_changed')),
                message          TEXT NOT NULL,
                is_read          BOOLEAN NOT NULL DEFAULT FALSE,
                created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES \"user\"(user_id) ON DELETE CASCADE,
                FOREIGN KEY (task_id) REFERENCES task(task_id)     ON DELETE CASCADE
            );
        ");

        // Index for fast unread queries
        $this->db->exec("
            CREATE INDEX IF NOT EXISTS idx_notif_user_unread
            ON notification(user_id, is_read);
        ");
    }

    /**
     * Generate due_soon / overdue notifications for tasks the user owns,
     * then return all notifications for that user (newest first).
     */
    public function generateAndFetch(int $userId): array {
        // Insert missing notifications (due within 3 days OR overdue)
        // NOT EXISTS prevents duplicates per task per type
        $stmt = $this->db->prepare("
            INSERT INTO notification (user_id, task_id, type, message)
            SELECT
                :uid,
                t.task_id,
                CASE
                    WHEN t.due_date < CURRENT_DATE THEN 'overdue'
                    ELSE 'due_soon'
                END,
                CASE
                    WHEN t.due_date < CURRENT_DATE
                        THEN '\"' || t.title || '\" is overdue'
                    WHEN t.due_date = CURRENT_DATE
                        THEN '\"' || t.title || '\" is due today'
                    ELSE '\"' || t.title || '\" is due on ' || TO_CHAR(t.due_date, 'Mon DD')
                END
            FROM task t
            WHERE t.assigned_to = :uid
              AND t.status      != 'completed'
              AND t.due_date    <= CURRENT_DATE + INTERVAL '3 days'
              AND NOT EXISTS (
                  SELECT 1 FROM notification n
                  WHERE n.user_id  = :uid
                    AND n.task_id  = t.task_id
                    AND n.type    IN ('due_soon', 'overdue')
              )
        ");
        $stmt->execute([':uid' => $userId]);

        // Fetch all (latest 50), joining task + project for context
        $stmt = $this->db->prepare("
            SELECT
                n.notification_id,
                n.type,
                n.message,
                n.is_read,
                n.created_at,
                t.task_id,
                t.title      AS task_title,
                t.due_date,
                t.status     AS task_status,
                t.priority,
                p.project_id,
                p.name       AS project_name
            FROM notification n
            JOIN task    t ON t.task_id    = n.task_id
            JOIN project p ON p.project_id = t.project_id
            WHERE n.user_id = :uid
            ORDER BY n.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markRead(int $notificationId, int $userId): bool {
        $stmt = $this->db->prepare("
            UPDATE notification
            SET is_read = TRUE
            WHERE notification_id = :nid AND user_id = :uid
        ");
        return $stmt->execute([':nid' => $notificationId, ':uid' => $userId]);
    }

    public function markAllRead(int $userId): bool {
        $stmt = $this->db->prepare("
            UPDATE notification SET is_read = TRUE
            WHERE user_id = :uid AND is_read = FALSE
        ");
        return $stmt->execute([':uid' => $userId]);
    }

    public function unreadCount(int $userId): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM notification
            WHERE user_id = :uid AND is_read = FALSE
        ");
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Called when a task is assigned to notify the assignee.
     */
    public function createAssignedNotification(int $assigneeId, int $taskId, string $taskTitle): void {
        // Avoid duplicate assigned notifications
        $check = $this->db->prepare("
            SELECT 1 FROM notification
            WHERE user_id = :uid AND task_id = :tid AND type = 'assigned'
        ");
        $check->execute([':uid' => $assigneeId, ':tid' => $taskId]);
        if ($check->fetch()) return;

        $stmt = $this->db->prepare("
            INSERT INTO notification (user_id, task_id, type, message)
            VALUES (:uid, :tid, 'assigned', :msg)
        ");
        $stmt->execute([
            ':uid' => $assigneeId,
            ':tid' => $taskId,
            ':msg' => '"' . $taskTitle . '" has been assigned to you',
        ]);
    }
}