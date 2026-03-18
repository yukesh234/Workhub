<?php

class UserAuthMiddleware {

    public static function checkAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            header('Location: ' . $base . '/user/login');
            exit();
        }
    }

    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    public static function userId(): int {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    public static function role(): string {
        return $_SESSION['role'] ?? 'member';
    }

    public static function isManager(): bool {
        return ($_SESSION['role'] ?? '') === 'manager';
    }

    public static function organizationId(): int {
        return (int) ($_SESSION['organization_id'] ?? 0);
    }
    public static function requirePasswordChanged(): void {
    if (isset($_SESSION['user_id']) && empty($_SESSION['password_changed'])) {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        header('Location: ' . $base . '/user/change-password');
        exit();
    }
}
}