<?php

require_once __DIR__ . '/../Models/ProjectMemberModel.php';
require_once __DIR__ . '/../Models/ProjectModel.php';
require_once __DIR__ . '/../Models/OrganizationModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/ActivityLogger.php'; // ← added

class ProjectMemberController {
    private ProjectMemberModel $projectMember;
    private ProjectModel       $project;
    private OrganizationModel  $organization;

    public function __construct() {
        $this->projectMember = new ProjectMemberModel();
        $this->project       = new ProjectModel();
        $this->organization  = new OrganizationModel();
    }

    public function addMember() {
        if (!AuthMiddleware::isLoggedIn()) Response(401, false, "Unauthorized");
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['project_id'], $data['user_id'])) {
            Response(400, false, "project_id and user_id are required");
        }

        $project_id = (int) $data['project_id'];
        $user_id    = (int) $data['user_id'];
        $role       = in_array($data['role'] ?? '', ['manager', 'member']) ? $data['role'] : 'member';

        $this->assertProjectOwnership($project_id);

        if ($this->projectMember->isMember($project_id, $user_id)) {
            Response(409, false, "User is already a member of this project");
        }

        $result  = $this->projectMember->addMember($project_id, $user_id, $role);
        $org_id  = $this->getOrgIdFromProject($project_id);

        // ── Log ──────────────────────────────────────────────────────
        ActivityLogger::log('added_project_member', 'member', $org_id, $user_id, "project #{$project_id}");

        Response(201, true, $result['message']);
    }

    public function removeMember() {
        if (!AuthMiddleware::isLoggedIn()) Response(401, false, "Unauthorized");
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['project_id'], $data['user_id'])) {
            Response(400, false, "project_id and user_id are required");
        }

        $project_id = (int) $data['project_id'];
        $user_id    = (int) $data['user_id'];

        $this->assertProjectOwnership($project_id);

        if (!$this->projectMember->isMember($project_id, $user_id)) {
            Response(404, false, "User is not a member of this project");
        }

        $result = $this->projectMember->removeMember($project_id, $user_id);
        $org_id = $this->getOrgIdFromProject($project_id);

        // ── Log ──────────────────────────────────────────────────────
        ActivityLogger::log('removed_project_member', 'member', $org_id, $user_id, "project #{$project_id}");

        Response(200, true, $result['message']);
    }

    public function getMembers() {
        if (!AuthMiddleware::isLoggedIn()) Response(401, false, "Unauthorized");
        header('Content-Type: application/json');

        $project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
        if (!$project_id) Response(400, false, "project_id is required");

        $this->assertProjectOwnership($project_id);

        $members = $this->projectMember->getMembers($project_id);
        Response(200, true, "Members fetched successfully", $members);
    }

    public function changeRole() {
        if (!AuthMiddleware::isLoggedIn()) Response(401, false, "Unauthorized");
        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['project_id'], $data['user_id'], $data['role'])) {
            Response(400, false, "project_id, user_id and role are required");
        }

        $project_id = (int) $data['project_id'];
        $user_id    = (int) $data['user_id'];
        $role       = $data['role'];

        if (!in_array($role, ['manager', 'member'])) {
            Response(400, false, "Role must be 'manager' or 'member'");
        }

        $this->assertProjectOwnership($project_id);

        if (!$this->projectMember->isMember($project_id, $user_id)) {
            Response(404, false, "User is not a member of this project");
        }

        $result = $this->projectMember->changeRole($project_id, $user_id, $role);
        $org_id = $this->getOrgIdFromProject($project_id);

        // ── Log ──────────────────────────────────────────────────────
        ActivityLogger::log('changed_member_role', 'member', $org_id, $user_id, "→ {$role} in project #{$project_id}");

        Response(200, true, $result['message']);
    }

    private function assertProjectOwnership(int $project_id): void {
        $project = $this->project->getProjectById($project_id);
        if (!$project) Response(404, false, "Project not found");

        $admin_id        = AuthMiddleware::adminId();
        $organization_id = AuthMiddleware::organization($this->organization, $admin_id);

        if ((int) $project['organization_id'] !== (int) $organization_id) {
            Response(403, false, "You do not have permission to manage this project");
        }
    }

    private function getOrgIdFromProject(int $project_id): int {
        $project = $this->project->getProjectById($project_id);
        return (int) ($project['organization_id'] ?? 0);
    }
}