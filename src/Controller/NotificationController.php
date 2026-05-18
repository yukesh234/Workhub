<?php

require_once __DIR__ . '/../Models/NotificationModel.php';
require_once __DIR__ . '/../Middleware/UserMIddleware.php';

class NotificationController {
    private NotificationModel $model;

    public function __construct() {
        $this->model = new NotificationModel();
    }

    /** GET /api/user/notifications */
    public function getNotifications(): void {
        header('Content-Type: application/json');
        if (!UserAuthMiddleware::isLoggedIn()) {
            Response(401, false, 'Unauthorized'); return;
        }
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $data   = $this->model->generateAndFetch($userId);
        Response(200, true, 'Notifications fetched', $data);
    }

    /** PATCH /api/user/notifications/{id}/read */
    public function markRead(): void {
        header('Content-Type: application/json');
        if (!UserAuthMiddleware::isLoggedIn()) {
            Response(401, false, 'Unauthorized'); return;
        }
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $body   = json_decode(file_get_contents('php://input'), true);
        $nid    = (int) ($body['notification_id'] ?? 0);

        if (!$nid) { Response(400, false, 'notification_id required'); return; }

        $ok = $this->model->markRead($nid, $userId);
        $ok ? Response(200, true, 'Marked as read', ['count' => $this->model->unreadCount($userId)])
            : Response(500, false, 'Failed to update');
    }

    /** PATCH /api/user/notifications/read-all */
    public function markAllRead(): void {
        header('Content-Type: application/json');
        if (!UserAuthMiddleware::isLoggedIn()) {
            Response(401, false, 'Unauthorized'); return;
        }
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $this->model->markAllRead($userId);
        Response(200, true, 'All marked as read', ['count' => 0]);
    }

    /** GET /api/user/notifications/unread-count */
    public function unreadCount(): void {
        header('Content-Type: application/json');
        if (!UserAuthMiddleware::isLoggedIn()) {
            Response(401, false, 'Unauthorized'); return;
        }
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        Response(200, true, 'OK', ['count' => $this->model->unreadCount($userId)]);
    }
}