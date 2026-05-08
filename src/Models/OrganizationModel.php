<?php

require_once __DIR__ . '/../Middleware/AuthMiddleware.php';

class OrganizationModel {
    private PDO $db;
    private static bool $tableChecked = false;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        if (!self::$tableChecked) {
            $this->createOrganizationTable();
            self::$tableChecked = true;
        }
    }

    private function createOrganizationTable(): void {
        $sql = "
            CREATE TABLE IF NOT EXISTS organization (
                organization_id  SERIAL PRIMARY KEY,
                admin_id         INT          NOT NULL UNIQUE,
                name             VARCHAR(255) NOT NULL UNIQUE,
                slogan           VARCHAR(255),
                organization_logo VARCHAR(255),
                logo_public_id   VARCHAR(255),
                created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_org_admin FOREIGN KEY (admin_id)
                    REFERENCES admin(id) ON DELETE CASCADE
            )
        ";
        // PostgreSQL has no ON UPDATE trigger baked into the column definition;
        // use a trigger to keep updated_at current, or update it explicitly in
        // every UPDATE statement. The trigger below is created once if absent.
        $trigger = "
            DO \$\$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_trigger WHERE tgname = 'trg_organization_updated_at'
                ) THEN
                    CREATE OR REPLACE FUNCTION fn_set_updated_at()
                    RETURNS TRIGGER LANGUAGE plpgsql AS \$fn\$
                    BEGIN
                        NEW.updated_at = CURRENT_TIMESTAMP;
                        RETURN NEW;
                    END;
                    \$fn\$;

                    CREATE TRIGGER trg_organization_updated_at
                        BEFORE UPDATE ON organization
                        FOR EACH ROW EXECUTE FUNCTION fn_set_updated_at();
                END IF;
            END \$\$
        ";

        try {
            $this->db->exec($sql);
            $this->db->exec($trigger);
        } catch (PDOException $e) {
            throw new Exception("Error creating Organization table: " . $e->getMessage());
        }
    }

    public function createOrganization(
        int $admin_id,
        string $name,
        ?string $slogan   = null,
        ?string $logoUrl  = null,
        ?string $publicId = null
    ): array  {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO organization (admin_id, name, slogan, organization_logo, logo_public_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$admin_id, $name, $slogan, $logoUrl, $publicId]);

            return [
                'success'         => true,
                'message'         => 'Organization created successfully',
                'organization_id' => (int) $this->db->lastInsertId('organization_organization_id_seq'),
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getOrganizationId(int $admin_id): ?int {
        $stmt = $this->db->prepare("SELECT organization_id FROM organization WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    public function getOrganizationDetails(int $admin_id): array|false {
        $stmt = $this->db->prepare("
            SELECT organization_id, name, slogan, organization_logo, created_at
            FROM organization
            WHERE admin_id = ?
        ");
        $stmt->execute([$admin_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateOrganization(int $org_id, string $name, string $slogan): array {
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
}