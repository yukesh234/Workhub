<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../Models/AdminModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Utils/Email.php';
require_once __DIR__ . '/../Utils/helpers.php';
require_once __DIR__ . '/../Models/OrganizationModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Service/CloudinaryService.php';

class AdminController {
    private CloudinaryService $cloudinary;
    private OrganizationModel $organization;
    private UserModel $user;

    public function __construct()
    {
       $this->cloudinary = new CloudinaryService();
       $this->organization = new OrganizationModel();
       $this->user = new UserModel();
    }

    private function getBaseUrl() {
        return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    }

    // ── Generate a readable one-time password ────────────────────────
    private function generatePassword(int $length = 10): string {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#';
        $pass  = '';
        for ($i = 0; $i < $length; $i++) {
            $pass .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $pass;
    }

    public function showRegisterForm() {
        if (AuthMiddleware::isLoggedIn()) {
            header("Location: " . $this->getBaseUrl() . "/dashboard");
            exit();
        }
        require_once __DIR__ . '/../../views/auth/register.php';
    }

    public function processRegister() {
        if (AuthMiddleware::isLoggedIn()) {
            header("Location: " . $this->getBaseUrl() . "/dashboard");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $admin    = new Admin();
            $result   = $admin->createAdmin($email, $password);

            if ($result['success']) {
                $otp = generateOTP(6);
                if ($admin->storeOTP($email, $otp)) {
                    $emailSent = Email::sendVerificationEmail($email, $otp);
                    if ($emailSent) {
                        header("Location: " . $this->getBaseUrl() . "/verify?email=" . urlencode($email));
                        exit();
                    } else {
                        $error = 'Failed to send verification email. Please try again.';
                        require_once __DIR__ . '/../../views/auth/register.php';
                    }
                } else {
                    $error = 'Failed to generate verification code. Please try again.';
                    require_once __DIR__ . '/../../views/auth/register.php';
                }
            } else {
                $error = $result['message'];
                require_once __DIR__ . '/../../views/auth/register.php';
            }
        }
    }

    public function showVerifyForm() {
        if (AuthMiddleware::isLoggedIn()) {
            header("Location: " . $this->getBaseUrl() . "/dashboard");
            exit();
        }
        $email = $_GET['email'] ?? '';
        require_once __DIR__ . '/../../views/auth/verify.php';
    }

    public function processVerify() {
        if (AuthMiddleware::isLoggedIn()) {
            header("Location: " . $this->getBaseUrl() . "/dashboard");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $otp   = trim($_POST['otp']   ?? '');

            if (empty($email) || empty($otp)) {
                $error = 'Email and OTP are required';
                require_once __DIR__ . '/../../views/auth/verify.php';
                return;
            }

            $admin  = new Admin();
            $result = $admin->verifyOTP($email, $otp);

            if ($result['success']) {
                $_SESSION['success'] = 'Email verified successfully. You can now login.';
                header("Location: " . $this->getBaseUrl() . "/login");
                exit();
            } else {
                $error = $result['message'];
                require_once __DIR__ . '/../../views/auth/verify.php';
            }
        }
    }

    public function resendOTP() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Email is required']);
                return;
            }

            $admin         = new Admin();
            $existingAdmin = $admin->getAdminByEmail($email);

            if (!$existingAdmin) {
                echo json_encode(['success' => false, 'message' => 'Email not found']);
                return;
            }

            if ($existingAdmin['isverified']) {
                echo json_encode(['success' => false, 'message' => 'Email already verified']);
                return;
            }

            $otp = generateOTP(6);
            if ($admin->storeOTP($email, $otp)) {
                $emailSent = Email::sendVerificationEmail($email, $otp);
                echo json_encode([
                    'success' => $emailSent,
                    'message' => $emailSent ? 'New OTP sent to your email' : 'Failed to send email'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to generate OTP']);
            }
        }
    }

    public function Logout() {
        session_unset();
        session_destroy();
        header("Location: " . $this->getBaseUrl() . "/Home");
        exit();
    }

    public function Login() {
        if (AuthMiddleware::isLoggedIn()) {
            header("Location: " . $this->getBaseUrl() . "/dashboard");
            exit();
        }
        require_once __DIR__ . '/../../views/auth/login.php';
    }

    public function processLogin() {
        if (AuthMiddleware::isLoggedIn()) {
            header("Location: " . $this->getBaseUrl() . "/dashboard");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $admin  = new Admin();
            $result = $admin->handleLogin($_POST['email'] ?? '', $_POST['password'] ?? '');

            if ($result['success']) {
                $_SESSION['admin_id']    = $result['admin']['id'];
                $_SESSION['admin_email'] = $result['admin']['email'];
                $_SESSION['is_verified'] = $result['admin']['isverified'];
                $_SESSION['admin_Id']    = $result['admin']['id'];

                setcookie("AdminEmail",    $result['admin']['email'],       time() + 86400 * 30, "/");
                setcookie("AdminId",       $result['admin']['id'],          time() + 86400 * 30, "/");
                setcookie("is_verified",   $result['admin']['isverified'],  time() + 86400 * 30, "/");

                header("Location: " . $this->getBaseUrl() . "/dashboard");
                exit();
            } else {
                $error = $result['message'];
                if (isset($result['redirect'])) {
                    header("Location: " . $this->getBaseUrl() . $result['redirect']);
                    exit();
                }
                require_once __DIR__ . '/../../views/auth/login.php';
            }
        }
    }

    public function createOrganization() {
        try {
            $admin_id = AuthMiddleware::adminId();

            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                Response(400, false, "Organization name is required");
            }

            $imageUrl = null;
            $publicId = null;

            if (isset($_FILES['organization_logo']) && $_FILES['organization_logo']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($_FILES['organization_logo']['type'], $allowedTypes)) {
                    Response(400, false, "Invalid image type. Only JPG, PNG, and WEBP allowed");
                }
                $uploaded = $this->cloudinary->uploadImage($_FILES['organization_logo']['tmp_name'], 'workhub/organizations');
                $imageUrl = $uploaded['url'];
                $publicId = $uploaded['public_id'];
            }

            $slogan = trim($_POST['slogan'] ?? '');
            $result = $this->organization->createOrganization($admin_id, $name, $slogan, $imageUrl, $publicId);

            if (!$result['success']) {
                if ($publicId) $this->cloudinary->deleteImage($publicId);
                Response(500, false, $result['message']);
            }

            Response(201, true, $result['message'], [
                'organization_id'   => $result['organization_id'],
                'name'              => $name,
                'slogan'            => $slogan,
                'organization_logo' => $imageUrl,
                'created_at'        => date('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            Response(500, false, $e->getMessage());
        }
    }

    // ── Create org member (admin-only) ───────────────────────────────
    // POST multipart: name, email, role, image (optional)
    // Password is auto-generated and returned ONCE — admin shares it.
    public function createUser() {
        try {
            if (!AuthMiddleware::isLoggedIn()) {
                Response(401, false, "Unauthorized");
            }

            $admin_id       = AuthMiddleware::adminId();
            $organization_id = AuthMiddleware::organization($this->organization, $admin_id);

            $name  = trim($_POST['name']  ?? '');
            $email = trim($_POST['email'] ?? '');
            $role  = trim($_POST['role']  ?? '');

            if (empty($name) || empty($email) || empty($role)) {
                Response(400, false, "Name, email, and role are required");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Response(400, false, "Invalid email format");
            }

            if (!in_array($role, ['manager', 'member'])) {
                Response(400, false, "Invalid role. Must be 'manager' or 'member'");
            }

            // Auto-generate a one-time password
            $plainPassword = $this->generatePassword(10);

            // Optional profile image
            $imageUrl = null;
            $publicId = null;

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                    Response(400, false, "Invalid image type. Only JPG, PNG, and WEBP allowed");
                }
                $uploaded = $this->cloudinary->uploadImage($_FILES['image']['tmp_name'], 'workhub/users');
                $imageUrl = $uploaded['url'];
                $publicId = $uploaded['public_id'];
            }

            $result = $this->user->createUser(
                $name,
                $email,
                $plainPassword,
                $organization_id,
                $role,
                $imageUrl,
                $publicId
            );

            if (!$result['success']) {
                if ($publicId) $this->cloudinary->deleteImage($publicId);
                Response(400, false, $result['message']);
            }

            // Return generated password — only time it's ever sent in plaintext
            Response(201, true, "Member created successfully", [
                'user_id'            => $result['user_id'],
                'name'               => $name,
                'email'              => $email,
                'role'               => $role,
                'generated_password' => $plainPassword,
                'userProfile'        => $imageUrl,
            ]);

        } catch (\Exception $e) {
            Response(500, false, $e->getMessage());
        }
    }

    public function removeMember(){
        try{
        if (!AuthMiddleware::isLoggedIn()) {
            Response(401, false, "Unauthorized");
        }

        header('Content-Type: application/json');

        $data = json_decode(file_get_contents('php://input'), true);

        if(!isset($data['user_id'])){
            Response(400,false,"user_id is required");
        }
        $user = $this->user->getUserById($data['user_id']);
        $result = $this->user->deleteUser($data['user_id']);
        if(!$result['success']){
            Response(400, false, $result['message']);
        }
        //deleting the image from the cloudinary
      // Only delete from Cloudinary if the user had a profile image
        $publicId = $user['profile_public_id'] ?? null;
        if ($publicId) {
            $this->cloudinary->deleteImage($publicId);
        }

        Response(200, true, 'Successfully deleted the user');
        if($img['result'] !== 'ok' )
            {
                Response(500,false,"error deleting the user");
            }
        Response(200,true,'successfully deleted the user');
        }catch(\Exception $e){
            Response(500,false,$e->getMessage());
        }
    }

    public function getOrganization() {
        try {
            if (!AuthMiddleware::isLoggedIn()) {
                Response(401, false, "Unauthorized");
            }
            $admin_id = AuthMiddleware::adminId();
            $result   = $this->organization->getOrganizationdetails($admin_id);
            Response(200, true, "fetched successfully", $result ?: null);
        } catch (\Exception $e) {
            Response(500, false, $e->getMessage());
        }
    }

    public function getOrganizationMember() {
        try {
            if (!AuthMiddleware::isLoggedIn()) {
                Response(401, false, "Unauthorized");
            }
            $admin_id        = AuthMiddleware::adminId();
            $organization_id = AuthMiddleware::organization($this->organization, $admin_id);
            $result          = $this->user->getOrganizationMember($organization_id);
            Response(200, true, "Fetched members successfully", $result);
        } catch (\Exception $e) {
            Response(500, false, "Failed fetching members: " . $e->getMessage());
        }
    }
}