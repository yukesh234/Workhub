<?php

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';
require_once __DIR__ . '/../Middleware/UserMIddleware.php';
require_once __DIR__ . '/../Models/MeetingModel.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../../config/jitsi.php';
require_once __DIR__ . '/../utils/ActivityLogger.php'; 

class MeetingController {
    private MeetingModel $meeting;

    public function __construct() {
        $this->meeting = new MeetingModel();
    }

    public function start(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $project_id = (int) ($data['project_id'] ?? 0);
        if (!$project_id) Response(400, false, 'project_id is required');

        $room_name = 'workhub-project-' . $project_id . '-' . substr(md5(JAAS_APP_ID . $project_id), 0, 8);
        [$author_id, $author_type] = $this->getAuthor();

        $result = $this->meeting->startMeeting($project_id, $room_name, $author_id, $author_type);
        if (!$result['success']) Response(500, false, $result['message']);

        $token  = $this->generateJaaSToken($author_id, $author_type, $room_name, true);
        $org_id = $this->getOrgIdFromProject($project_id);

        // ── Log ──────────────────────────────────────────────────────
        ActivityLogger::log('meeting_started', 'meeting', $org_id, (int) $result['meeting_id'], "project #{$project_id}");

        Response(201, true, 'Meeting started', [
            'meeting_id' => $result['meeting_id'],
            'room_name'  => $room_name,
            'token'      => $token,
            'jaas_url'   => 'https://8x8.vc/' . JAAS_APP_ID . '/' . $room_name,
        ]);
    }

    public function getActive(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $project_id = (int) ($_GET['project_id'] ?? 0);
        if (!$project_id) Response(400, false, 'project_id is required');

        $meeting = $this->meeting->getActiveMeeting($project_id);
        if ($meeting) {
            $meeting['jaas_url'] = 'https://8x8.vc/' . JAAS_APP_ID . '/' . $meeting['room_name'];
        }

        Response(200, true, $meeting ? 'Active meeting found' : 'No active meeting', $meeting);
    }

    public function getToken(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $project_id = (int) ($_GET['project_id'] ?? 0);
        if (!$project_id) Response(400, false, 'project_id is required');

        $meeting = $this->meeting->getActiveMeeting($project_id);
        if (!$meeting) Response(404, false, 'No active meeting for this project');

        [$author_id, $author_type] = $this->getAuthor();
        $isModerator = ($author_type === 'admin') || UserAuthMiddleware::isManager();
        $token       = $this->generateJaaSToken($author_id, $author_type, $meeting['room_name'], $isModerator);

        Response(200, true, 'Token generated', [
            'token'     => $token,
            'jaas_url'  => 'https://8x8.vc/' . JAAS_APP_ID . '/' . $meeting['room_name'],
            'room_name' => $meeting['room_name'],
        ]);
    }

    public function end(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $data       = json_decode(file_get_contents('php://input'), true) ?? [];
        $meeting_id = (int) ($data['meeting_id'] ?? 0);
        if (!$meeting_id) Response(400, false, 'meeting_id is required');

        [$author_id,] = $this->getAuthor();
        $result = $this->meeting->endMeeting($meeting_id, $author_id);
        if (!$result['success']) Response(400, false, $result['message']);

        // ── Log ──────────────────────────────────────────────────────
        // Get project_id for org lookup from the meeting record
        $org_id = $this->getOrgIdFromMeeting($meeting_id);
        ActivityLogger::log('meeting_ended', 'meeting', $org_id, $meeting_id, "meeting #{$meeting_id}");

        Response(200, true, 'Meeting ended');
    }

    public function history(): void {
        header('Content-Type: application/json');
        $this->requireAnyAuth();

        $project_id = (int) ($_GET['project_id'] ?? 0);
        if (!$project_id) Response(400, false, 'project_id is required');

        Response(200, true, 'History fetched', $this->meeting->getMeetingHistory($project_id));
    }

    private function generateJaaSToken(int $userId, string $userType, string $roomName, bool $isModerator): string {
        if ($userType === 'admin') {
            $name  = $_SESSION['admin_email'] ?? 'Admin';
            $email = $_SESSION['admin_email'] ?? 'admin@workhub.com';
            $uid   = 'admin-' . $userId;
        } else {
            $name  = $_SESSION['user_name']  ?? 'Team Member';
            $email = $_SESSION['user_email'] ?? strtolower(str_replace(' ', '.', $name)) . '@workhub.com';
            $uid   = 'user-' . $userId;
        }

        $now     = time();
        $header  = $this->b64url(json_encode(['alg'=>'RS256','kid'=>JAAS_APP_ID.'/'.JAAS_API_KEY_ID,'typ'=>'JWT']));
        $payload = $this->b64url(json_encode([
            'iss'=>'chat','aud'=>'jitsi','iat'=>$now,'exp'=>$now+7200,'nbf'=>$now-10,'sub'=>JAAS_APP_ID,
            'context'=>['user'=>['id'=>$uid,'name'=>$name,'email'=>$email,'moderator'=>$isModerator],
                        'features'=>['lobby'=>false,'recording'=>$isModerator,'livestreaming'=>false,'outbound-call'=>false]],
            'room'=>$roomName,
        ]));

        $signingInput = "$header.$payload";
        if (!openssl_sign($signingInput, $signature, JAAS_PRIVATE_KEY, OPENSSL_ALGO_SHA256)) {
            Response(500, false, 'JWT signing failed — check JAAS_PRIVATE_KEY in config/jaas.php');
        }
        return "$header.$payload." . $this->b64url($signature);
    }

    private function b64url(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function requireAnyAuth(): void {
        if (!AuthMiddleware::isLoggedIn() && !UserAuthMiddleware::isLoggedIn()) {
            Response(401, false, 'Unauthorized');
        }
    }

    private function getAuthor(): array {
        if (AuthMiddleware::isLoggedIn()) return [AuthMiddleware::adminId(), 'admin'];
        return [UserAuthMiddleware::userId(), 'user'];
    }

    private function getOrgIdFromProject(int $project_id): int {
        try {
            $db   = \Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT organization_id FROM project WHERE project_id=?");
            $stmt->execute([$project_id]);
            return (int) ($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) { return 0; }
    }

    private function getOrgIdFromMeeting(int $meeting_id): int {
        try {
            $db   = \Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT p.organization_id FROM meeting m JOIN project p ON p.project_id=m.project_id WHERE m.meeting_id=?");
            $stmt->execute([$meeting_id]);
            return (int) ($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) { return 0; }
    }
}