<?php

require_once __DIR__ . '/Database.php';

class SettingModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getOrgById(int $org_id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM organization WHERE organization_id = ? LIMIT 1");
        $stmt->execute([$org_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateOrgInfo(int $org_id, string $name, string $slogan): array {
        try {
            $stmt = $this->db->prepare("
                UPDATE organization
                SET name = ?, slogan = ?
                WHERE organization_id = ?
            ");
            $stmt->execute([$name, $slogan, $org_id]);
            return ['success' => true, 'message' => 'Organization updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateOrgLogo(int $org_id, ?string $logoUrl, ?string $publicId): array {
        try {
            $stmt = $this->db->prepare("
                UPDATE organization
                SET organization_logo = ?,
                    logo_public_id    = ?
                WHERE organization_id = ?
            ");
            $stmt->execute([$logoUrl, $publicId, $org_id]);
            return ['success' => true, 'message' => 'Logo updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteOrganization(int $org_id): array {
        try {
            $this->db->beginTransaction();

            $this->db->prepare("DELETE FROM activity_log WHERE org_id = ?")->execute([$org_id]);

            $this->db->prepare("
                DELETE FROM task_attachment
                WHERE task_id IN (
                    SELECT t.task_id FROM task t
                    JOIN project p ON p.project_id = t.project_id
                    WHERE p.organization_id = ?
                )
            ")->execute([$org_id]);

            $this->db->prepare("
                DELETE FROM task_comment
                WHERE task_id IN (
                    SELECT t.task_id FROM task t
                    JOIN project p ON p.project_id = t.project_id
                    WHERE p.organization_id = ?
                )
            ")->execute([$org_id]);

            $this->db->prepare("
                DELETE FROM task
                WHERE project_id IN (
                    SELECT project_id FROM project WHERE organization_id = ?
                )
            ")->execute([$org_id]);

            $this->db->prepare("
                DELETE FROM project_members
                WHERE project_id IN (
                    SELECT project_id FROM project WHERE organization_id = ?
                )
            ")->execute([$org_id]);

            $this->db->prepare("
                DELETE FROM meeting
                WHERE project_id IN (
                    SELECT project_id FROM project WHERE organization_id = ?
                )
            ")->execute([$org_id]);

            $this->db->prepare("DELETE FROM project      WHERE organization_id = ?")->execute([$org_id]);
            $this->db->prepare("DELETE FROM \"user\"     WHERE organization_id = ?")->execute([$org_id]);
            $this->db->prepare("DELETE FROM organization WHERE organization_id = ?")->execute([$org_id]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Organization deleted'];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deleteAllMembers(int $org_id): array {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT user_id FROM \"user\" WHERE organization_id = ?");
            $stmt->execute([$org_id]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($userIds)) {
                // Build a placeholder list: $1, $2, $3 ...
                $ph = implode(',', array_map(fn($i) => '?' , array_keys($userIds)));

                $this->db->prepare("DELETE FROM project_members WHERE user_id IN ($ph)")->execute($userIds);
                $this->db->prepare("UPDATE task SET assigned_to = NULL WHERE assigned_to IN ($ph)")->execute($userIds);
                $this->db->prepare("
                    DELETE FROM activity_log
                    WHERE actor_id IN ($ph) AND actor_type = 'user'
                ")->execute($userIds);
            }

            $stmt  = $this->db->prepare("DELETE FROM \"user\" WHERE organization_id = ?");
            $stmt->execute([$org_id]);
            $count = $stmt->rowCount();

            $this->db->commit();
            return ['success' => true, 'message' => 'All members removed', 'count' => $count];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function changeAdminPassword(int $admin_id, string $currentPw, string $newPw): array {
        try {
            $stmt = $this->db->prepare("SELECT password FROM admin WHERE id = ? LIMIT 1");
            $stmt->execute([$admin_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return ['success' => false, 'message' => 'Admin not found'];
            }
            if (!password_verify($currentPw, $row['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }

            $hash = password_hash($newPw, PASSWORD_DEFAULT);
            $this->db->prepare("UPDATE admin SET password = ? WHERE id = ?")->execute([$hash, $admin_id]);

            return ['success' => true, 'message' => 'Password updated'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}