<?php

require_once __DIR__ . '/../Models/SettingModel.php';
require_once __DIR__ . '/../Models/OrganizationModel.php';
require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Service/CloudinaryService.php';
require_once __DIR__ . '/../Utils/ActivityLogger.php';
require_once __DIR__ . '/../utils/response.php';

class SettingController {
    private SettingModel      $setting;
    private OrganizationModel $organization;
    private CloudinaryService $cloudinary;

    public function __construct() {
        $this->setting      = new SettingModel();
        $this->organization = new OrganizationModel();
        $this->cloudinary   = new CloudinaryService();
    }

    // ── Helper: get org_id for the logged-in admin ─────────────────
    private function getOrgId(): int {
        $admin_id = AuthMiddleware::adminId();
        $org_id   = (int) AuthMiddleware::organization($this->organization, $admin_id);
        if (!$org_id) Response(404, false, 'No organisation found');
        return $org_id;
    }

    // ── POST /api/organization/update ─────────────────────────────────
    // Body: multipart — name*, slogan
    public function updateOrgInfo(): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkAuth();

        $name   = trim($_POST['name']   ?? '');
        $slogan = trim($_POST['slogan'] ?? '');

        if (empty($name)) Response(400, false, 'Organization name is required');

        $org_id = $this->getOrgId();
        $result = $this->setting->updateOrgInfo($org_id, $name, $slogan);
        if (!$result['success']) Response(500, false, $result['message']);

        ActivityLogger::log('updated_organization', 'organization', $org_id, $org_id, $name);
        Response(200, true, 'Organization updated', ['name' => $name, 'slogan' => $slogan]);
    }

    // ── POST /api/organization/logo ───────────────────────────────────
    // Body: multipart — logo (file)
    public function uploadOrgLogo(): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkAuth();

        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            Response(400, false, 'No logo file uploaded');
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($_FILES['logo']['type'], $allowed)) {
            Response(400, false, 'Invalid file type. Only JPG, PNG, WEBP allowed');
        }
        if ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
            Response(400, false, 'File too large. Max 5MB');
        }

        $org_id   = $this->getOrgId();
        $existing = $this->setting->getOrgById($org_id);

        try {
            // Delete old logo from Cloudinary if one exists
            if (!empty($existing['logo_public_id'])) {
                try { $this->cloudinary->deleteImage($existing['logo_public_id']); } catch (\Exception $e) {}
            }

            $uploaded = $this->cloudinary->uploadImage($_FILES['logo']['tmp_name'], 'workhub/organizations');
            $result   = $this->setting->updateOrgLogo($org_id, $uploaded['url'], $uploaded['public_id']);
            if (!$result['success']) Response(500, false, $result['message']);

            Response(200, true, 'Logo updated', ['organization_logo' => $uploaded['url']]);
        } catch (\Exception $e) {
            Response(500, false, $e->getMessage());
        }
    }

    // ── DELETE /api/organization/logo ─────────────────────────────────
    public function removeOrgLogo(): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkAuth();

        $org_id   = $this->getOrgId();
        $existing = $this->setting->getOrgById($org_id);

        if (empty($existing['organization_logo'])) {
            Response(400, false, 'No logo to remove');
        }

        try {
            if (!empty($existing['logo_public_id'])) {
                try { $this->cloudinary->deleteImage($existing['logo_public_id']); } catch (\Exception $e) {}
            }

            $result = $this->setting->updateOrgLogo($org_id, null, null);
            if (!$result['success']) Response(500, false, $result['message']);

            Response(200, true, 'Logo removed');
        } catch (\Exception $e) {
            Response(500, false, $e->getMessage());
        }
    }

    // ── DELETE /api/organization ──────────────────────────────────────
    public function deleteOrganization(): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkAuth();

        $org_id   = $this->getOrgId();
        $existing = $this->setting->getOrgById($org_id);

        try {
            if (!empty($existing['logo_public_id'])) {
                try { $this->cloudinary->deleteImage($existing['logo_public_id']); } catch (\Exception $e) {}
            }

            $result = $this->setting->deleteOrganization($org_id);
            if (!$result['success']) Response(500, false, $result['message']);

            Response(200, true, 'Organization deleted');
        } catch (\Exception $e) {
            Response(500, false, $e->getMessage());
        }
    }

    // ── DELETE /api/members/all ───────────────────────────────────────
    public function deleteAllMembers(): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkAuth();

        $org_id = $this->getOrgId();
        $result = $this->setting->deleteAllMembers($org_id);
        if (!$result['success']) Response(500, false, $result['message']);

        Response(200, true, 'All members removed', ['count' => $result['count'] ?? 0]);
    }

    // ── POST /api/admin/change-password ──────────────────────────────
    // Body: JSON — current_password, new_password, confirm_password
    public function changeAdminPassword(): void {
        header('Content-Type: application/json');
        AuthMiddleware::checkAuth();

        $data    = json_decode(file_get_contents('php://input'), true) ?? [];
        $current = trim($data['current_password']  ?? '');
        $new     = trim($data['new_password']       ?? '');
        $confirm = trim($data['confirm_password']   ?? '');

        if (!$current || !$new || !$confirm) Response(400, false, 'All fields are required');
        if (strlen($new) < 8)               Response(400, false, 'Password must be at least 8 characters');
        if ($new !== $confirm)              Response(400, false, 'Passwords do not match');

        $admin_id = AuthMiddleware::adminId();
        $result   = $this->setting->changeAdminPassword($admin_id, $current, $new);
        if (!$result['success']) Response(400, false, $result['message']);

        Response(200, true, 'Password updated successfully');
    }
}