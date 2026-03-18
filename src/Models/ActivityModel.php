<?php

require_once __DIR__ . '/Database.php';

class ActivityLogModel {
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
        CREATE TABLE IF NOT EXISTS activity_log (
            log_id       INT AUTO_INCREMENT PRIMARY KEY,
            actor_id     INT NOT NULL,
            actor_type   ENUM('admin','user') NOT NULL,
            actor_name   VARCHAR(255) NOT NULL,
            action       VARCHAR(100) NOT NULL,
            entity_type  VARCHAR(50)  NOT NULL,
            entity_id    INT          DEFAULT NULL,
            entity_label VARCHAR(255) DEFAULT NULL,
            org_id       INT          NOT NULL,
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_org  (org_id),
            INDEX idx_actor(actor_id, actor_type),
            INDEX idx_ts   (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Log an activity.
     *
     * @param int    $actorId     admin.id or user.user_id
     * @param string $actorType   'admin' | 'user'
     * @param string $actorName   display name
     * @param string $action      e.g. 'created_task', 'deleted_member'
     * @param string $entityType  e.g. 'task', 'project', 'member', 'meeting'
     * @param int    $orgId       organisation scope
     * @param int|null $entityId
     * @param string|null $entityLabel  human-readable name of the entity
     */
    public function log(
        int    $actorId,
        string $actorType,
        string $actorName,
        string $action,
        string $entityType,
        int    $orgId,
        ?int   $entityId    = null,
        ?string $entityLabel = null
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO activity_log
                    (actor_id, actor_type, actor_name, action, entity_type, entity_id, entity_label, org_id)
                VALUES
                    (:actor_id,:actor_type,:actor_name,:action,:entity_type,:entity_id,:entity_label,:org_id)
            ");
            $stmt->execute([
                ':actor_id'    => $actorId,
                ':actor_type'  => $actorType,
                ':actor_name'  => $actorName,
                ':action'      => $action,
                ':entity_type' => $entityType,
                ':entity_id'   => $entityId,
                ':entity_label'=> $entityLabel,
                ':org_id'      => $orgId,
            ]);
        } catch (\Throwable $e) {
            // Logging should never crash the main flow
            error_log('ActivityLog error: ' . $e->getMessage());
        }
    }

    /**
     * Get recent logs for an organisation with optional filters.
     */
    public function getLogs(
        int    $org_id,
        int    $limit  = 50,
        int    $offset = 0,
        string $action = '',
        string $entity_type = '',
        string $actor_type  = ''
    ): array {
        $where   = ['org_id = :org_id'];
        $params  = [':org_id' => $org_id];

        if ($action)      { $where[] = 'action = :action';           $params[':action']      = $action;      }
        if ($entity_type) { $where[] = 'entity_type = :entity_type'; $params[':entity_type'] = $entity_type; }
        if ($actor_type)  { $where[] = 'actor_type = :actor_type';   $params[':actor_type']  = $actor_type;  }

        $sql = "SELECT * FROM activity_log WHERE " . implode(' AND ', $where)
             . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $params[':limit']  = $limit;
        $params[':offset'] = $offset;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countLogs(int $org_id): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM activity_log WHERE org_id=?");
        $stmt->execute([$org_id]);
        return (int) $stmt->fetchColumn();
    }

    /** Distinct action types for filter dropdown */
    public function getActionTypes(int $org_id): array {
        $stmt = $this->db->prepare("SELECT DISTINCT action FROM activity_log WHERE org_id=? ORDER BY action");
        $stmt->execute([$org_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}