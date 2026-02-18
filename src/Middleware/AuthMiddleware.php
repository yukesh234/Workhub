<?php

class AuthMiddleware {
    private static function getBaseUrl() {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return $basePath;
    }

    // Check if user is authenticated (returns boolean)
    public static function isLoggedIn(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }

    // Redirect if not authenticated (middleware for protected routes)
    public static function checkAuth(): bool {
        if (!self::isLoggedIn()) {
            header("Location: " . self::getBaseUrl() . "/login");
            exit();
        }
        return true;
    }

    public static function adminId(): int
    {
        if (!isset($_SESSION['admin_id'])) {
            throw new Exception("unauthorized", 401);
        }
        return $_SESSION['admin_id'];
    }
}