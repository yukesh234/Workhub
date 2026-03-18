<?php

require_once __DIR__ . '/../Models/ActivityLogModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/UserMIddleware.php';

/**
 * Static one-liner for activity logging.
 * Usage (in any controller method, after a successful DB operation):
 *
 *   ActivityLogger::log('created_task', 'task', $org_id, $task_id, $task_title);
 *   ActivityLogger::log('deleted_member', 'member', $org_id, $user_id, $user_name);
 */
class ActivityLogger {
    private static ?ActivityLogModel $model = null;

    private static function model(): ActivityLogModel {
        if (!self::$model) self::$model = new ActivityLogModel();
        return self::$model;
    }

    public static function log(
        string  $action,
        string  $entityType,
        int     $orgId,
        ?int    $entityId    = null,
        ?string $entityLabel = null
    ): void {
        if (AuthMiddleware::isLoggedIn()) {
            $id   = AuthMiddleware::adminId();
            $type = 'admin';
            $name = $_SESSION['admin_email'] ?? 'Admin';
        } elseif (UserAuthMiddleware::isLoggedIn()) {
            $id   = UserAuthMiddleware::userId();
            $type = 'user';
            $name = $_SESSION['user_name'] ?? 'Team Member';
        } else {
            return; // not authenticated — skip
        }

        self::model()->log($id, $type, $name, $action, $entityType, $orgId, $entityId, $entityLabel);
    }
}