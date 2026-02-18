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
require_once __DIR__ . '/../Service/CloudinaryService.php';
class AdminController {
    private CloudinaryService $cloudinary;
    private OrganizationModel $organization;
    public function __construct()
    {
       $this->cloudinary = new CloudinaryService();
       $this->organization = new OrganizationModel();
    }
    private function getBaseUrl() {
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return $basePath;
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
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $admin = new Admin();
            $result = $admin->createAdmin($email, $password);

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
            $otp = trim($_POST['otp'] ?? '');

            if (empty($email) || empty($otp)) {
                $error = 'Email and OTP are required';
                require_once __DIR__ . '/../../views/auth/verify.php';
                return;
            }

            $admin = new Admin();
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

            $admin = new Admin();
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
                
                if ($emailSent) {
                    echo json_encode(['success' => true, 'message' => 'New OTP sent to your email']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send email']);
                }
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
            $admin = new Admin();
            $result = $admin->handleLogin($_POST['email'] ?? '', $_POST['password'] ?? '');
            
            
            if ($result['success']) {
                $_SESSION['admin_id'] = $result['admin']['id'];
                $_SESSION['admin_email'] = $result['admin']['email'];
                $_SESSION['is_verified'] = $result['admin']['isverified'];
                $cookie_name = "AdminEmail";;
                $cookie_value = $result['admin']['email'];
                setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/"); 
                //seeting the id and is verified as well
                $_SESSION['admin_Id'] = $result['admin']['id'];
                $_SESSION['is_verified'] = $result['admin']['isverified'];
                setcookie("AdminId", $result['admin']['id'], time() + (86400 * 30), "/");
                setcookie("is_verified", $result['admin']['isverified'], time() + (86400 * 30), "/");
                header("Location: " . $this->getBaseUrl() . "/dashboard");
                exit();
            } else {
                $error = $result['message'];
                
                // If unverified, redirect to verify page
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
        
        // 1. Validate required fields
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            Response(400, false, "Organization name is required");
        }

        // 2. Handle image upload (OPTIONAL)
        $imageUrl = null;
        $publicId = null;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = $_FILES['image']['type'];

            if (!in_array($fileType, $allowedTypes)) {
                Response(400, false, "Invalid image type. Only JPG, PNG, and WEBP allowed");
            }

            // Upload to Cloudinary
            $uploaded = $this->cloudinary->uploadImage($_FILES['image']['tmp_name'], 'workhub/organizations');
            $imageUrl = $uploaded['url'];
            $publicId = $uploaded['public_id'];
        }

        // 3. Save to database (OUTSIDE the image upload block!)
        $slogan = trim($_POST['slogan'] ?? '');
        $result = $this->organization->createOrganization(
            $admin_id, 
            $name, 
            $slogan, 
            $imageUrl,
            $publicId  // ← Add this
        );

        if (!$result['success']) {
            // If DB fails and we uploaded an image, clean it up
            if ($publicId) {
                $this->cloudinary->deleteImage($publicId);
            }
            Response(500, false, $result['message']);
        }

        Response(201, true, $result['message'], [
            'organization_id' => $result['organization_id']
        ]);

    } catch (\Exception $e) {
        Response(500, false, $e->getMessage());
    }
}
}