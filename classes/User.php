<?php
declare (strict_types = 1);

class User
{
    private ?PDO $pdo             = null;
    private string $activityTable = 'zentra_useractivityaudit';
    private string $userTable     = 'zentra_users';

    public int $id;
    public string $full_name;
    public string $timezone;

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo !== null) {
            $this->pdo = $pdo;
        }
    }

    private function requirePdo(string $context): PDO
    {
        if (! $this->pdo instanceof PDO) {
            throw new RuntimeException("PDO not set in User::{$context}");
        }

        return $this->pdo;
    }

    public function logActivity(
        int $userId,
        string $identifier,
        string $action,
        array $context = []
    ): void {
        if (! $this->pdo instanceof PDO) {
            return;
        }

        $tenantId = (int) ($_SESSION['tenant_id'] ?? 0);
        $logger   = new ActivityLogger($this->pdo, $tenantId);
        $logger->log($userId, $identifier, $action, $context);
    }

    public function register(array $data): int
    {
        $pdo = $this->requirePdo('register');

        $plainPassword = $data['plainPassword'] ?? generatePassword();

        $firstName = $data['first_name'] ?? ($_POST['first_name'] ?? '');
        $lastName  = $data['last_name'] ?? ($_POST['last_name'] ?? '');
        $email     = $data['user_email'] ?? ($_POST['user_email'] ?? '');
        $username  = $data['user_name'] ?? $email;
        $ip        = $data['ip'] ?? '0.0.0.0';

        $payload = [
            'first_name'              => $firstName,
            'last_name'               => $lastName,
            'user_email'              => $email,
            'user_name'               => $username,
            'pwd'                     => password_hash($plainPassword, PASSWORD_DEFAULT),
            'users_ip'                => $ip,
            'date_created'            => date('Y-m-d H:i:s'),
            'verification_email_sent' => '0000-00-00 00:00:00',
            'md5_id'                  => bin2hex(random_bytes(16)),
            'termination_reason'      => $plainPassword,
        ];

        $exists = $pdo->prepare(
            "SELECT COUNT(*) FROM {$this->userTable} WHERE user_email = ?"
        );
        $exists->execute([$email]);

        if ((int) $exists->fetchColumn() > 0) {
            throw new RuntimeException("The email address '{$email}' is already registered.");
        }

        $columns      = implode(', ', array_keys($payload));
        $placeholders = ':' . implode(', :', array_keys($payload));

        $stmt = $pdo->prepare(
            "INSERT INTO {$this->userTable} ({$columns}) VALUES ({$placeholders})"
        );
        $stmt->execute($payload);

        $userId     = (int) $pdo->lastInsertId();
        $identifier = "New user registered with email {$email} and username {$username}";

        $this->logActivity(
            $userId,
            $identifier,
            'Registered',
            ['ip' => $ip]
        );

        return $userId;
    }

    public function login(string $username, string $password, string $ip): bool
    {
        $pdo = $this->requirePdo('login');

        $stmt = $pdo->prepare(
            "SELECT * FROM {$this->userTable}
             WHERE user_name = :username OR user_email = :username
             LIMIT 1"
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, (string) $user['pwd'])) {
            $_SESSION['user']          = $user;
            $_SESSION['user_id']       = (int) $user['id'];
            $_SESSION['user_email']    = (string) $user['user_email'];
            $_SESSION['user_name']     = (string) ($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['user_timezone'] = (string) ($user['timezone'] ?? 'UTC');

            $identifier = "User with ID {$user['id']} logged in using '{$username}'";

            $this->logActivity(
                (int) $user['id'],
                $identifier,
                'Logged In',
                ['ip' => $ip]
            );

            return true;
        }

        return false;
    }

    public function forgotPassword(string $email, string $ip): string | false
    {
        $pdo = $this->requirePdo('forgotPassword');

        $stmt = $pdo->prepare(
            "SELECT * FROM {$this->userTable} WHERE user_email = :email"
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $user) {
            return false;
        }

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        $update = $pdo->prepare(
            "UPDATE {$this->userTable}
             SET reset_token = ?, reset_expires = ?
             WHERE id = ?"
        );
        $update->execute([$token, $expires, $user['id']]);

        $identifier = "Password reset requested for email {$email} from IP {$ip}";

        $this->logActivity(
            (int) $user['id'],
            $identifier,
            'Requested Password Reset',
            ['ip' => $ip]
        );

        return $token;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $pdo = $this->requirePdo('resetPassword');

        $stmt = $pdo->prepare(
            "SELECT id, user_id
             FROM zentra_password_resets
             WHERE reset_token = :token
               AND expires_at > NOW()
               AND status = 'active'
             LIMIT 1"
        );
        $stmt->execute(['token' => $token]);
        $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $resetRequest) {
            return false;
        }

        $userId  = (int) $resetRequest['user_id'];
        $resetId = (int) $resetRequest['id'];

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        if (! $hashed) {
            return false;
        }

        $updateUser = $pdo->prepare(
            "UPDATE {$this->userTable} SET pwd = :pwd WHERE id = :id"
        );
        $success = $updateUser->execute([
            'pwd' => $hashed,
            'id'  => $userId,
        ]);

        if ($success) {
            $updateReset = $pdo->prepare(
                "UPDATE zentra_password_resets
                 SET status = 'used', used_at = NOW()
                 WHERE id = :id"
            );
            $updateReset->execute(['id' => $resetId]);

            $this->logActivity(
                $userId,
                'Password reset via token',
                'Password Reset'
            );
        }

        return $success;
    }

    public function updateProfile(int $userId, array $data): void
    {
        $pdo = $this->requirePdo('updateProfile');

        $stmt = $pdo->prepare(
            "SELECT * FROM {$this->userTable} WHERE id = ?"
        );
        $stmt->execute([$userId]);
        $currentData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $currentData) {
            throw new RuntimeException('User not found');
        }

        $changes = [];
        foreach ($data as $key => $newValue) {
            $oldValue = $currentData[$key] ?? null;
            if ($oldValue != $newValue) {
                $changes[] = "{$key} changed from '{$oldValue}' to '{$newValue}'";
            }
        }

        $activityText = ! empty($changes)
            ? 'User updated profile: ' . implode('; ', $changes)
            : 'User updated profile but no changes detected';

        if (! empty($data)) {
            $setParts = [];
            foreach ($data as $key => $value) {
                $setParts[] = "{$key} = :{$key}";
            }
            $setQuery   = implode(', ', $setParts);
            $data['id'] = $userId;

            $stmt = $pdo->prepare(
                "UPDATE {$this->userTable} SET {$setQuery} WHERE id = :id"
            );
            $stmt->execute($data);
        }

        $this->logActivity($userId, $activityText, 'Updated Profile');
    }

    public function track(int $userId, string $action): void
    {
        $this->logActivity($userId, $action, $action);
    }

    public function getInitials(?string $firstName = null, ?string $lastName = null): string
    {
        if (empty($firstName) && empty($lastName)) {
            return 'Z';
        }

        $firstName = trim((string) $firstName);
        $lastName  = trim((string) $lastName);

        $initials = '';
        if ($firstName !== '') {
            $initials .= strtoupper(substr($firstName, 0, 1));
        }
        if ($lastName !== '') {
            $initials .= strtoupper(substr($lastName, 0, 1));
        }

        return $initials !== '' ? $initials : 'Z';
    }

    public function checkMasterAdminAccess(): void
    {
        if (! isset($_SESSION['user'])) {
            header('Location: login.php');
            exit;
        }

        $user = $_SESSION['user'];

        if ((int) $user['user_level'] !== 9 || $user['user_email'] !== 'abc@abc.com') {
            header('Location: myaccount.php');
            exit;
        }
    }

    public function isMasterAdmin(): bool
    {
        return isset($_SESSION['user'])
        && (int) $_SESSION['user']['user_level'] === 9
            && $_SESSION['user']['user_email'] === 'abc@abc.com';
    }

    public static function loadFromSession(): ?User
    {
        if (! isset($_SESSION['user_id'])) {
            return null;
        }

        $user            = new User();
        $user->id        = (int) $_SESSION['user_id'];
        $user->full_name = (string) ($_SESSION['user_name'] ?? '');
        $user->timezone  = (string) ($_SESSION['user_timezone'] ?? 'UTC');

        return $user;
    }
}
