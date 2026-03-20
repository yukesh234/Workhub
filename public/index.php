<?php

session_start();

require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../src/Models/Database.php';
require_once __DIR__ . '/../src/Models/AdminModel.php';
require_once __DIR__ . '/../src/Models/OrganizationModel.php';
require_once __DIR__ . '/../src/Models/ProjectModel.php';
require_once __DIR__ . '/../src/Models/ProjectMemberModel.php';
require_once __DIR__ . '/../src/Models/UserModel.php';
require_once __DIR__ . '/../src/Models/TaskModel.php';
require_once __DIR__ . '/../src/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/Middleware/UserMIddleware.php';
require_once __DIR__ . '/../src/Controller/AdminController.php';
require_once __DIR__ . '/../src/Controller/ProjectController.php';
require_once __DIR__ . '/../src/Controller/ProjectMemberController.php';
require_once __DIR__ . '/../src/Controller/MemberController.php';
require_once __DIR__ . '/../src/Controller/TaskController.php';
require_once __DIR__ . '/../src/Controller/UserController.php';
require_once __DIR__ . '/../src/Controller/CommentController.php';
require_once __DIR__ . '/../src/Controller/AttachmentController.php';
require_once __DIR__ . '/../src/Controller/MeetingController.php';
require_once __DIR__ . '/../src/Controller/AnalyticsController.php';
require_once __DIR__ . '/../src/Models/ActivityLogModel.php';
require_once __DIR__ . '/../src/Models/AnalyticsModel.php';
require_once __DIR__ . '/../src/Utils/ActivityLogger.php';
require_once __DIR__ . '/../src/Models/CommentModel.php';
require_once __DIR__ . '/../src/Models/AttachmentModel.php';
require_once __DIR__ . '/../src/Models/MeetingModel.php';
require_once __DIR__ . '/../src/Models/SettingModel.php';
require_once __DIR__ . '/../src/Controller/SettingController.php';

function getBaseUrl(): string {
    return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
}

// ── Normalise URI ─────────────────────────────────────────────────────
$requestUri = strtok($_SERVER['REQUEST_URI'], '?');
$basePath   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

if (!empty($basePath) && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}
if (empty($requestUri) || $requestUri[0] !== '/') {
    $requestUri = '/' . $requestUri;
}

// ── Controllers ───────────────────────────────────────────────────────
$adminController         = new AdminController();
$projectController       = new ProjectController();
$projectMemberController = new ProjectMemberController();
$memberController        = new MemberController();
$taskController          = new TaskController();
$userController          = new UserController();
$commentController       = new CommentController();
$attachmentController    = new AttachmentController();
$meetingController       = new MeetingController();
$analyticsController     = new AnalyticsController();
$settingController       = new SettingController();

// ── Router ────────────────────────────────────────────────────────────
switch ($requestUri) {

    // ── Root ──────────────────────────────────────────────────────────
    case '/':
        if (AuthMiddleware::isLoggedIn()) {
            header('Location: ' . getBaseUrl() . '/dashboard');
        } elseif (UserAuthMiddleware::isLoggedIn()) {
            header('Location: ' . getBaseUrl() . '/user/dashboard');
        } else {
            header('Location: ' . getBaseUrl() . '/Home');
        }
        exit();

    case '/Home':
        require_once __DIR__ . '/../views/Home.php';
        break;

    // ── Admin auth ────────────────────────────────────────────────────
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

    // ── Admin views ───────────────────────────────────────────────────
    case '/dashboard':
        AuthMiddleware::checkAuth();
        require_once __DIR__ . '/../views/Dashboard.php';
        break;

    case '/projects':
        AuthMiddleware::checkAuth();
        require_once __DIR__ . '/../views/project.php';
        break;

    case '/project-detail':
        AuthMiddleware::checkAuth();
        require_once __DIR__ . '/../views/projectdetail.php';
        break;

    case '/members':
        AuthMiddleware::checkAuth();
        require_once __DIR__ . '/../views/Members.php';
        break;

    case '/settings':
        AuthMiddleware::checkAuth();
        require_once __DIR__ . '/../views/Setting.php';
        break;

    // ── Organization API ──────────────────────────────────────────────
    case '/api/organization':
        $adminController->getOrganization();
        break;

    case '/organization/create':
        $adminController->createOrganization();
        break;

    // ── Settings API ──────────────────────────────────────────────────
    case '/api/organization/update':
        AuthMiddleware::checkAuth();
        $settingController->updateOrgInfo();
        break;

    case '/api/organization/logo':
        AuthMiddleware::checkAuth();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':   $settingController->uploadOrgLogo(); break;
            case 'DELETE': $settingController->removeOrgLogo(); break;
            default: http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    case '/api/organization/delete':
        AuthMiddleware::checkAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $settingController->deleteOrganization();
        } else {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    case '/api/members/all':
        AuthMiddleware::checkAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $settingController->deleteAllMembers();
        } else {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    case '/api/admin/change-password':
        AuthMiddleware::checkAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $settingController->changeAdminPassword();
        } else {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    // ── Admin Members API ─────────────────────────────────────────────
    case '/api/members':
        AuthMiddleware::checkAuth();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':    $adminController->getOrganizationMember(); break;
            case 'POST':   $adminController->createUser();            break;
            case 'DELETE': $adminController->removeMember();          break;
            default: http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    // ── Admin Task API ────────────────────────────────────────────────
    case '/api/tasks':
        AuthMiddleware::checkAuth();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':    $taskController->getAllTasks(); break;
            case 'POST':   $taskController->createTask(); break;
            case 'PUT':    $taskController->updateTask(); break;
            case 'DELETE': $taskController->deleteTask(); break;
            default: http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    case '/api/tasks/status':
        AuthMiddleware::checkAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
            $taskController->updateStatus();
        } else {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    // ── Meetings API ──────────────────────────────────────────────────
    case '/analytics':
        AuthMiddleware::checkAuth();
        require_once __DIR__ . '/../views/Analytics.php';
        break;

    case '/member-analytics':
        AuthMiddleware::checkAuth();
        require_once __DIR__ . '/../views/MemberAnalytics.php';
        break;

    case '/api/analytics/admin':
        AuthMiddleware::checkAuth();
        $analyticsController->adminOverview();
        break;

    case '/api/analytics/activity':
        AuthMiddleware::checkAuth();
        $analyticsController->activityLog();
        break;

    case '/api/analytics/member':
        AuthMiddleware::checkAuth();
        $analyticsController->memberAnalytics();
        break;

    case '/api/analytics/project':
        UserAuthMiddleware::checkAuth();
        UserAuthMiddleware::requirePasswordChanged();
        $analyticsController->projectAnalytics();
        break;

    case '/api/meetings/start':
        $meetingController->start();
        break;
    case '/api/meetings/active':
        $meetingController->getActive();
        break;
    case '/api/meetings/token':
        $meetingController->getToken();
        break;
    case '/api/meetings/end':
        $meetingController->end();
        break;
    case '/api/meetings/history':
        $meetingController->history();
        break;

    // ── Comments API ──────────────────────────────────────────────────
    case '/api/tasks/comments':
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':    $commentController->getComments();    break;
            case 'POST':   $commentController->addComment();     break;
            case 'DELETE': $commentController->deleteComment();  break;
            default:
                http_response_code(405);
                echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    // ── Attachments API ───────────────────────────────────────────────
    case '/api/tasks/attachments':
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':    $attachmentController->getAttachments();    break;
            case 'POST':   $attachmentController->uploadAttachment();  break;
            case 'DELETE': $attachmentController->deleteAttachment();  break;
            default:
                http_response_code(405);
                echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    // ── Admin Projects API ────────────────────────────────────────────
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

    // ── Admin Project Members API ─────────────────────────────────────
    case '/api/projects/members':
        AuthMiddleware::checkAuth();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':    $projectMemberController->getMembers();   break;
            case 'POST':   $projectMemberController->addMember();    break;
            case 'DELETE': $projectMemberController->removeMember(); break;
            default: http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    case '/api/projects/members/role':
        AuthMiddleware::checkAuth();
        $projectMemberController->changeRole();
        break;

    // ══════════════════════════════════════════════════════════════════
    // ── USER (member / manager) routes ───────────────────────────────
    // ══════════════════════════════════════════════════════════════════

    case '/user/login':
        $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $memberController->processLogin()
            : $memberController->showLoginForm();
        break;

    case '/user/logout':
        $memberController->logout();
        break;

    // Change password — checkAuth only, NO requirePasswordChanged guard
    case '/user/change-password':
        UserAuthMiddleware::checkAuth();
        $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $memberController->changePassword()
            : $memberController->showChangePasswordForm();
        break;

    case '/user/dashboard':
        UserAuthMiddleware::checkAuth();
        UserAuthMiddleware::requirePasswordChanged();
        require_once __DIR__ . '/../views/User/userDashboard.php';
        break;

    case '/user/project':
        UserAuthMiddleware::checkAuth();
        UserAuthMiddleware::requirePasswordChanged();
        require_once __DIR__ . '/../views/User/project-description.php';
        break;

    // ── User-side API: Projects ───────────────────────────────────────
    case '/api/user/projects':
        UserAuthMiddleware::checkAuth();
        UserAuthMiddleware::requirePasswordChanged();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $userController->getMyProjects();
        } else {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    // ── User-side API: Tasks ──────────────────────────────────────────
    case '/api/user/tasks':
        UserAuthMiddleware::checkAuth();
        UserAuthMiddleware::requirePasswordChanged();
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':    $userController->getMyTasks();    break;
            case 'POST':   $userController->createTask();    break;
            case 'PUT':    $userController->updateTask();    break;
            case 'DELETE': $userController->deleteTask();    break;
            default: http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    case '/api/user/tasks/status':
        UserAuthMiddleware::checkAuth();
        UserAuthMiddleware::requirePasswordChanged();
        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
            $userController->updateTaskStatus();
        } else {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    // ── User-side API: Project members (assignee dropdown) ────────────
    case '/api/user/project/single':
        UserAuthMiddleware::checkAuth();
        UserAuthMiddleware::requirePasswordChanged();
        $userController->getProjectDetail();
        break;

    case '/api/user/project/tasks':
        UserAuthMiddleware::checkAuth();
        UserAuthMiddleware::requirePasswordChanged();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $userController->getProjectTasks();
        } else {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    case '/api/user/project/members':
        UserAuthMiddleware::checkAuth();
        UserAuthMiddleware::requirePasswordChanged();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $userController->getProjectMembers();
        } else {
            http_response_code(405);
            echo json_encode(['success'=>false,'message'=>'Method not allowed']);
        }
        break;

    // ── 404 ───────────────────────────────────────────────────────────
    default:
        http_response_code(404);
        echo "404 — Page Not Found";
        break;
}