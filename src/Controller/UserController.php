<?php

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../Middleware/UserMIddleware.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/TaskModel.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/ActivityLogger.php'; 

class UserController {
    private UserModel $user;
    private TaskModel $task;
    private PDO       $db;

    public function __construct() {
        $this->user = new UserModel();
        $this->task = new TaskModel();
        $this->db   = Database::getInstance()->getConnection();
    }

    public function getMyProjects(): void {
        header('Content-Type: application/json');
        $user_id = UserAuthMiddleware::userId();

        try {
            $stmt = $this->db->prepare("
                SELECT p.project_id, p.name, p.description, p.status, p.created_at,
                       pm.role AS my_role,
                       COUNT(t.task_id)              AS task_count,
                       SUM(t.status = 'completed')   AS done_count
                FROM project_members pm
                JOIN project p  ON p.project_id  = pm.project_id
                LEFT JOIN task t ON t.project_id  = pm.project_id
                WHERE pm.user_id = :user_id
                GROUP BY p.project_id, pm.role
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([':user_id' => $user_id]);
            Response(200, true, 'Projects fetched', $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            Response(500, false, 'DB error: ' . $e->getMessage());
        }
    }

    public function getMyTasks(): void {
        header('Content-Type: application/json');
        $user_id   = UserAuthMiddleware::userId();
        $isManager = UserAuthMiddleware::isManager();

        try {
            if ($isManager) {
                $stmt = $this->db->prepare("
                    SELECT t.*, p.name AS project_name,
                           u.name AS assigned_user_name, u.email AS assigned_user_email, u.userProfile AS assigned_user_avatar
                    FROM task t
                    JOIN project p          ON p.project_id  = t.project_id
                    JOIN project_members pm ON pm.project_id = t.project_id
                    LEFT JOIN user u        ON u.user_id     = t.assigned_to
                    WHERE pm.user_id = :user_id AND pm.role = 'manager'
                    ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.due_date ASC
                ");
            } else {
                $stmt = $this->db->prepare("
                    SELECT t.*, p.name AS project_name,
                           u.name AS assigned_user_name, u.email AS assigned_user_email, u.userProfile AS assigned_user_avatar
                    FROM task t
                    JOIN project p   ON p.project_id = t.project_id
                    LEFT JOIN user u ON u.user_id    = t.assigned_to
                    WHERE t.assigned_to = :user_id
                    ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.due_date ASC
                ");
            }
            $stmt->execute([':user_id' => $user_id]);
            Response(200, true, 'Tasks fetched', $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            Response(500, false, 'DB error: ' . $e->getMessage());
        }
    }

    public function updateTaskStatus(): void {
        header('Content-Type: application/json');
        $user_id = UserAuthMiddleware::userId();

        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $task_id    = (int) ($data['task_id']    ?? 0);
        $project_id = (int) ($data['project_id'] ?? 0);
        $status     = $data['status'] ?? '';

        if (!$task_id || !$project_id || !$status) Response(400, false, 'task_id, project_id and status are required');
        if (!in_array($status, ['pending','in_progress','in_review','completed'])) Response(400, false, 'Invalid status value');

        try {
            $taskRow = $this->task->getTaskById($task_id, $project_id);
            if (!$taskRow) Response(404, false, 'Task not found');
            if ((int) $taskRow['assigned_to'] !== $user_id) {
                Response(403, false, 'Only the assigned member can update this task\'s status');
            }

            $result = $this->task->updateStatus($task_id, $status);
            if (!$result['success']) Response(500, false, $result['message']);

            // ── Log ──────────────────────────────────────────────────
            $org_id = (int) ($_SESSION['organization_id'] ?? 0);
            ActivityLogger::log('status_updated', 'task', $org_id, $task_id, $taskRow['title'] . ' → ' . $status);

            Response(200, true, "Status updated to '{$status}'", ['task_id' => $task_id, 'status' => $status]);
        } catch (PDOException $e) {
            Response(500, false, 'DB error: ' . $e->getMessage());
        }
    }

    public function getProjectMembers(): void {
        header('Content-Type: application/json');
        $user_id    = UserAuthMiddleware::userId();
        $project_id = (int) ($_GET['project_id'] ?? 0);
        if (!$project_id) Response(400, false, 'project_id is required');

        try {
            $check = $this->db->prepare("SELECT 1 FROM project_members WHERE project_id=:p AND user_id=:u");
            $check->execute([':p' => $project_id, ':u' => $user_id]);
            if (!$check->fetch()) Response(403, false, 'Access denied');

            $stmt = $this->db->prepare("
                SELECT u.user_id, u.name, u.email, u.userProfile, pm.role
                FROM project_members pm
                JOIN user u ON u.user_id = pm.user_id
                WHERE pm.project_id = :project_id
                ORDER BY pm.role DESC, u.name ASC
            ");
            $stmt->execute([':project_id' => $project_id]);
            Response(200, true, 'Members fetched', $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            Response(500, false, 'DB error: ' . $e->getMessage());
        }
    }

    public function createTask(): void {
        header('Content-Type: application/json');
        if (!UserAuthMiddleware::isManager()) Response(403, false, 'Only managers can create tasks');

        $data        = json_decode(file_get_contents('php://input'), true) ?? [];
        $project_id  = (int)   ($data['project_id']  ?? 0);
        $assigned_to = (int)   ($data['assigned_to']  ?? 0) ?: null;
        $title       = trim($data['title']       ?? '');
        $description = trim($data['description'] ?? '') ?: null;
        $status      = $data['status']   ?? 'pending';
        $priority    = $data['priority'] ?? 'medium';
        $due_date    = $data['due_date'] ?? null;

        if (!$project_id || !$title) Response(400, false, 'project_id and title are required');
        if (!in_array($status,   ['pending','in_progress','in_review','completed'])) Response(400, false, 'Invalid status');
        if (!in_array($priority, ['low','medium','high','critical']))                 Response(400, false, 'Invalid priority');

        $this->assertManagerOfProject($project_id);

        $result = $this->task->createTask($project_id, $assigned_to, $title, $description, $status, $priority, $due_date);
        if (!$result['success']) Response(500, false, $result['message']);

        // ── Log ──────────────────────────────────────────────────────
        $org_id = (int) ($_SESSION['organization_id'] ?? 0);
        ActivityLogger::log('created_task', 'task', $org_id, (int) $result['task_id'], $title);

        Response(201, true, 'Task created', ['task_id' => $result['task_id']]);
    }

    public function updateTask(): void {
        header('Content-Type: application/json');
        if (!UserAuthMiddleware::isManager()) Response(403, false, 'Only managers can edit tasks');

        $data        = json_decode(file_get_contents('php://input'), true) ?? [];
        $task_id     = (int)   ($data['task_id']     ?? 0);
        $project_id  = (int)   ($data['project_id']  ?? 0);
        $assigned_to = (int)   ($data['assigned_to'] ?? 0) ?: null;
        $title       = trim($data['title']       ?? '');
        $description = trim($data['description'] ?? '') ?: null;
        $priority    = $data['priority'] ?? 'medium';
        $due_date    = $data['due_date'] ?? null;

        if (!$task_id || !$project_id || !$title) Response(400, false, 'task_id, project_id and title are required');

        $this->assertManagerOfProject($project_id);

        $result = $this->task->updateTask($task_id, $assigned_to, $title, $description, $priority, $due_date);
        if (!$result['success']) Response(500, false, $result['message']);

        // ── Log ──────────────────────────────────────────────────────
        $org_id = (int) ($_SESSION['organization_id'] ?? 0);
        ActivityLogger::log('updated_task', 'task', $org_id, $task_id, $title);

        Response(200, true, 'Task updated', [
            'task_id' => $task_id, 'title' => $title,
            'assigned_to' => $assigned_to, 'priority' => $priority, 'due_date' => $due_date,
        ]);
    }

    public function deleteTask(): void {
        header('Content-Type: application/json');
        if (!UserAuthMiddleware::isManager()) Response(403, false, 'Only managers can delete tasks');

        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $task_id    = (int) ($data['task_id']    ?? 0);
        $project_id = (int) ($data['project_id'] ?? 0);
        if (!$task_id || !$project_id) Response(400, false, 'task_id and project_id are required');

        $this->assertManagerOfProject($project_id);

        // Grab title before deletion for the log
        $taskRow = $this->task->getTaskById($task_id, $project_id);

        $result = $this->task->deleteTask($task_id);
        if (!$result['success']) Response(500, false, $result['message']);

        // ── Log ──────────────────────────────────────────────────────
        $org_id = (int) ($_SESSION['organization_id'] ?? 0);
        ActivityLogger::log('deleted_task', 'task', $org_id, $task_id, $taskRow['title'] ?? "task #{$task_id}");

        Response(200, true, 'Task deleted');
    }

    public function getProjectDetail(): void {
        header('Content-Type: application/json');
        $user_id    = UserAuthMiddleware::userId();
        $project_id = (int) ($_GET['project_id'] ?? 0);
        if (!$project_id) Response(400, false, 'project_id is required');

        try {
            $stmt = $this->db->prepare("
                SELECT p.*, pm.role AS my_role
                FROM project p
                JOIN project_members pm ON pm.project_id = p.project_id
                WHERE p.project_id = :project_id AND pm.user_id = :user_id
            ");
            $stmt->execute([':project_id' => $project_id, ':user_id' => $user_id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$project) Response(404, false, 'Project not found or access denied');
            Response(200, true, 'Project fetched', $project);
        } catch (PDOException $e) {
            Response(500, false, 'DB error: ' . $e->getMessage());
        }
    }

    public function getProjectTasks(): void {
        header('Content-Type: application/json');
        $user_id    = UserAuthMiddleware::userId();
        $project_id = (int) ($_GET['project_id'] ?? 0);
        if (!$project_id) Response(400, false, 'project_id is required');

        try {
            $check = $this->db->prepare("SELECT role FROM project_members WHERE project_id=:p AND user_id=:u");
            $check->execute([':p' => $project_id, ':u' => $user_id]);
            if (!$check->fetch()) Response(403, false, 'You are not a member of this project');

            $stmt = $this->db->prepare("
                SELECT t.*, u.name AS assigned_user_name, u.email AS assigned_user_email, u.userProfile AS assigned_user_avatar
                FROM task t
                LEFT JOIN user u ON u.user_id = t.assigned_to
                WHERE t.project_id = :project_id
                ORDER BY FIELD(t.priority,'critical','high','medium','low'), t.due_date ASC
            ");
            $stmt->execute([':project_id' => $project_id]);
            Response(200, true, 'Tasks fetched', $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            Response(500, false, 'DB error: ' . $e->getMessage());
        }
    }

    private function assertManagerOfProject(int $project_id): void {
        $user_id = UserAuthMiddleware::userId();
        try {
            $stmt = $this->db->prepare("SELECT 1 FROM project_members WHERE project_id=:p AND user_id=:u AND role='manager'");
            $stmt->execute([':p' => $project_id, ':u' => $user_id]);
            if (!$stmt->fetch()) Response(403, false, 'You are not a manager of this project');
        } catch (PDOException $e) {
            Response(500, false, 'DB error: ' . $e->getMessage());
        }
    }
}