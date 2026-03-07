<?php

require_once __DIR__ . '/../Models/ProjectMemberModel.php';
require_once __DIR__ . '/../Models/ProjectModel.php';
require_once __DIR__ . '/../Models/OrganizationModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class ProjectMemberController {

    private ProjectMemberModel $projectMember;
    private ProjectModel $project;
    private OrganizationModel $organization;

    public function __construct() {
        $this->projectMember = new ProjectMemberModel();
        $this->project       = new ProjectModel();
        $this->organization  = new OrganizationModel();
    }

    // POST /api/project/members/add
    // { project_id, user_id, role? }
    public function addMember() {
        if (!AuthMiddleware::isLoggedIn()) {
            Response(401, false, "Unauthorized");
        }

        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['project_id'], $data['user_id'])) {
            Response(400, false, "project_id and user_id are required");
        }

        $project_id = (int) $data['project_id'];
        $user_id    = (int) $data['user_id'];
        $role       = in_array($data['role'] ?? '', ['manager', 'member'])
                        ? $data['role']
                        : 'member';

        // Verify project belongs to this admin's org
        $this->assertProjectOwnership($project_id);

        // Prevent duplicate
        if ($this->projectMember->isMember($project_id, $user_id)) {
            Response(409, false, "User is already a member of this project");
        }

        $result = $this->projectMember->addMember($project_id, $user_id, $role);
        Response(201, true, $result['message']);
    }

    // DELETE /api/project/members/remove
    // { project_id, user_id }
    public function removeMember() {
        if (!AuthMiddleware::isLoggedIn()) {
            Response(401, false, "Unauthorized");
        }

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
        Response(200, true, $result['message']);
    }

    // GET /api/project/members?project_id=X
    public function getMembers() {
        if (!AuthMiddleware::isLoggedIn()) {
            Response(401, false, "Unauthorized");
        }

        header('Content-Type: application/json');

        $project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

        if ($project_id === 0) {
            Response(400, false, "project_id is required");
        }

        $this->assertProjectOwnership($project_id);

        $members = $this->projectMember->getMembers($project_id);
        Response(200, true, "Members fetched successfully", $members);
    }

    // PATCH /api/project/members/role
    // { project_id, user_id, role }
    public function changeRole() {
        if (!AuthMiddleware::isLoggedIn()) {
            Response(401, false, "Unauthorized");
        }

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
        Response(200, true, $result['message']);
    }

    // ── Private helper ──────────────────────────────────────────────
    // Fetches the project and verifies it belongs to the logged-in admin's org.
    // Calls Response() and exits automatically if the check fails.
    private function assertProjectOwnership(int $project_id): void {
        $project = $this->project->getProjectById($project_id);

        if (!$project) {
            Response(404, false, "Project not found");
        }

        $admin_id        = AuthMiddleware::adminId();
        $organization_id = AuthMiddleware::organization($this->organization, $admin_id);

        if ((int) $project['organization_id'] !== (int) $organization_id) {
            Response(403, false, "You do not have permission to manage this project");
        }
    }
}