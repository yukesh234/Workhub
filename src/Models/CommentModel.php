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
            comment_id  SERIAL PRIMARY KEY,
            task_id     INT NOT NULL,
            author_id   INT NOT NULL,
            author_type VARCHAR(10) NOT NULL DEFAULT 'admin'
                            CHECK (author_type IN ('admin','user')),
            body        TEXT NOT NULL,
            created_at  TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES task(task_id) ON DELETE CASCADE
        )";
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Error creating comment table: " . $e->getMessage());
        }
    }

    public function addComment(int $task_id, int $author_id, string $author_type, string $body): array {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO task_comment (task_id, author_id, author_type, body)
                VALUES (:task_id, :author_id, :author_type, :body)
                RETURNING comment_id
            ");
            $stmt->execute([
                ':task_id'     => $task_id,
                ':author_id'   => $author_id,
                ':author_type' => $author_type,
                ':body'        => $body,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ['success' => true, 'comment_id' => (int) $row['comment_id']];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all comments for a task with the author name joined.
     * PostgreSQL: no backtick quoting; use standard double-quotes if needed.
     * COALESCE picks the admin email when author_type = 'admin', otherwise the user's name.
     */
    public function getComments(int $task_id): array {
    try {
        $stmt = $this->db->prepare("
            SELECT
                c.comment_id,
                c.task_id,
                c.author_id,
                c.author_type,
                c.body,
                c.created_at,
                CASE
                    WHEN c.author_type = 'admin' THEN SPLIT_PART(a.email, '@', 1)
                    ELSE u.name
                END AS author_name,
                u.\"userProfile\" AS author_avatar
            FROM task_comment c
            LEFT JOIN admin a
                   ON c.author_type = 'admin'
                  AND a.id = c.author_id
            LEFT JOIN \"user\" u
                   ON c.author_type = 'user'
                  AND u.user_id = c.author_id
            WHERE c.task_id = :task_id
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([':task_id' => $task_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('getComments failed: ' . $e->getMessage());
        return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
    }
}

    /** Delete a comment — caller must verify ownership */
    public function deleteComment(int $comment_id): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM task_comment WHERE comment_id = ?");
            $stmt->execute([$comment_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            error_log("Error deleting comment: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Get single comment (for ownership check) */
    public function getComment(int $comment_id): array|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM task_comment WHERE comment_id = ?");
            $stmt->execute([$comment_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching comment: " . $e->getMessage());
            return false;
        }
    }
}