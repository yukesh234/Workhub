<?php

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/UserMIddleware.php';
require_once __DIR__ . '/../Models/AnalyticsModel.php';
require_once __DIR__ . '/../Models/ActivityLogModel.php';
require_once __DIR__ . '/../utils/response.php';

class AnalyticsController {
    private AnalyticsModel  $analytics;
    private ActivityLogModel $actLog;

    public function __construct() {
        $this->analytics = new AnalyticsModel();
        $this->actLog    = new ActivityLogModel();
    }

    // ── GET /api/analytics/admin ──────────────────────────────────────
    // Full org-wide analytics for the admin dashboard
    public function adminOverview(): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkAuth();

        $org = AuthMiddleware::organization(
            new \OrganizationModel(),
            AuthMiddleware::adminId()
        );
        if (!$org) {
            Response(404, false, 'No organisation found');
            return;
        }

        $org_id = is_array($org) ? (int) $org['organization_id'] : (int) $org;

        Response(200, true, 'Analytics fetched', [
            'summary'           => $this->analytics->getOrgSummary($org_id),
            'task_trend'        => $this->analytics->getTaskCreationTrend($org_id, 30),
            'status_breakdown'  => $this->analytics->getTaskStatusBreakdown($org_id),
            'priority_breakdown'=> $this->analytics->getTaskPriorityBreakdown($org_id),
            'top_performers'    => $this->analytics->getTopPerformers($org_id, 5),
            'project_progress'  => $this->analytics->getProjectProgress($org_id),
        ]);
    }

    // ── GET /api/analytics/activity?limit=50&offset=0 ─────────────────
    // Activity log for admin
    public function activityLog(): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkAuth();

        $org = AuthMiddleware::organization(
            new \OrganizationModel(),
            AuthMiddleware::adminId()
        );
        if (!$org) {
            Response(404, false, 'No organisation found');
            return;
        }

        $org_id      = is_array($org) ? (int) $org['organization_id'] : (int) $org;
        $limit       = min((int) ($_GET['limit']       ?? 50), 100);
        $offset      = (int) ($_GET['offset']      ?? 0);
        $action      = trim($_GET['action']      ?? '');
        $entity_type = trim($_GET['entity_type'] ?? '');
        $actor_type  = trim($_GET['actor_type']  ?? '');

        $logs   = $this->actLog->getLogs($org_id, $limit, $offset, $action, $entity_type, $actor_type);
        $total  = $this->actLog->countLogs($org_id);
        $types  = $this->actLog->getActionTypes($org_id);

        Response(200, true, 'Activity log fetched', [
            'logs'         => $logs,
            'total'        => $total,
            'action_types' => $types,
        ]);
    }

    // ── GET /api/analytics/project?project_id=X ───────────────────────
    // Project analytics for manager
    public function projectAnalytics(): void {
        header('Content-Type: application/json');
        UserAuthMiddleware::checkAuth();
        UserAuthMiddleware::requirePasswordChanged();

        $project_id = (int) ($_GET['project_id'] ?? 0);
        if (!$project_id) {
            Response(400, false, 'project_id is required');
            return;
        }

        // Verify membership
        $db  = \Database::getInstance()->getConnection();
        $chk = $db->prepare("SELECT role FROM project_members WHERE project_id=? AND user_id=?");
        $chk->execute([$project_id, UserAuthMiddleware::userId()]);
        if (!$chk->fetch()) {
            Response(403, false, 'Access denied');
            return;
        }

        Response(200, true, 'Project analytics fetched', [
            'summary'          => $this->analytics->getProjectSummary($project_id),
            'completion_trend' => $this->analytics->getProjectCompletionTrend($project_id, 30),
            'member_stats'     => $this->analytics->getMemberStats($project_id),
        ]);
    }
}