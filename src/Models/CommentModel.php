<?php

require_once __DIR__ . '/Database.php';

class CommentModel {
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
        $sql = "
        CREATE TABLE IF NOT EXISTS task_comment (
            comment_id   INT AUTO_INCREMENT PRIMARY KEY,
            task_id      INT NOT NULL,
            author_id    INT NOT NULL,
            author_type  ENUM('admin','user') NOT NULL DEFAULT 'admin',
            body         TEXT NOT NULL,
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES task(task_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Error creating comment table: " . $e->getMessage());
        }
    }

    // Add a comment
    public function addComment(int $task_id, int $author_id, string $author_type, string $body): array {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO task_comment (task_id, author_id, author_type, body)
                VALUES (:task_id, :author_id, :author_type, :body)
            ");
            $stmt->execute([
                ':task_id'     => $task_id,
                ':author_id'   => $author_id,
                ':author_type' => $author_type,
                ':body'        => $body,
            ]);
            return ['success' => true, 'comment_id' => (int) $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Get all comments for a task, with author name joined
    public function getComments(int $task_id): array {
        try {
            // Union admin + user tables so we get the author name regardless of type
            $stmt = $this->db->prepare("
                SELECT
                    c.comment_id,
                    c.task_id,
                    c.author_id,
                    c.author_type,
                    c.body,
                    c.created_at,
                    COALESCE(a.email, u.name) AS author_name,
                    u.userProfile             AS author_avatar
                FROM task_comment c
                LEFT JOIN admin a ON c.author_type = 'admin' AND a.id     = c.author_id
                LEFT JOIN user  u ON c.author_type = 'user'  AND u.user_id = c.author_id
                WHERE c.task_id = :task_id
                ORDER BY c.created_at ASC
            ");
            $stmt->execute([':task_id' => $task_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    // Delete a comment — caller must verify ownership
    public function deleteComment(int $comment_id): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM task_comment WHERE comment_id = ?");
            $stmt->execute([$comment_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Get single comment (for ownership check)
    public function getComment(int $comment_id): array|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM task_comment WHERE comment_id = ?");
            $stmt->execute([$comment_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }
}