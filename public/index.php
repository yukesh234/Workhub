<?php

session_start();

require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../src/Models/Database.php';
require_once __DIR__ . '/../src/Models/AdminModel.php';
require_once __DIR__ . '/../src/Models/OrganizationModel.php';
require_once __DIR__ . '/../src/Models/ProjectModel.php';
require_once __DIR__ . '/../src/Models/ProjectMemberModel.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/Controller/AdminController.php';
require_once __DIR__ . '/../src/Controller/ProjectController.php';
require_once __DIR__ . '/../src/Controller/ProjectMemberController.php';

function getBaseUrl() {
    return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
}

// ── Normalise request URI ────────────────────────────────────────────
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$basePath   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

if (!empty($basePath) && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

if (empty($requestUri) || $requestUri[0] !== '/') {
    $requestUri = '/' . $requestUri;
}

// ── Controllers ──────────────────────────────────────────────────────
$adminController         = new AdminController();
$projectController       = new ProjectController();
$projectMemberController = new ProjectMemberController();

// ── Router ───────────────────────────────────────────────────────────
switch ($requestUri) {

    // ── Root ──
    case '/':
        if (AuthMiddleware::isLoggedIn()) {
            header('Location: ' . getBaseUrl() . '/dashboard');
        } else {
            header('Location: ' . getBaseUrl() . '/Home');
        }
        exit();

    // ── Public pages ──
    case '/Home':
        require_once __DIR__ . '/../views/Home.php';
        break;

    // ── Auth ──
    case '/register':
        $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $adminController->processRegister()
            : $adminController->showRegisterForm();
        break;

    case '/login':
        $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $adminController->processLogin()
            : $adminController->Login();
        break;

    case '/verify':
        $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $adminController->processVerify()
            : $adminController->showVerifyForm();
        break;

    case '/resend-otp':
        $adminController->resendOTP();
        break;

    case '/logout':
        $adminController->Logout();
        break;

    // ── Admin views ──
    case '/dashboard':
        AuthMiddleware::checkAuth();
        require_once __DIR__ . '/../views/Dashboard.php';
        break;

    case '/projects':
        AuthMiddleware::checkAuth();
        require_once __DIR__ . '/../views/project.php';
        break;

    // ── Organization API ──
    case '/api/organization':
        $adminController->getOrganization();
        break;

    case '/organization/create':
        $adminController->createOrganization();
        break;

    // ── Project API ──
    case '/api/projects':
        AuthMiddleware::checkAuth();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':    $projectController->getAllProject();  break;
            case 'POST':   $projectController->createProject(); break;
            case 'PUT':    $projectController->editProject();   break;
            case 'DELETE': $projectController->deleteProject(); break;
            default: http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    case '/api/projects/single':
        AuthMiddleware::checkAuth();
        $projectController->getProjectByID();
        break;

    // ── Project Member API ──
    case '/api/projects/members':
        AuthMiddleware::checkAuth();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':    $projectMemberController->getMembers();    break;
            case 'POST':   $projectMemberController->addMember();     break;
            case 'DELETE': $projectMemberController->removeMember(); break;
            default: http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    case '/api/projects/members/role':
        AuthMiddleware::checkAuth();
        $projectMemberController->changeRole();
        break;

    // ── 404 ──
    default:
        http_response_code(404);
        echo "404 — Page Not Found";
        break;
}