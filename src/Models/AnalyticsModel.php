<?php

require_once __DIR__ . '/Database.php';

class AnalyticsModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Admin org-wide analytics 

    public function getOrgSummary(int $org_id): array {
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM \"user\"   WHERE organization_id = $1) AS total_members,
                (SELECT COUNT(*) FROM project    WHERE organization_id = $2) AS total_projects,
                (SELECT COUNT(*) FROM project    WHERE organization_id = $3 AND status = 'active')    AS active_projects,
                (SELECT COUNT(*) FROM project    WHERE organization_id = $4 AND status = 'completed') AS completed_projects,
                (SELECT COUNT(t.task_id) FROM task t JOIN project p ON p.project_id = t.project_id WHERE p.organization_id = $5) AS total_tasks,
                (SELECT COUNT(t.task_id) FROM task t JOIN project p ON p.project_id = t.project_id WHERE p.organization_id = $6 AND t.status = 'completed') AS completed_tasks,
                (SELECT COUNT(t.task_id) FROM task t JOIN project p ON p.project_id = t.project_id WHERE p.organization_id = $7 AND t.due_date < CURRENT_DATE AND t.status != 'completed') AS overdue_tasks
        ");
        $stmt->execute(array_fill(0, 7, $org_id));
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** Tasks created per day for the last N days */
    public function getTaskCreationTrend(int $org_id, int $days = 30): array {
        $stmt = $this->db->prepare("
            SELECT DATE(t.created_at) AS day, COUNT(*) AS count
            FROM task t
            JOIN project p ON p.project_id = t.project_id
            WHERE p.organization_id = $1
              AND t.created_at >= CURRENT_DATE - INTERVAL '1 day' * $2
            GROUP BY DATE(t.created_at)
            ORDER BY day ASC
        ");
        $stmt->execute([$org_id, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Task status breakdown across the org */
    public function getTaskStatusBreakdown(int $org_id): array {
        $stmt = $this->db->prepare("
            SELECT t.status, COUNT(*) AS count
            FROM task t
            JOIN project p ON p.project_id = t.project_id
            WHERE p.organization_id = $1
            GROUP BY t.status
        ");
        $stmt->execute([$org_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Task priority breakdown */
    public function getTaskPriorityBreakdown(int $org_id): array {
        $stmt = $this->db->prepare("
            SELECT t.priority, COUNT(*) AS count
            FROM task t
            JOIN project p ON p.project_id = t.project_id
            WHERE p.organization_id = $1
            GROUP BY t.priority
            ORDER BY CASE t.priority
                WHEN 'critical' THEN 1
                WHEN 'high'     THEN 2
                WHEN 'medium'   THEN 3
                WHEN 'low'      THEN 4
                ELSE 5
            END
        ");
        $stmt->execute([$org_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Top members by tasks completed */
    public function getTopPerformers(int $org_id, int $limit = 5): array {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.name, u.\"userProfile\",
                   COUNT(t.task_id)                                                        AS total_tasks,
                   SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END)                AS done,
                   ROUND(
                       SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END)::numeric
                       / COUNT(t.task_id) * 100
                   )                                                                       AS pct
            FROM \"user\" u
            JOIN task t    ON t.assigned_to  = u.user_id
            JOIN project p ON p.project_id   = t.project_id
            WHERE u.organization_id = $1
            GROUP BY u.user_id, u.name, u.\"userProfile\"
            HAVING COUNT(t.task_id) > 0
            ORDER BY done DESC
            LIMIT $2
        ");
        $stmt->bindValue(1, $org_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Project-level task completion rates */
    public function getProjectProgress(int $org_id): array {
        $stmt = $this->db->prepare("
            SELECT p.project_id, p.name, p.status,
                   COUNT(t.task_id)                                             AS total,
                   SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END)     AS done,
                   CASE WHEN COUNT(t.task_id) > 0
                        THEN ROUND(
                            SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END)::numeric
                            / COUNT(t.task_id) * 100
                        )
                        ELSE 0
                   END AS pct
            FROM project p
            LEFT JOIN task t ON t.project_id = p.project_id
            WHERE p.organization_id = $1
            GROUP BY p.project_id, p.name, p.status
            ORDER BY pct DESC
        ");
        $stmt->execute([$org_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Member-scoped analytics ───────────────────────────────────────

    /** Full profile + summary for one member */
    public function getMemberProfile(int $user_id, int $org_id): array {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.name, u.email, u.role, u.\"userProfile\", u.created_at,
                COUNT(t.task_id)                                                                    AS total_tasks,
                SUM(CASE WHEN t.status = 'completed'   THEN 1 ELSE 0 END)                          AS completed,
                SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END)                          AS in_progress,
                SUM(CASE WHEN t.status = 'in_review'   THEN 1 ELSE 0 END)                          AS in_review,
                SUM(CASE WHEN t.status = 'pending'     THEN 1 ELSE 0 END)                          AS pending,
                SUM(CASE WHEN t.due_date < CURRENT_DATE AND t.status != 'completed' THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN t.priority = 'critical'  THEN 1 ELSE 0 END)                          AS critical,
                SUM(CASE WHEN t.priority = 'high'      THEN 1 ELSE 0 END)                          AS high_p,
                SUM(CASE WHEN t.priority = 'medium'    THEN 1 ELSE 0 END)                          AS medium_p,
                SUM(CASE WHEN t.priority = 'low'       THEN 1 ELSE 0 END)                          AS low_p
            FROM \"user\" u
            LEFT JOIN task t ON t.assigned_to = u.user_id
            WHERE u.user_id = $1 AND u.organization_id = $2
            GROUP BY u.user_id, u.name, u.email, u.role, u.\"userProfile\", u.created_at
        ");
        $stmt->execute([$user_id, $org_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** Tasks completed per day for this member (last N days) */
    public function getMemberCompletionTrend(int $user_id, int $days = 30): array {
        $stmt = $this->db->prepare("
            SELECT DATE(updated_at) AS day, COUNT(*) AS count
            FROM task
            WHERE assigned_to = $1
              AND status = 'completed'
              AND updated_at >= CURRENT_DATE - INTERVAL '1 day' * $2
            GROUP BY DATE(updated_at)
            ORDER BY day ASC
        ");
        $stmt->execute([$user_id, $days]);
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
            WHERE t.assigned_to = $1 AND p.organization_id = $2
            ORDER BY
                CASE t.status
                    WHEN 'in_progress' THEN 1
                    WHEN 'in_review'   THEN 2
                    WHEN 'pending'     THEN 3
                    WHEN 'completed'   THEN 4
                    ELSE 5
                END,
                CASE t.priority
                    WHEN 'critical' THEN 1
                    WHEN 'high'     THEN 2
                    WHEN 'medium'   THEN 3
                    WHEN 'low'      THEN 4
                    ELSE 5
                END,
                t.due_date ASC
        ");
        $stmt->execute([$user_id, $org_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Projects this member is part of */
    public function getMemberProjects(int $user_id): array {
        $stmt = $this->db->prepare("
            SELECT p.project_id, p.name, p.status, pm.role,
                   COUNT(t.task_id)                                         AS total,
                   SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS done
            FROM project_members pm
            JOIN project p ON p.project_id = pm.project_id
            LEFT JOIN task t ON t.project_id = p.project_id AND t.assigned_to = $1
            WHERE pm.user_id = $2
            GROUP BY p.project_id, p.name, p.status, pm.role, p.created_at
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Manager project-scoped analytics ─────────────────────────────

    public function getProjectSummary(int $project_id): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*)                                                                        AS total,
                SUM(CASE WHEN status = 'completed'   THEN 1 ELSE 0 END)                        AS done,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END)                        AS in_progress,
                SUM(CASE WHEN status = 'in_review'   THEN 1 ELSE 0 END)                        AS in_review,
                SUM(CASE WHEN status = 'pending'     THEN 1 ELSE 0 END)                        AS pending,
                SUM(CASE WHEN due_date < CURRENT_DATE AND status != 'completed' THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN priority = 'critical'  THEN 1 ELSE 0 END)                        AS critical,
                SUM(CASE WHEN priority = 'high'      THEN 1 ELSE 0 END)                        AS high_p,
                SUM(CASE WHEN priority = 'medium'    THEN 1 ELSE 0 END)                        AS medium_p,
                SUM(CASE WHEN priority = 'low'       THEN 1 ELSE 0 END)                        AS low_p
            FROM task
            WHERE project_id = $1
        ");
        $stmt->execute([$project_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** Tasks completed per day for a project (last N days) */
    public function getProjectCompletionTrend(int $project_id, int $days = 30): array {
        $stmt = $this->db->prepare("
            SELECT DATE(updated_at) AS day, COUNT(*) AS count
            FROM task
            WHERE project_id = $1
              AND status = 'completed'
              AND updated_at >= CURRENT_DATE - INTERVAL '1 day' * $2
            GROUP BY DATE(updated_at)
            ORDER BY day ASC
        ");
        $stmt->execute([$project_id, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Per-member stats within a project */
    public function getMemberStats(int $project_id): array {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.name, u.\"userProfile\", pm.role,
                   COUNT(t.task_id)                                                            AS total,
                   SUM(CASE WHEN t.status = 'completed'   THEN 1 ELSE 0 END)                  AS done,
                   SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END)                  AS in_progress,
                   SUM(CASE WHEN t.due_date < CURRENT_DATE AND t.status != 'completed' THEN 1 ELSE 0 END) AS overdue
            FROM project_members pm
            JOIN \"user\" u ON u.user_id = pm.user_id
            LEFT JOIN task t ON t.assigned_to = u.user_id AND t.project_id = pm.project_id
            WHERE pm.project_id = $1
            GROUP BY u.user_id, u.name, u.\"userProfile\", pm.role
            ORDER BY done DESC
        ");
        $stmt->execute([$project_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}