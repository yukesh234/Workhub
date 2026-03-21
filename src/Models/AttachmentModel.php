<?php

require_once __DIR__ . '/Database.php';

class AttachmentModel {
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
        CREATE TABLE IF NOT EXISTS task_attachment (
            attachment_id  INT AUTO_INCREMENT PRIMARY KEY,
            task_id        INT NOT NULL,
            file_name      VARCHAR(255) NOT NULL,
            file_url       VARCHAR(500) NOT NULL,
            public_id      VARCHAR(255),
            file_type      VARCHAR(100),
            file_size      INT DEFAULT 0,
            uploaded_by    INT NOT NULL,
            uploaded_type  ENUM('admin','user') NOT NULL DEFAULT 'admin',
            created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (task_id) REFERENCES task(task_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Error creating attachment table: " . $e->getMessage());
        }
    }

    public function addAttachment(
        int    $task_id,
        string $file_name,
        string $file_url,
        string $public_id,
        string $file_type,
        int    $file_size,
        int    $uploaded_by,
        string $uploaded_type
    ): array {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO task_attachment
                    (task_id, file_name, file_url, public_id, file_type, file_size, uploaded_by, uploaded_type)
                VALUES
                    (:task_id, :file_name, :file_url, :public_id, :file_type, :file_size, :uploaded_by, :uploaded_type)
            ");
            $stmt->execute([
                ':task_id'       => $task_id,
                ':file_name'     => $file_name,
                ':file_url'      => $file_url,
                ':public_id'     => $public_id,
                ':file_type'     => $file_type,
                ':file_size'     => $file_size,
                ':uploaded_by'   => $uploaded_by,
                ':uploaded_type' => $uploaded_type,
            ]);
            return ['success' => true, 'attachment_id' => (int) $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getAttachments(int $task_id): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM task_attachment
                WHERE task_id = :task_id
                ORDER BY created_at DESC
            ");
            $stmt->execute([':task_id' => $task_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAttachment(int $attachment_id): array|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM task_attachment WHERE attachment_id = ?");
            $stmt->execute([$attachment_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deleteAttachment(int $attachment_id): array {
        try {
            $stmt = $this->db->prepare("DELETE FROM task_attachment WHERE attachment_id = ?");
            $stmt->execute([$attachment_id]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    public function getProjectFiles(int $project_id): array {
    try {
        $stmt = $this->db->prepare("
            SELECT
                a.attachment_id, a.file_name, a.file_url, a.file_type,
                a.file_size, a.created_at, a.public_id,
                t.task_id, t.title AS task_title
            FROM task_attachment a
            JOIN task t ON t.task_id = a.task_id
            WHERE t.project_id = :project_id
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([':project_id' => $project_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
}