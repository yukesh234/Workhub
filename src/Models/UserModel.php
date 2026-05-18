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

    // ── Create ────────────────────────────────────────────────────────
    public function createUser(
        string  $name,
        string  $email,
        string  $password,
        int     $organization_id,
        string  $role,
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

    //  Edit 
    public function editUser(
        int     $user_id,
        string  $name,
        string  $email,
        string  $role,
        ?string $userProfile       = null,
        ?string $profile_public_id = null
    ): array {
        try {
            // Check email uniqueness — exclude the current user
            $stmt = $this->db->prepare("
                SELECT user_id FROM \"user\"
                WHERE email = :email AND user_id != :user_id
            ");
            $stmt->execute([':email' => $email, ':user_id' => $user_id]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already in use by another member'];
            }

            // Only update profile columns when a new image was supplied
            if ($userProfile !== null) {
                $stmt = $this->db->prepare("
                    UPDATE \"user\"
                    SET name              = :name,
                        email             = :email,
                        role              = :role,
                        \"userProfile\"   = :userProfile,
                        profile_public_id = :profile_public_id
                    WHERE user_id = :user_id
                ");
                $stmt->execute([
                    ':name'              => $name,
                    ':email'             => $email,
                    ':role'              => $role,
                    ':userProfile'       => $userProfile,
                    ':profile_public_id' => $profile_public_id,
                    ':user_id'           => $user_id,
                ]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE \"user\"
                    SET name  = :name,
                        email = :email,
                        role  = :role
                    WHERE user_id = :user_id
                ");
                $stmt->execute([
                    ':name'    => $name,
                    ':email'   => $email,
                    ':role'    => $role,
                    ':user_id' => $user_id,
                ]);
            }

            return ['success' => true, 'message' => 'Member updated successfully'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    //  Delete 
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

    //  Delete all members for an org 
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
                $ph     = implode(',', array_map(fn($i) => ':u' . $i, array_keys($userIds)));
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

    //  Password 
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
            $stmt->execute([':password' => $hashedPassword, ':user_id' => $user_id]);
            return ['success' => true, 'message' => 'Password updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    //  Queries 
    public function getUserById(int $user_id): array|false
    {
        try {
            $stmt = $this->db->prepare("
                SELECT user_id, name, email, \"userProfile\", profile_public_id, role, created_at
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
            return [];
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
            return false;
        }
    }

    public function getUserRole(int $user_id): array|false
    {
        $stmt = $this->db->prepare("SELECT role FROM \"user\" WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

     public function getManagerAnalytics(int $user_id): array
    {
        try {
            // ── 1. Projects the manager belongs to 
            $stmt = $this->db->prepare("
                SELECT p.project_id, p.name, p.status, p.created_at
                FROM project p
                INNER JOIN project_members pm
                    ON pm.project_id = p.project_id
                WHERE pm.user_id = :uid
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([':uid' => $user_id]);
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
            if (empty($projects)) {
                return ['success' => true, 'data' => [
                    'projects'          => [],
                    'headline'          => ['total_tasks'=>0,'completed'=>0,'overdue'=>0,'active_members'=>0,'completion_rate'=>0],
                    'tasks_by_project'  => [],
                    'workload'          => [],
                    'priority_split'    => [],
                    'weekly_completions'=> [],
                ]];
            }
 
            $projectIds = array_column($projects, 'project_id');
            $ph         = implode(',', array_fill(0, count($projectIds), '?'));
 
            // ── 2. All tasks across those projects 
            $stmt = $this->db->prepare("
                SELECT
                    t.task_id, t.project_id, t.title, t.status, t.priority,
                    t.due_date, t.assigned_to, t.created_at,
                    u.name AS assigned_user_name
                FROM task t
                LEFT JOIN \"user\" u ON u.user_id = t.assigned_to
                WHERE t.project_id IN ($ph)
            ");
            $stmt->execute($projectIds);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
            // ── 3. All members across those projects ──────────────────
            $stmt = $this->db->prepare("
                SELECT DISTINCT pm.user_id, pm.project_id, u.name, u.\"userProfile\", u.role
                FROM project_members pm
                INNER JOIN \"user\" u ON u.user_id = pm.user_id
                WHERE pm.project_id IN ($ph)
            ");
            $stmt->execute($projectIds);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
            $now = new DateTime(); $now->setTime(0,0,0);
 
            // ── 4. Headline stats ─────────────────────────────────────
            $total     = count($tasks);
            $completed = count(array_filter($tasks, fn($t) => $t['status'] === 'completed'));
            $overdue   = count(array_filter($tasks, function($t) use ($now) {
                if (!$t['due_date'] || $t['status'] === 'completed') return false;
                return new DateTime($t['due_date']) < $now;
            }));
            $activeMembers = count(array_unique(array_filter(
                array_column($tasks, 'assigned_to'),
                fn($id) => $id !== null
            )));
            $completionRate = $total > 0 ? round(($completed / $total) * 100) : 0;
 
            // ── 5. Tasks per project (for project health table) ───────
            $tasksByProject = [];
            foreach ($projects as $p) {
                $pid   = $p['project_id'];
                $pTasks = array_filter($tasks, fn($t) => $t['project_id'] == $pid);
                $pTotal = count($pTasks);
                $pDone  = count(array_filter($pTasks, fn($t) => $t['status'] === 'completed'));
                $pOver  = count(array_filter($pTasks, function($t) use ($now) {
                    if (!$t['due_date'] || $t['status'] === 'completed') return false;
                    return new DateTime($t['due_date']) < $now;
                }));
                $tasksByProject[] = [
                    'project_id'      => $pid,
                    'name'            => $p['name'],
                    'status'          => $p['status'],
                    'total'           => $pTotal,
                    'completed'       => $pDone,
                    'overdue'         => $pOver,
                    'completion_rate' => $pTotal > 0 ? round(($pDone / $pTotal) * 100) : 0,
                ];
            }
 
            // ── 6. Member workload 
            $memberMap = [];
            foreach ($members as $m) {
                $uid = $m['user_id'];
                if (!isset($memberMap[$uid])) {
                    $memberMap[$uid] = [
                        'user_id'     => $uid,
                        'name'        => $m['name'],
                        'userProfile' => $m['userProfile'],
                        'total'       => 0,
                        'completed'   => 0,
                        'pending'     => 0,
                        'overdue'     => 0,
                    ];
                }
            }
            foreach ($tasks as $t) {
                if (!$t['assigned_to'] || !isset($memberMap[$t['assigned_to']])) continue;
                $m =& $memberMap[$t['assigned_to']];
                $m['total']++;
                if ($t['status'] === 'completed') {
                    $m['completed']++;
                } else {
                    $m['pending']++;
                    if ($t['due_date'] && new DateTime($t['due_date']) < $now) {
                        $m['overdue']++;
                    }
                }
            }
            $workload = array_values($memberMap);
            usort($workload, fn($a, $b) => $b['total'] - $a['total']);
 
            // ── 7. Priority split ─────────────────────────────────────
            $prios = ['critical'=>0,'high'=>0,'medium'=>0,'low'=>0];
            foreach ($tasks as $t) {
                if (isset($prios[$t['priority']])) $prios[$t['priority']]++;
            }
            $prioritySplit = [];
            foreach ($prios as $k => $v) {
                $prioritySplit[] = ['priority'=>$k, 'count'=>$v];
            }
 
            //  8. Weekly completions (last 8 weeks) 
            $weeks = [];
            for ($i = 7; $i >= 0; $i--) {
                $d = new DateTime();
                $d->modify("-$i weeks");
                $weeks[] = $d->format('o') . '-W' . $d->format('W');  // e.g. "2026-W20"
            }
            $weeklyMap = array_fill_keys($weeks, 0);

            foreach ($tasks as $t) {
                if ($t['status'] !== 'completed' || !$t['created_at']) continue;
                $td  = new DateTime($t['created_at']);
                $key = $td->format('o') . '-W' . $td->format('W');    // same format
                if (isset($weeklyMap[$key])) $weeklyMap[$key]++;
            }

            $weeklyCompletions = [];
            foreach ($weeklyMap as $w => $count) {
                // $w is like "2026-W20"
                [, $weekNum] = explode('-W', $w);                      // now splits correctly
                $weeklyCompletions[] = [
                    'week'  => 'W' . $weekNum,                        // "W20"
                    'count' => $count,
                ];
            }
            return [
                'success' => true,
                'data'    => [
                    'projects'          => $projects,
                    'headline'          => [
                        'total_tasks'     => $total,
                        'completed'       => $completed,
                        'overdue'         => $overdue,
                        'active_members'  => $activeMembers,
                        'completion_rate' => $completionRate,
                    ],
                    'tasks_by_project'  => $tasksByProject,
                    'workload'          => $workload,
                    'priority_split'    => $prioritySplit,
                    'weekly_completions'=> $weeklyCompletions,
                ],
            ];
 
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }
}