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

        $admin_id = AuthMiddleware::adminId();
        $org_id   = (int) AuthMiddleware::organization(new \OrganizationModel(), $admin_id);
        if (!$org_id) Response(404, false, 'No organisation found');

        Response(200, true, 'Analytics fetched', [
            'summary'           => $this->analytics->getOrgSummary($org_id),
            'task_trend'        => $this->analytics->getTaskCreationTrend($org_id, 30),
            'status_breakdown'  => $this->analytics->getTaskStatusBreakdown($org_id),
            'priority_breakdown'=> $this->analytics->getTaskPriorityBreakdown($org_id),
            'top_performers'    => $this->analytics->getTopPerformers($org_id, 5),
            'project_progress'  => $this->analytics->getProjectProgress($org_id),
        ]);
    }

    public function activityLog(): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkAuth();

        $admin_id    = AuthMiddleware::adminId();
        $org_id      = (int) AuthMiddleware::organization(new \OrganizationModel(), $admin_id);
        if (!$org_id) Response(404, false, 'No organisation found');

        $limit       = min((int) ($_GET['limit']       ?? 50), 100);
        $offset      = (int) ($_GET['offset']      ?? 0);
        $action      = trim($_GET['action']      ?? '');
        $entity_type = trim($_GET['entity_type'] ?? '');
        $actor_type  = trim($_GET['actor_type']  ?? '');

        $logs  = $this->actLog->getLogs($org_id, $limit, $offset, $action, $entity_type, $actor_type);
        $total = $this->actLog->countLogs($org_id);
        $types = $this->actLog->getActionTypes($org_id);

        Response(200, true, 'Activity log fetched', [
            'logs'         => $logs,
            'total'        => $total,
            'action_types' => $types,
        ]);
    }

    public function memberAnalytics(): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkAuth();

        $user_id = (int) ($_GET['user_id'] ?? 0);
        if (!$user_id) Response(400, false, 'user_id is required');

        $admin_id = AuthMiddleware::adminId();
        $org_id   = (int) AuthMiddleware::organization(new \OrganizationModel(), $admin_id);
        if (!$org_id) Response(404, false, 'No organisation found');

        $profile = $this->analytics->getMemberProfile($user_id, $org_id);
        if (!$profile) Response(404, false, 'Member not found in your organisation');

        Response(200, true, 'Member analytics fetched', [
            'profile'          => $profile,
            'completion_trend' => $this->analytics->getMemberCompletionTrend($user_id, 30),
            'tasks'            => $this->analytics->getMemberTasks($user_id, $org_id),
            'projects'         => $this->analytics->getMemberProjects($user_id),
        ]);
    }


    // Project analytics for manager
    public function projectAnalytics(): void {
        header('Content-Type: application/json');
        UserAuthMiddleware::checkAuth();
        UserAuthMiddleware::requirePasswordChanged();

        $project_id = (int) ($_GET['project_id'] ?? 0);
        if (!$project_id) Response(400, false, 'project_id is required');

        // Verify membership
        $db   = \Database::getInstance()->getConnection();
        $chk  = $db->prepare("SELECT role FROM project_members WHERE project_id=? AND user_id=?");
        $chk->execute([$project_id, UserAuthMiddleware::userId()]);
        if (!$chk->fetch()) Response(403, false, 'Access denied');

        Response(200, true, 'Project analytics fetched', [
            'summary'          => $this->analytics->getProjectSummary($project_id),
            'completion_trend' => $this->analytics->getProjectCompletionTrend($project_id, 30),
            'member_stats'     => $this->analytics->getMemberStats($project_id),
        ]);
    }
}