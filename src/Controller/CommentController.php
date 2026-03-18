<?php

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/UserMIddleware.php';
require_once __DIR__ . '/../Models/CommentModel.php';
require_once __DIR__ . '/../Models/TaskModel.php';
require_once __DIR__ . '/../utils/response.php';

class CommentController {
    private CommentModel $comment;
    private TaskModel    $task;

    public function __construct() {
        $this->comment = new CommentModel();
        $this->task    = new TaskModel();
    }

    // ── GET /api/tasks/comments?task_id=X&project_id=Y ────────────────
    public function getComments(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $task_id    = (int) ($_GET['task_id']    ?? 0);
        $project_id = (int) ($_GET['project_id'] ?? 0);

        if (!$task_id || !$project_id) {
            Response(400, false, 'task_id and project_id are required');
        }

        // Verify task exists in this project
        $task = $this->task->getTaskById($task_id, $project_id);
        if (!$task) Response(404, false, 'Task not found');

        $comments = $this->comment->getComments($task_id);
        Response(200, true, 'Comments fetched', $comments);
    }

    // ── POST /api/tasks/comments  { task_id, project_id, body } ───────
    public function addComment(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $task_id    = (int)   ($data['task_id']    ?? 0);
        $project_id = (int)   ($data['project_id'] ?? 0);
        $body       = trim($data['body'] ?? '');

        if (!$task_id || !$project_id || $body === '') {
            Response(400, false, 'task_id, project_id and body are required');
        }

        $task = $this->task->getTaskById($task_id, $project_id);
        if (!$task) Response(404, false, 'Task not found');

        [$author_id, $author_type] = $this->getAuthor();

        $result = $this->comment->addComment($task_id, $author_id, $author_type, $body);
        if (!$result['success']) Response(500, false, $result['message']);

        $comments = $this->comment->getComments($task_id);
        Response(201, true, 'Comment added', $comments);
    }

    // ── DELETE /api/tasks/comments  { comment_id } ────────────────────
    public function deleteComment(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $comment_id = (int) ($data['comment_id'] ?? 0);

        if (!$comment_id) Response(400, false, 'comment_id is required');

        $comment = $this->comment->getComment($comment_id);
        if (!$comment) Response(404, false, 'Comment not found');

        // Only the author can delete their own comment
        [$author_id, $author_type] = $this->getAuthor();
        if ((int) $comment['author_id'] !== $author_id || $comment['author_type'] !== $author_type) {
            Response(403, false, 'You can only delete your own comments');
        }

        $result = $this->comment->deleteComment($comment_id);
        if (!$result['success']) Response(500, false, $result['message']);

        Response(200, true, 'Comment deleted');
    }

    // ── Helpers ───────────────────────────────────────────────────────
    private function requireAnyAuth(): void {
        if (!AuthMiddleware::isLoggedIn() && !UserAuthMiddleware::isLoggedIn()) {
            Response(401, false, 'Unauthorized');
        }
    }

    private function getAuthor(): array {
        if (AuthMiddleware::isLoggedIn()) {
            return [AuthMiddleware::adminId(), 'admin'];
        }
        return [UserAuthMiddleware::userId(), 'user'];
    }
}