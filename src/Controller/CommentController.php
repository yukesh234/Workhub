<?php

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/UserMIddleware.php';
require_once __DIR__ . '/../Models/CommentModel.php';
require_once __DIR__ . '/../Models/TaskModel.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/ActivityLogger.php'; // ← added

class CommentController {
    private CommentModel $comment;
    private TaskModel    $task;

    public function __construct() {
        $this->comment = new CommentModel();
        $this->task    = new TaskModel();
    }

    public function getComments(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $task_id    = (int) ($_GET['task_id']    ?? 0);
        $project_id = (int) ($_GET['project_id'] ?? 0);
        if (!$task_id || !$project_id) Response(400, false, 'task_id and project_id are required');

        $task = $this->task->getTaskById($task_id, $project_id);
        if (!$task) Response(404, false, 'Task not found');

        Response(200, true, 'Comments fetched', $this->comment->getComments($task_id));
    }

    public function addComment(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $task_id    = (int) ($data['task_id']    ?? 0);
        $project_id = (int) ($data['project_id'] ?? 0);
        $body       = trim($data['body'] ?? '');

        if (!$task_id || !$project_id || $body === '') {
            Response(400, false, 'task_id, project_id and body are required');
        }

        $task = $this->task->getTaskById($task_id, $project_id);
        if (!$task) Response(404, false, 'Task not found');

        [$author_id, $author_type] = $this->getAuthor();
        $result = $this->comment->addComment($task_id, $author_id, $author_type, $body);
        if (!$result['success']) Response(500, false, $result['message']);

        // ── Log ──────────────────────────────────────────────────────
        $org_id = $this->getOrgIdFromProject($project_id);
        ActivityLogger::log('added_comment', 'comment', $org_id, (int) $result['comment_id'], $task['title']);

        Response(201, true, 'Comment added', $this->comment->getComments($task_id));
    }

    public function deleteComment(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $comment_id = (int) ($data['comment_id'] ?? 0);
        if (!$comment_id) Response(400, false, 'comment_id is required');

        $comment = $this->comment->getComment($comment_id);
        if (!$comment) Response(404, false, 'Comment not found');

        [$author_id, $author_type] = $this->getAuthor();
        if ((int) $comment['author_id'] !== $author_id || $comment['author_type'] !== $author_type) {
            Response(403, false, 'You can only delete your own comments');
        }

        $result = $this->comment->deleteComment($comment_id);
        if (!$result['success']) Response(500, false, $result['message']);

        Response(200, true, 'Comment deleted');
    }

    private function requireAnyAuth(): void {
        if (!AuthMiddleware::isLoggedIn() && !UserAuthMiddleware::isLoggedIn()) {
            Response(401, false, 'Unauthorized');
        }
    }

    private function getAuthor(): array {
        if (AuthMiddleware::isLoggedIn()) return [AuthMiddleware::adminId(), 'admin'];
        return [UserAuthMiddleware::userId(), 'user'];
    }

    private function getOrgIdFromProject(int $project_id): int {
        try {
            $db   = \Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT organization_id FROM project WHERE project_id=?");
            $stmt->execute([$project_id]);
            return (int) ($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) { return 0; }
    }
}