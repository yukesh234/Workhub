<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../Middleware/UserMIddleware.php';

class MemberController
{
    private UserModel $user_model;

    public function __construct()
    {
        $this->user_model = new UserModel();
    }

    private function getBaseUrl(): string
    {
        return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    }

    // ── Show login form ───────────────────────────────────────────────
    public function showLoginForm(): void
    {
        // Already logged in → redirect based on password status
        if (UserAuthMiddleware::isLoggedIn()) {
            if (!$_SESSION['password_changed']) {
                header("Location: " . $this->getBaseUrl() . "/user/change-password");
            } else {
                header("Location: " . $this->getBaseUrl() . "/user/dashboard");
            }
            exit();
        }
        require_once __DIR__ . '/../../views/Member-auth/login.php';
    }

    // ── Process login ─────────────────────────────────────────────────
    public function processLogin(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $result = $this->user_model->handle_member_login(
            trim($_POST['email']    ?? ''),
            trim($_POST['password'] ?? '')
        );

        if ($result['success']) {
            // Set all session values
            $_SESSION['user_id']          = $result['data']['user_id'];
            $_SESSION['role']             = $result['data']['role'];
            $_SESSION['organization_id']  = $result['data']['organization_id'];
            $_SESSION['user_name']        = $result['data']['name'];
            $_SESSION['password_changed'] = (int) $result['data']['password_changed'];

            // Force password change on first login
            if (!$result['data']['password_changed']) {
                header("Location: " . $this->getBaseUrl() . "/user/change-password");
            } else {
                header("Location: " . $this->getBaseUrl() . "/user/dashboard");
            }
            exit();
        }

        $error = $result['message'];
        require_once __DIR__ . '/../../views/Member-auth/login.php';
    }

    // ── Show change password form ─────────────────────────────────────
    public function showChangePasswordForm(): void
    {
        UserAuthMiddleware::checkAuth();
        // If they already changed it, redirect to dashboard
        if (!empty($_SESSION['password_changed'])) {
            header("Location: " . $this->getBaseUrl() . "/user/dashboard");
            exit();
        }
        require_once __DIR__ . '/../../views/Member-auth/changepassword.php';
    }

    // ── Process password change  POST JSON ────────────────────────────
    // Body: { password, confirm }
    public function changePassword(): void
    {
        UserAuthMiddleware::checkAuth();

        header('Content-Type: application/json');

        $data     = json_decode(file_get_contents('php://input'), true) ?? [];
        $password = trim($data['password'] ?? '');
        $confirm  = trim($data['confirm']  ?? '');

        if (empty($password) || empty($confirm)) {
            Response(400, false, "Both fields are required");
        }

        if (strlen($password) < 8) {
            Response(400, false, "Password must be at least 8 characters");
        }

        if ($password !== $confirm) {
            Response(400, false, "Passwords do not match");
        }

        $result = $this->user_model->updatePassword($password, $_SESSION['user_id']);

        if (!$result['success']) {
            Response(500, false, $result['message']);
        }

        // Update session so the guard doesn't redirect again
        $_SESSION['password_changed'] = 1;

        Response(200, true, "Password changed successfully");
    }

    // ── Logout ────────────────────────────────────────────────────────
    public function logout(): void
    {
        session_unset();
        session_destroy();
        header("Location: " . $this->getBaseUrl() . "/user/login");
        exit();
    }
}