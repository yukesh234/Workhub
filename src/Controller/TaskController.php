<?php

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Models/TaskModel.php';
require_once __DIR__ . '/../Models/ProjectModel.php';
require_once __DIR__ . '/../Models/ProjectMemberModel.php';
require_once __DIR__ . '/../Models/OrganizationModel.php';
require_once __DIR__ . '/../utils/response.php';

class TaskController {
    private TaskModel $task_model;
    private ProjectModel $project;
    private ProjectMemberModel $projectMember;
    private OrganizationModel $organization;

    public function __construct() {
        $this->task_model    = new TaskModel();
        $this->project       = new ProjectModel();
        $this->projectMember = new ProjectMemberModel();
        $this->organization  = new OrganizationModel();
    }

    // ── Who can do what ──────────────────────────────────────────────
    // Create / Edit / Delete task  → admin (project owner) OR project manager
    // Update status only           → assigned member OR admin OR project manager

    // ── POST /api/tasks  { project_id, title, assigned_to, priority, due_date, description? } ──
    public function createTask() {
        if (!AuthMiddleware::isLoggedIn()) Response(401, false, "Unauthorized");

        header('Content-Type: application/json');

        $data        = json_decode(file_get_contents('php://input'), true) ?? [];
        $project_id  = isset($data['project_id'])  ? (int) $data['project_id']  : 0;
        $assigned_to = isset($data['assigned_to'])  ? (int) $data['assigned_to'] : null;
        $title       = trim($data['title']       ?? '');
        $description = trim($data['description'] ?? '') ?: null;
        $status      = $data['status']   ?? 'pending';
        $priority    = $data['priority'] ?? 'medium';
        $due_date    = trim($data['due_date'] ?? '');

        if (!$project_id || $title === '' || !$assigned_to) {
            Response(400, false, "project_id, title and assigned_to are required");
        }

        if (!in_array($status,   ['pending','in_progress','in_review','completed'])) {
            Response(400, false, "Invalid status value");
        }
        if (!in_array($priority, ['low','medium','high','critical'])) {
            Response(400, false, "Invalid priority value");
        }

        // Due date must be today or future
        if ($due_date !== '') {
            if ((new DateTime()) > (new DateTime($due_date))) {
                Response(400, false, "Due date cannot be in the past");
            }
        }

        // Must be admin who owns the project OR a manager in the project
        $this->assertCanManageTask($project_id);

        $result = $this->task_model->createTask(
            $project_id, $assigned_to, $title,
            $description, $status, $priority, $due_date ?: null
        );

        if (!$result['success']) Response(500, false, "DB error: " . $result['message']);

        Response(201, true, "Task created successfully", ['task_id' => $result['task_id']]);
    }

    // ── PUT /api/tasks  { task_id, project_id, title, assigned_to, priority, due_date, description? } ──
    public function updateTask() {
        if (!AuthMiddleware::isLoggedIn()) Response(401, false, "Unauthorized");

        header('Content-Type: application/json');

        $data        = json_decode(file_get_contents('php://input'), true) ?? [];
        $task_id     = isset($data['task_id'])     ? (int) $data['task_id']     : 0;
        $project_id  = isset($data['project_id'])  ? (int) $data['project_id']  : 0;
        $assigned_to = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        $title       = trim($data['title']       ?? '');
        $description = trim($data['description'] ?? '') ?: null;
        $priority    = $data['priority'] ?? 'medium';
        $due_date    = trim($data['due_date'] ?? '');

        if (!$task_id || !$project_id || $title === '') {
            Response(400, false, "task_id, project_id and title are required");
        }

        if (!in_array($priority, ['low','medium','high','critical'])) {
            Response(400, false, "Invalid priority value");
        }

        if ($due_date !== '') {
            if ((new DateTime()) > (new DateTime($due_date))) {
                Response(400, false, "Due date cannot be in the past");
            }
        }

        $this->assertCanManageTask($project_id);

        // Make sure task actually belongs to this project
        $task = $this->task_model->getTaskById($task_id, $project_id);
        if (!$task) Response(404, false, "Task not found");

        $result = $this->task_model->updateTask(
            $task_id, $assigned_to, $title,
            $description, $priority, $due_date ?: null
        );

        if (!$result['success']) Response(500, false, "DB error: " . $result['message']);

        Response(200, true, "Task updated successfully", [
            'task_id'     => $task_id,
            'title'       => $title,
            'assigned_to' => $assigned_to,
            'description' => $description,
            'priority'    => $priority,
            'due_date'    => $due_date,
        ]);
    }

    // ── PATCH /api/tasks/status  { task_id, project_id, status } ────
    // Allowed: assigned member, project manager, admin
    public function updateStatus() {
        if (!AuthMiddleware::isLoggedIn()) Response(401, false, "Unauthorized");

        header('Content-Type: application/json');

        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $task_id    = isset($data['task_id'])    ? (int) $data['task_id']    : 0;
        $project_id = isset($data['project_id']) ? (int) $data['project_id'] : 0;
        $status     = $data['status'] ?? '';

        if (!$task_id || !$project_id || $status === '') {
            Response(400, false, "task_id, project_id and status are required");
        }

        if (!in_array($status, ['pending','in_progress','in_review','completed'])) {
            Response(400, false, "Invalid status value");
        }

        $task = $this->task_model->getTaskById($task_id, $project_id);
        if (!$task) Response(404, false, "Task not found");

        // Allow: any project member, manager, or org admin
        // (status updates are a team activity — no reason to restrict to assigned only)
        $admin_id = AuthMiddleware::adminId();
        $org_id   = AuthMiddleware::organization($this->organization, $admin_id);
        $project  = $this->project->getProjectById($project_id);

        $isProjectOwner  = $project && (int) $project['organization_id'] === (int) $org_id;
        $isProjectMember = $this->projectMember->isMember($project_id, $admin_id);

        if (!$isProjectOwner && !$isProjectMember) {
            Response(403, false, "You must be a member of this project to update task status");
        }

        $result = $this->task_model->updateStatus($task_id, $status);
        if (!$result['success']) Response(500, false, $result['message']);

        Response(200, true, "Status updated to '{$status}'", ['task_id' => $task_id, 'status' => $status]);
    }

    // ── DELETE /api/tasks  { task_id, project_id } ──────────────────
    public function deleteTask() {
        if (!AuthMiddleware::isLoggedIn()) Response(401, false, "Unauthorized");

        header('Content-Type: application/json');

        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $task_id    = isset($data['task_id'])    ? (int) $data['task_id']    : 0;
        $project_id = isset($data['project_id']) ? (int) $data['project_id'] : 0;

        if (!$task_id || !$project_id) {
            Response(400, false, "task_id and project_id are required");
        }

        $this->assertCanManageTask($project_id);

        $task = $this->task_model->getTaskById($task_id, $project_id);
        if (!$task) Response(404, false, "Task not found");

        $result = $this->task_model->deleteTask($task_id);
        if (!$result['success']) Response(500, false, $result['message']);

        Response(200, true, "Task deleted successfully");
    }

    // ── GET /api/tasks?project_id=X ─────────────────────────────────
    public function getAllTasks() {
        if (!AuthMiddleware::isLoggedIn()) Response(401, false, "Unauthorized");

        header('Content-Type: application/json');

        // ↑ Fixed: was reading from JSON body — GET requests use query params
        $project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

        if (!$project_id) Response(400, false, "project_id is required");

        $this->assertCanManageTask($project_id);

        $result = $this->task_model->getAllTasks($project_id);
        Response(200, true, "Tasks fetched successfully", $result);
    }

    // ── GET /api/tasks/single?task_id=X&project_id=Y ────────────────
    public function getTaskById() {
        if (!AuthMiddleware::isLoggedIn()) Response(401, false, "Unauthorized");

        header('Content-Type: application/json');

        // ↑ Fixed: was reading from JSON body
        $task_id    = isset($_GET['task_id'])    ? (int) $_GET['task_id']    : 0;
        $project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

        if (!$task_id || !$project_id) {
            Response(400, false, "task_id and project_id are required");
        }

        $result = $this->task_model->getTaskById($task_id, $project_id);
        if (!$result) Response(404, false, "Task not found");

        Response(200, true, "Task fetched successfully", $result);
    }

    // ── Private helper: admin owns the project OR is a project manager ──
    private function assertCanManageTask(int $project_id): void {
        $admin_id = AuthMiddleware::adminId();
        $org_id   = AuthMiddleware::organization($this->organization, $admin_id);
        $project  = $this->project->getProjectById($project_id);

        if (!$project) Response(404, false, "Project not found");

        $isProjectOwner   = (int) $project['organization_id'] === (int) $org_id;
        $isProjectManager = $this->projectMember->isManager($project_id, $admin_id);

        if (!$isProjectOwner && !$isProjectManager) {
            Response(403, false, "Only the project owner or a project manager can manage tasks");
        }
    }
}