<?php

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/UserMIddleware.php';
require_once __DIR__ . '/../Models/AttachmentModel.php';
require_once __DIR__ . '/../Models/TaskModel.php';
require_once __DIR__ . '/../Service/CloudinaryService.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/ActivityLogger.php'; // ← added

class AttachmentController {
    private AttachmentModel   $attachment;
    private TaskModel         $task;
    private CloudinaryService $cloudinary;

    public function __construct() {
        $this->attachment = new AttachmentModel();
        $this->task       = new TaskModel();
        $this->cloudinary = new CloudinaryService();
    }

    public function getAttachments(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $task_id    = (int) ($_GET['task_id']    ?? 0);
        $project_id = (int) ($_GET['project_id'] ?? 0);
        if (!$task_id || !$project_id) Response(400, false, 'task_id and project_id are required');

        $task = $this->task->getTaskById($task_id, $project_id);
        if (!$task) Response(404, false, 'Task not found');

        Response(200, true, 'Attachments fetched', $this->attachment->getAttachments($task_id));
    }

    public function uploadAttachment(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $task_id    = (int) ($_POST['task_id']    ?? 0);
        $project_id = (int) ($_POST['project_id'] ?? 0);
        if (!$task_id || !$project_id) Response(400, false, 'task_id and project_id are required');

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response(400, false, 'No file uploaded or upload error');
        }

        $task = $this->task->getTaskById($task_id, $project_id);
        if (!$task) Response(404, false, 'Task not found');

        $file      = $_FILES['file'];
        $fileName  = $file['name'];
        $fileSize  = $file['size'];
        $fileType  = $file['type'];
        $tmpPath   = $file['tmp_name'];

        if ($fileSize > 10 * 1024 * 1024) Response(400, false, 'File size must be under 10 MB');

        $allowedTypes = [
            'image/jpeg','image/png','image/gif','image/webp',
            'application/pdf','application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain','text/csv',
        ];
        if (!in_array($fileType, $allowedTypes)) Response(400, false, 'File type not allowed');

        try {
            $uploaded = $this->cloudinary->uploadImage($tmpPath, 'workhub/attachments');
            [$author_id, $author_type] = $this->getAuthor();

            $result = $this->attachment->addAttachment(
                $task_id, $fileName, $uploaded['url'], $uploaded['public_id'],
                $fileType, $fileSize, $author_id, $author_type
            );

            if (!$result['success']) {
                $this->cloudinary->deleteImage($uploaded['public_id']);
                Response(500, false, $result['message']);
            }

            // ── Log ──────────────────────────────────────────────────
            $org_id = $this->getOrgIdFromProject($project_id);
            ActivityLogger::log('uploaded_attachment', 'attachment', $org_id, (int) $result['attachment_id'], $fileName);

            Response(201, true, 'File uploaded', $this->attachment->getAttachments($task_id));
        } catch (\Exception $e) {
            Response(500, false, 'Upload failed: ' . $e->getMessage());
        }
    }

    public function deleteAttachment(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $data          = json_decode(file_get_contents('php://input'), true) ?? [];
        $attachment_id = (int) ($data['attachment_id'] ?? 0);
        if (!$attachment_id) Response(400, false, 'attachment_id is required');

        $attachment = $this->attachment->getAttachment($attachment_id);
        if (!$attachment) Response(404, false, 'Attachment not found');

        [$author_id, $author_type] = $this->getAuthor();
        if ((int) $attachment['uploaded_by'] !== $author_id || $attachment['uploaded_type'] !== $author_type) {
            Response(403, false, 'You can only delete your own attachments');
        }

        if (!empty($attachment['public_id'])) {
            try { $this->cloudinary->deleteImage($attachment['public_id']); } catch (\Exception $e) {}
        }

        $result = $this->attachment->deleteAttachment($attachment_id);
        if (!$result['success']) Response(500, false, $result['message']);

        Response(200, true, 'Attachment deleted');
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