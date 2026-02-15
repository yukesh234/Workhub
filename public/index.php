<?php

session_start();

require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../src/Models/Database.php';
require_once __DIR__ . '/../src/Models/AdminModel.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/Controller/AdminController.php';

function getBaseUrl() {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    return $basePath;
}

$requestUri = $_SERVER['REQUEST_URI'];
$requestUri = strtok($requestUri, '?');

$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if (!empty($basePath) && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

if (empty($requestUri) || $requestUri[0] !== '/') {
    $requestUri = '/' . $requestUri;
}

$adminController = new AdminController();

switch ($requestUri) {
    case '/':
        if (AuthMiddleware::isLoggedIn()) {
            header('Location: ' . getBaseUrl() . '/dashboard');
            exit();
        } else {
            header('Location:'. getBaseUrl() . '/Home');
            exit();
        }
        break;
        
    case '/Home':
        require_once __DIR__ . '/../views/Home.php';
        break;
        
    case '/register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->processRegister();
        } else {
            $adminController->showRegisterForm();
        }
        break;
        
    case '/login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->processLogin();
        } else {
            $adminController->Login();
        }
        break;
        
    case '/verify':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->processVerify();
        } else {
            $adminController->showVerifyForm();
        }
        break;
        
    case '/resend-otp':
        $adminController->resendOTP();
        break;
        
    case '/dashboard':
        AuthMiddleware::checkAuth();
        require_once __DIR__ . '/../views/Dashboard.php';
        break;
        
    case '/logout':
        $adminController->Logout();
        break;
        
    default:
        http_response_code(404);
        echo "404 - Page Not Found";
        break;
}