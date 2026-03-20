<?php

require_once __DIR__ . '/Database.php';

class AnalyticsModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Admin org-wide analytics ──────────────────────────────────────

    public function getOrgSummary(int $org_id): array {
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM user    WHERE organization_id=:o)  AS total_members,
                (SELECT COUNT(*) FROM project WHERE organization_id=:o2) AS total_projects,
                (SELECT COUNT(*) FROM project WHERE organization_id=:o3 AND status='active') AS active_projects,
                (SELECT COUNT(*) FROM project WHERE organization_id=:o4 AND status='completed') AS completed_projects,
                (SELECT COUNT(t.task_id) FROM task t JOIN project p ON p.project_id=t.project_id WHERE p.organization_id=:o5) AS total_tasks,
                (SELECT COUNT(t.task_id) FROM task t JOIN project p ON p.project_id=t.project_id WHERE p.organization_id=:o6 AND t.status='completed') AS completed_tasks,
                (SELECT COUNT(t.task_id) FROM task t JOIN project p ON p.project_id=t.project_id WHERE p.organization_id=:o7 AND t.due_date < CURDATE() AND t.status != 'completed') AS overdue_tasks
        ");
        $stmt->execute([':o'=>$org_id,':o2'=>$org_id,':o3'=>$org_id,':o4'=>$org_id,':o5'=>$org_id,':o6'=>$org_id,':o7'=>$org_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** Tasks created per day for the last N days */
    public function getTaskCreationTrend(int $org_id, int $days = 30): array {
        $stmt = $this->db->prepare("
            SELECT DATE(t.created_at) AS day, COUNT(*) AS count
            FROM task t
            JOIN project p ON p.project_id = t.project_id
            WHERE p.organization_id = :org_id
              AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(t.created_at)
            ORDER BY day ASC
        ");
        $stmt->execute([':org_id' => $org_id, ':days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Task status breakdown across the org */
    public function getTaskStatusBreakdown(int $org_id): array {
        $stmt = $this->db->prepare("
            SELECT t.status, COUNT(*) AS count
            FROM task t JOIN project p ON p.project_id=t.project_id
            WHERE p.organization_id = :org_id
            GROUP BY t.status
        ");
        $stmt->execute([':org_id' => $org_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Task priority breakdown */
    public function getTaskPriorityBreakdown(int $org_id): array {
        $stmt = $this->db->prepare("
            SELECT t.priority, COUNT(*) AS count
            FROM task t JOIN project p ON p.project_id=t.project_id
            WHERE p.organization_id = :org_id
            GROUP BY t.priority
            ORDER BY FIELD(t.priority,'critical','high','medium','low')
        ");
        $stmt->execute([':org_id' => $org_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Top members by tasks completed */
    public function getTopPerformers(int $org_id, int $limit = 5): array {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.name, u.userProfile,
                   COUNT(t.task_id)                                  AS total_tasks,
                   SUM(t.status='completed')                         AS done,
                   ROUND(SUM(t.status='completed')/COUNT(t.task_id)*100) AS pct
            FROM user u
            JOIN task t ON t.assigned_to = u.user_id
            JOIN project p ON p.project_id = t.project_id
            WHERE u.organization_id = :org_id
            GROUP BY u.user_id
            HAVING total_tasks > 0
            ORDER BY done DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':org_id', $org_id, PDO::PARAM_INT);
        $stmt->bindValue(':lim',    $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Project-level task completion rates */
    public function getProjectProgress(int $org_id): array {
        $stmt = $this->db->prepare("
            SELECT p.project_id, p.name, p.status,
                   COUNT(t.task_id)              AS total,
                   SUM(t.status='completed')     AS done,
                   CASE WHEN COUNT(t.task_id)>0
                        THEN ROUND(SUM(t.status='completed')/COUNT(t.task_id)*100)
                        ELSE 0 END               AS pct
            FROM project p
            LEFT JOIN task t ON t.project_id = p.project_id
            WHERE p.organization_id = :org_id
            GROUP BY p.project_id
            ORDER BY pct DESC
        ");
        $stmt->execute([':org_id' => $org_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Member-scoped analytics ───────────────────────────────────────

    /** Full profile + summary for one member */
    public function getMemberProfile(int $user_id, int $org_id): array {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.name, u.email, u.role, u.userProfile, u.created_at,
                COUNT(t.task_id)                                          AS total_tasks,
                SUM(t.status = 'completed')                               AS completed,
                SUM(t.status = 'in_progress')                             AS in_progress,
                SUM(t.status = 'in_review')                               AS in_review,
                SUM(t.status = 'pending')                                 AS pending,
                SUM(t.due_date < CURDATE() AND t.status != 'completed')   AS overdue,
                SUM(t.priority = 'critical')                              AS critical,
                SUM(t.priority = 'high')                                  AS high_p,
                SUM(t.priority = 'medium')                                AS medium_p,
                SUM(t.priority = 'low')                                   AS low_p
            FROM user u
            LEFT JOIN task t ON t.assigned_to = u.user_id
            WHERE u.user_id = :uid AND u.organization_id = :org
            GROUP BY u.user_id
        ");
        $stmt->execute([':uid' => $user_id, ':org' => $org_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** Tasks completed per day for this member (last 30 days) */
    public function getMemberCompletionTrend(int $user_id, int $days = 30): array {
        $stmt = $this->db->prepare("
            SELECT DATE(updated_at) AS day, COUNT(*) AS count
            FROM task
            WHERE assigned_to = :uid
              AND status = 'completed'
              AND updated_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(updated_at)
            ORDER BY day ASC
        ");
        $stmt->execute([':uid' => $user_id, ':days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** All tasks assigned to this member with project info */
    public function getMemberTasks(int $user_id, int $org_id): array {
        $stmt = $this->db->prepare("
            SELECT t.task_id, t.title, t.status, t.priority, t.due_date,
                   t.created_at, t.updated_at,
                   p.name AS project_name, p.project_id
            FROM task t
            JOIN project p ON p.project_id = t.project_id
            WHERE t.assigned_to = :uid AND p.organization_id = :org
            ORDER BY FIELD(t.status,'in_progress','in_review','pending','completed'),
                     FIELD(t.priority,'critical','high','medium','low'),
                     t.due_date ASC
        ");
        $stmt->execute([':uid' => $user_id, ':org' => $org_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Projects this member is part of */
    public function getMemberProjects(int $user_id): array {
        $stmt = $this->db->prepare("
            SELECT p.project_id, p.name, p.status, pm.role,
                   COUNT(t.task_id)            AS total,
                   SUM(t.status='completed')   AS done
            FROM project_members pm
            JOIN project p ON p.project_id = pm.project_id
            LEFT JOIN task t ON t.project_id = p.project_id AND t.assigned_to = :uid
            WHERE pm.user_id = :uid2
            GROUP BY p.project_id, pm.role
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([':uid' => $user_id, ':uid2' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Manager project-scoped analytics ─────────────────────────────

    public function getProjectSummary(int $project_id): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*)                          AS total,
                SUM(status='completed')           AS done,
                SUM(status='in_progress')         AS in_progress,
                SUM(status='in_review')           AS in_review,
                SUM(status='pending')             AS pending,
                SUM(due_date < CURDATE() AND status != 'completed') AS overdue,
                SUM(priority='critical')          AS critical,
                SUM(priority='high')              AS high_p,
                SUM(priority='medium')            AS medium_p,
                SUM(priority='low')               AS low_p
            FROM task WHERE project_id = :pid
        ");
        $stmt->execute([':pid' => $project_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** Tasks completed per day for a project (last 30 days) */
    public function getProjectCompletionTrend(int $project_id, int $days = 30): array {
        $stmt = $this->db->prepare("
            SELECT DATE(updated_at) AS day, COUNT(*) AS count
            FROM task
            WHERE project_id = :pid
              AND status = 'completed'
              AND updated_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(updated_at)
            ORDER BY day ASC
        ");
        $stmt->execute([':pid' => $project_id, ':days' => $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Per-member stats within a project */
    public function getMemberStats(int $project_id): array {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.name, u.userProfile, pm.role,
                   COUNT(t.task_id)              AS total,
                   SUM(t.status='completed')     AS done,
                   SUM(t.status='in_progress')   AS in_progress,
                   SUM(t.due_date < CURDATE() AND t.status != 'completed') AS overdue
            FROM project_members pm
            JOIN user u ON u.user_id = pm.user_id
            LEFT JOIN task t ON t.assigned_to = u.user_id AND t.project_id = pm.project_id
            WHERE pm.project_id = :pid
            GROUP BY u.user_id, pm.role
            ORDER BY done DESC
        ");
        $stmt->execute([':pid' => $project_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}