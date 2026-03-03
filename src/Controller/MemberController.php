<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Models/UserModel.php';

class MemberController
{
    private UserModel $user_model;

    public function __construct()
    {
        $this->user_model = new UserModel();
    }

    private function getBaseUrl()
    {
        return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    }

    public function showLoginForm()
    {
        require_once __DIR__ . '/../../views/Member-auth/login.php';
    }

    public function processLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $result = $this->user_model->handle_member_login(
                $_POST['email'] ?? '',
                $_POST['password'] ?? ''
            );

            if ($result['success']) {

                $_SESSION['user_id'] = $result['data']['user_id'];
                $_SESSION['role'] = $result['data']['role'];
                $_SESSION['organization_id'] = $result['data']['organization_id'];

                header("Location: " . $this->getBaseUrl() . "/dashboard");
                exit();
            } else {
                $error = $result['message'];
                require_once __DIR__ . '/../../views/Member-auth/login.php';
            }
        }
    }
}