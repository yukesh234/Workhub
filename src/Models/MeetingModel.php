<?php

require_once __DIR__ . '/Database.php';

class MeetingModel {
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
        CREATE TABLE IF NOT EXISTS meeting (
            meeting_id   INT AUTO_INCREMENT PRIMARY KEY,
            project_id   INT NOT NULL,
            room_name    VARCHAR(255) NOT NULL,
            title        VARCHAR(255) DEFAULT NULL,
            started_by   INT NOT NULL,
            starter_type ENUM('admin','user') NOT NULL DEFAULT 'admin',
            status       ENUM('active','ended') NOT NULL DEFAULT 'active',
            started_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ended_at     TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (project_id) REFERENCES project(project_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        try {
            $this->db->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Error creating meeting table: " . $e->getMessage());
        }
    }

    // Start a new meeting — ends any existing active meeting for this project first
    public function startMeeting(
        int    $project_id,
        string $room_name,
        int    $started_by,
        string $starter_type,
        string $title = ''
    ): array {
        try {
            // End any currently active meeting for this project
            $this->db->prepare("
                UPDATE meeting SET status='ended', ended_at=NOW()
                WHERE project_id=:pid AND status='active'
            ")->execute([':pid' => $project_id]);

            $stmt = $this->db->prepare("
                INSERT INTO meeting (project_id, room_name, title, started_by, starter_type, status)
                VALUES (:project_id, :room_name, :title, :started_by, :starter_type, 'active')
            ");
            $stmt->execute([
                ':project_id'   => $project_id,
                ':room_name'    => $room_name,
                ':title'        => $title ?: 'Project Meeting',
                ':started_by'   => $started_by,
                ':starter_type' => $starter_type,
            ]);

            return [
                'success'    => true,
                'meeting_id' => (int) $this->db->lastInsertId(),
                'room_name'  => $room_name,
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Get the active meeting for a project (null if none)
    public function getActiveMeeting(int $project_id): array|null {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM meeting
                WHERE project_id = :project_id AND status = 'active'
                ORDER BY started_at DESC
                LIMIT 1
            ");
            $stmt->execute([':project_id' => $project_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    // End a meeting by ID
    public function endMeeting(int $meeting_id, int $ended_by): array {
        try {
            $stmt = $this->db->prepare("
                UPDATE meeting SET status='ended', ended_at=NOW()
                WHERE meeting_id=:meeting_id AND status='active'
            ");
            $stmt->execute([':meeting_id' => $meeting_id]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Meeting not found or already ended'];
            }
            return ['success' => true, 'message' => 'Meeting ended'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Recent meetings for a project (last 10)
    public function getMeetingHistory(int $project_id): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM meeting
                WHERE project_id = :project_id
                ORDER BY started_at DESC
                LIMIT 10
            ");
            $stmt->execute([':project_id' => $project_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}