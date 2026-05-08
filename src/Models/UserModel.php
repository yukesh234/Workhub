<?php

require_once __DIR__ . '/../Models/Database.php';

class UserModel
{
    private PDO $db;
    private static bool $tableChecked = false;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();

        if (!self::$tableChecked) {
            $this->createUserTable();
            self::$tableChecked = true;
        }
    }

    private function createUserTable(): void
    {
        // Ensure the shared trigger function exists
        $this->db->exec("
            CREATE OR REPLACE FUNCTION set_updated_at()
            RETURNS TRIGGER AS \$\$
            BEGIN
                NEW.updated_at = CURRENT_TIMESTAMP;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS \"user\" (
                user_id           SERIAL PRIMARY KEY,
                name              VARCHAR(255) NOT NULL,
                email             VARCHAR(255) NOT NULL UNIQUE,
                password          VARCHAR(255) NOT NULL,
                \"userProfile\"   VARCHAR(255),
                password_changed  SMALLINT NOT NULL DEFAULT 0,
                profile_public_id VARCHAR(255),
                role              VARCHAR(10) NOT NULL
                                      CHECK (role IN ('manager','member')),
                organization_id   INT NOT NULL,
                created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                FOREIGN KEY (organization_id)
                    REFERENCES organization(organization_id)
                    ON DELETE CASCADE
            );
        ");

        $this->db->exec("
            DROP TRIGGER IF EXISTS user_updated_at ON \"user\";
            CREATE TRIGGER user_updated_at
                BEFORE UPDATE ON \"user\"
                FOR EACH ROW EXECUTE FUNCTION set_updated_at();
        ");
    }

    public function createUser(
        string $name,
        string $email,
        string $password,
        int    $organization_id,
        string $role,
        ?string $userprofile       = null,
        ?string $profile_public_id = null
    ): array {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("
                INSERT INTO \"user\"
                    (name, email, password, \"userProfile\", organization_id, profile_public_id, role)
                VALUES
                    (:name, :email, :password, :userprofile, :organization_id, :profile_public_id, :role)
                RETURNING user_id
            ");

            $stmt->execute([
                ':name'              => $name,
                ':email'             => $email,
                ':password'          => $hashedPassword,
                ':userprofile'       => $userprofile,
                ':organization_id'   => $organization_id,
                ':profile_public_id' => $profile_public_id,
                ':role'              => $role,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => (int) $row['user_id'],
            ];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function deleteUser(int $user_id): array
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM \"user\" WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);

            return ['success' => true, 'message' => 'Deleted user successfully'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    public function updatePassword(string $newPassword, int $user_id): array
    {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare("
                UPDATE \"user\"
                SET password         = :password,
                    password_changed = 1
                WHERE user_id = :user_id
            ");

            $stmt->execute([
                ':password' => $hashedPassword,
                ':user_id'  => $user_id,
            ]);

            return ['success' => true, 'message' => 'Password updated successfully'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getUserById(int $user_id): array|false
    {
        try {
            $stmt = $this->db->prepare("
                SELECT user_id, name, \"userProfile\", profile_public_id, role, created_at
                FROM \"user\"
                WHERE user_id = :user_id
            ");
            $stmt->execute([':user_id' => $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return false;
        }
    }

    public function getOrganizationMember(int $organization_id): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT user_id, name, email, \"userProfile\", role, created_at
                FROM \"user\"
                WHERE organization_id = :organization_id
                ORDER BY created_at DESC
            ");
            $stmt->execute([':organization_id' => $organization_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function getUserByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare("
            SELECT user_id, name, email, password, password_changed,
                   \"userProfile\", role, organization_id
            FROM \"user\"
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function handle_member_login(string $email, string $password): array
    {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $member = $this->getUserByEmail($email);

        if (!$member || !password_verify($password, $member['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        unset($member['password']);

        return ['success' => true, 'message' => 'Logged in successfully', 'data' => $member];
    }

    public function getRole(int $user_id): array|false
    {
        try {
            $stmt = $this->db->prepare("SELECT role FROM \"user\" WHERE user_id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    public function deleteAllMembers(int $org_id): array
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "SELECT user_id FROM \"user\" WHERE organization_id = :org_id"
            );
            $stmt->execute([':org_id' => $org_id]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($userIds)) {
                // Build positional placeholders: $1, $2, ...
                $ph = implode(',', array_map(fn($i) => ':u' . $i, array_keys($userIds)));
                $params = [];
                foreach ($userIds as $i => $id) {
                    $params[':u' . $i] = $id;
                }

                $this->db->prepare("DELETE FROM project_members WHERE user_id IN ($ph)")
                    ->execute($params);
                $this->db->prepare("UPDATE task SET assigned_to = NULL WHERE assigned_to IN ($ph)")
                    ->execute($params);
                $this->db->prepare("DELETE FROM activity_log WHERE actor_id IN ($ph) AND actor_type = 'user'")
                    ->execute($params);
            }

            $stmt = $this->db->prepare("DELETE FROM \"user\" WHERE organization_id = :org_id");
            $stmt->execute([':org_id' => $org_id]);
            $count = $stmt->rowCount();

            $this->db->commit();
            return ['success' => true, 'message' => 'All members removed', 'count' => $count];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getUserRole(int $user_id){
        $stmt = $this->db->prepare("SELECT role FROM \"user\" WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}