<?php
declare (strict_types = 1);

class User
{
    private ?PDO $pdo = null;

    private string $activityTable = 'zentra_useractivityaudit';
    private string $userTable     = 'zentra_users';

    // Core identity
    public int $id           = 0;
    public string $full_name = '';
    public string $timezone  = 'UTC';

    // Multi-tenant
    public int $tenant_id = 0;

    // Additional identity fields
    public string $email     = '';
    public string $firstName = '';
    public string $lastName  = '';

    // RBAC (optional)
    public array $roles       = [];
    public array $permissions = [];

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
    public function register(array $data): int
    {
        $pdo = $this->requirePdo('register');

        // 1. Tenant (Option A)
        $tenantId = $data['tenant_id'] ?? null;
        if (! $tenantId) {
            throw new RuntimeException("Tenant ID is required for user registration.");
        }

        // 2. Input
        $plainPassword = $data['plainPassword'] ?? generatePassword();

        $firstName = $data['first_name'] ?? ($_POST['first_name'] ?? '');
        $lastName  = $data['last_name'] ?? ($_POST['last_name'] ?? '');
        $email     = $data['user_email'] ?? ($_POST['user_email'] ?? '');
        $username  = $data['user_name'] ?? $email;

        // 3. Environment context
        $ip      = $_SESSION['user_ip'] ?? cleanIP(getClientIP());
        $agent   = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $browser = getBrowserName($agent);
        $device  = getDeviceType($agent);
        $geo     = $_SESSION['geo'] ?? [];

        // 4. Prevent duplicate email
        $exists = $pdo->prepare("SELECT COUNT(*) FROM {$this->userTable} WHERE user_email = ?");
        $exists->execute([$email]);

        if ((int) $exists->fetchColumn() > 0) {
            throw new RuntimeException("The email address '{$email}' is already registered.");
        }

        // 5. Insert user
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
            'termination_reason'      => null, // FIXED SECURITY ISSUE
            'tenant_id'               => $tenantId,
        ];

        $columns      = implode(', ', array_keys($payload));
        $placeholders = ':' . implode(', :', array_keys($payload));

        $stmt = $pdo->prepare("INSERT INTO {$this->userTable} ({$columns}) VALUES ({$placeholders})");
        $stmt->execute($payload);

        $userId = (int) $pdo->lastInsertId();

        // 6. SOC2 logger
        $logger = new ActivityLogger($pdo, $tenantId);

        $logger->log(
            $userId,
            "User Registered: {$email}",
            'User Registered',
            [
                'user_name'     => $firstName,
                'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                'tenant_id'     => $tenantId,

                'geo_raw'       => $geo['raw'] ?? null,
                'ip'            => $ip,
                'browser'       => $browser,
                'device'        => $device,
                'city'          => $geo['city'] ?? null,
                'region'        => $geo['region'] ?? null,
                'country'       => $geo['country'] ?? null,

                'audit_payload' => [

                    // EVENT BLOCK
                    'event'               => [
                        'type'       => 'user_registered',
                        'identifier' => "user:{$userId}",
                        'success'             => true,
                        'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                        'event_time_local'    => (new DateTime('now', new DateTimeZone($_SESSION['user_timezone'] ?? 'UTC')))
                            ->format('Y-m-d H:i:s'),
                        'event_user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                        'session_id'          => session_id(),
                        'ip'                  => $ip,
                    ],

                    // USER BLOCK
                    'user'          => [
                        'user_id'    => $userId,
                        'username'   => $email,
                        'first_name' => $firstName,
                        'tenant_id'  => $tenantId,
                    ],

                    // LOCATION BLOCK
                    'location'      => [
                        'city'     => $geo['city'] ?? null,
                        'region'   => $geo['region'] ?? null,
                        'country'  => $geo['country'] ?? null,
                        'timezone' => $geo['timezone'] ?? null,
                        'lat'      => $geo['latitude'] ?? null,
                        'lon'      => $geo['longitude'] ?? null,
                    ],

                    // NETWORK BLOCK
                    'network'       => [
                        'asn' => $geo['asn'] ?? null,
                        'isp' => $geo['isp'] ?? null,
                    ],

                    // SECURITY BLOCK
                    'security'      => [
                        'vpn'     => $geo['vpn'] ?? null,
                        'proxy'   => $geo['proxy'] ?? null,
                        'tor'     => $geo['tor'] ?? null,
                        'hosting' => $geo['hosting'] ?? null,
                        'mobile'  => $geo['mobile'] ?? null,
                        'carrier' => $geo['carrier'] ?? null,
                        'bot'     => $geo['bot'] ?? null,
                    ],

                    // DEVICE BLOCK
                    'device'        => [
                        'browser' => $browser,
                        'device'  => $device,
                    ],
                ],
            ]
        );

        return $userId;
    }
    public function login(string $username, string $password, string $ip): bool
    {
        $pdo = $this->requirePdo('login');

        // 1. Fetch user by username or email
        $stmt = $pdo->prepare(
            "SELECT * FROM {$this->userTable}
         WHERE user_name = :username OR user_email = :username
         LIMIT 1"
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Validate password
        if ($user && password_verify($password, (string) $user['pwd'])) {

            // 3. Store session values
            $_SESSION['user']          = $user;
            $_SESSION['user_id']       = (int) $user['id'];
            $_SESSION['user_email']    = (string) $user['user_email'];
            $_SESSION['user_name']     = (string) ($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['user_timezone'] = (string) ($user['timezone'] ?? 'UTC');
            $_SESSION['tenant_id']     = (int) $user['tenant_id']; // ← CRITICAL

            // 4. Build environment context
            $tenantId = $_SESSION['tenant_id'];

            $agent   = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $browser = getBrowserName($agent);
            $device  = getDeviceType($agent);
            $geo     = $_SESSION['geo'] ?? [];

            // 5. SOC2 logger
            $logger = new ActivityLogger($pdo, $tenantId);

            $logger->log(
                (int) $user['id'],
                "User Logged In: {$user['user_email']}",
                'User Logged In',
                [
                    'user_name'     => $_SESSION['user_name'],
                    'user_timezone' => $_SESSION['user_timezone'],
                    'tenant_id'     => $tenantId,

                    'geo_raw'       => $geo['raw'] ?? null,
                    'ip'            => $ip,
                    'browser'       => $browser,
                    'device'        => $device,
                    'city'          => $geo['city'] ?? null,
                    'region'        => $geo['region'] ?? null,
                    'country'       => $geo['country'] ?? null,

                    'audit_payload' => [

                        // EVENT BLOCK
                        'event'               => [
                            'type'       => 'user_logged_in',
                            'identifier' => "user:{$user['id']}",
                            'success'             => true,
                            'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                            'event_time_local'    => (new DateTime('now', new DateTimeZone($_SESSION['user_timezone'])))
                                ->format('Y-m-d H:i:s'),
                            'event_user_timezone' => $_SESSION['user_timezone'],
                            'session_id'          => session_id(),
                            'ip'                  => $ip,
                        ],

                        // USER BLOCK
                        'user'          => [
                            'user_id'    => (int) $user['id'],
                            'username'   => $user['user_email'],
                            'first_name' => $user['first_name'],
                            'tenant_id'  => $tenantId,
                        ],

                        // LOCATION BLOCK
                        'location'      => [
                            'city'     => $geo['city'] ?? null,
                            'region'   => $geo['region'] ?? null,
                            'country'  => $geo['country'] ?? null,
                            'timezone' => $geo['timezone'] ?? null,
                            'lat'      => $geo['latitude'] ?? null,
                            'lon'      => $geo['longitude'] ?? null,
                        ],

                        // NETWORK BLOCK
                        'network'       => [
                            'asn' => $geo['asn'] ?? null,
                            'isp' => $geo['isp'] ?? null,
                        ],

                        // SECURITY BLOCK
                        'security'      => [
                            'vpn'     => $geo['vpn'] ?? null,
                            'proxy'   => $geo['proxy'] ?? null,
                            'tor'     => $geo['tor'] ?? null,
                            'hosting' => $geo['hosting'] ?? null,
                            'mobile'  => $geo['mobile'] ?? null,
                            'carrier' => $geo['carrier'] ?? null,
                            'bot'     => $geo['bot'] ?? null,
                        ],

                        // DEVICE BLOCK
                        'device'        => [
                            'browser' => $browser,
                            'device'  => $device,
                        ],
                    ],
                ]
            );

            return true;
        }

        return false;
    }
    public function forgotPassword(string $email, string $ip): string | false
    {
        $pdo = $this->requirePdo('forgotPassword');

        // 1. Fetch user
        $stmt = $pdo->prepare(
            "SELECT * FROM {$this->userTable} WHERE user_email = :email LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $user) {
            return false;
        }

        // 2. Generate token + expiry
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        // 3. Store token
        $update = $pdo->prepare(
            "UPDATE {$this->userTable}
         SET reset_token = ?, reset_expires = ?
         WHERE id = ?"
        );
        $update->execute([$token, $expires, $user['id']]);

        // 4. Determine tenant (Option A: session cached)
        $tenantId = $_SESSION['tenant_id'] ?? null;

        // Fallback for public forgot-password page
        if (! $tenantId) {
            $tenantStmt = $pdo->prepare("SELECT tenant_id FROM {$this->userTable} WHERE id = ?");
            $tenantStmt->execute([$user['id']]);
            $tenantId = (int) $tenantStmt->fetchColumn();
        }

        // 5. Environment context
        $agent   = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $browser = getBrowserName($agent);
        $device  = getDeviceType($agent);
        $geo     = $_SESSION['geo'] ?? [];

        // 6. SOC2 logger
        $logger = new ActivityLogger($pdo, $tenantId);

        $logger->log(
            (int) $user['id'],
            "Password Reset Requested: {$email}",
            'Password Reset Requested',
            [
                'user_name'      => $user['first_name'],
                'user_timezone'  => $_SESSION['user_timezone'] ?? 'UTC',
                'tenant_id'      => $tenantId,

                // Forensics
                'geo_raw'        => $geo['raw'] ?? null,
                'ip'             => $ip,
                'browser'        => $browser,
                'device'         => $device,
                'city'           => $geo['city'] ?? null,
                'region'         => $geo['region'] ?? null,
                'country'        => $geo['country'] ?? null,

                // ---- SOC2 STRUCTURED PAYLOAD ----
                'audit_payload'  => [

                    // EVENT BLOCK
                    'event'               => [
                        'type'       => 'password_reset_requested',
                        'identifier' => "password_reset_request:{$user['id']}",
                        'success'             => true,
                        'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                        'event_time_local'    => (new DateTime('now', new DateTimeZone($_SESSION['user_timezone'] ?? 'UTC')))
                            ->format('Y-m-d H:i:s'),
                        'event_user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                        'session_id'          => session_id(),
                        'ip'                  => $ip,
                    ],

                    // USER BLOCK
                    'user'           => [
                        'user_id'    => (int) $user['id'],
                        'username'   => $user['user_email'],
                        'first_name' => $user['first_name'],
                        'tenant_id'  => $tenantId,
                    ],

                    // PASSWORD RESET BLOCK
                    'password_reset' => [
                        'token_created' => true,
                        'expires_at'    => $expires,
                    ],

                    // LOCATION BLOCK
                    'location'       => [
                        'city'     => $geo['city'] ?? null,
                        'region'   => $geo['region'] ?? null,
                        'country'  => $geo['country'] ?? null,
                        'timezone' => $geo['timezone'] ?? null,
                        'lat'      => $geo['latitude'] ?? null,
                        'lon'      => $geo['longitude'] ?? null,
                    ],

                    // NETWORK BLOCK
                    'network'        => [
                        'asn' => $geo['asn'] ?? null,
                        'isp' => $geo['isp'] ?? null,
                    ],

                    // SECURITY BLOCK
                    'security'       => [
                        'vpn'     => $geo['vpn'] ?? null,
                        'proxy'   => $geo['proxy'] ?? null,
                        'tor'     => $geo['tor'] ?? null,
                        'hosting' => $geo['hosting'] ?? null,
                        'mobile'  => $geo['mobile'] ?? null,
                        'carrier' => $geo['carrier'] ?? null,
                        'bot'     => $geo['bot'] ?? null,
                    ],

                    // DEVICE BLOCK
                    'device'         => [
                        'browser' => $browser,
                        'device'  => $device,
                    ],
                ],
            ]
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

            // 5. Determine tenant
            $tenantStmt = $pdo->prepare("SELECT tenant_id FROM {$this->userTable} WHERE id = ?");
            $tenantStmt->execute([$userId]);
            $tenantId = (int) $tenantStmt->fetchColumn();

            // 6. Build environment context
            $ip      = $_SESSION['user_ip'] ?? cleanIP(getClientIP());
            $agent   = $_SESSION['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
            $browser = getBrowserName($agent);
            $device  = getDeviceType($agent);
            $geo     = $_SESSION['geo'] ?? [];
            // 7. Log SOC2 event
            $logger = new ActivityLogger($pdo, $tenantId);

            $logger->log(
                $userId,
                "Password reset via token",
                "Password Reset",
                [
                    'tenant_id'     => $tenantId,
                    'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                    'ip'            => $ip,
                    'browser'       => $browser,
                    'device'        => $device,
                    'city'          => $geo['city'] ?? null,
                    'region'        => $geo['region'] ?? null,
                    'country'       => $geo['country'] ?? null,
                    'geo_raw'       => $geo['raw'] ?? null,

                    'audit_payload' => [
                        'event'               => [
                            'type'       => 'password_reset',
                            'identifier' => "user:{$userId}",
                            'success'             => true,
                            'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                            'event_time_local'    => (new DateTime('now', new DateTimeZone($_SESSION['user_timezone'] ?? 'UTC')))
                                ->format('Y-m-d H:i:s'),
                            'event_user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                            'session_id'          => session_id(),
                            'ip'                  => $ip,
                        ],

                        'user'          => [
                            'user_id'   => $userId,
                            'tenant_id' => $tenantId,
                        ],

                        'location'      => [
                            'city'     => $geo['city'] ?? null,
                            'region'   => $geo['region'] ?? null,
                            'country'  => $geo['country'] ?? null,
                            'timezone' => $geo['timezone'] ?? null,
                            'lat'      => $geo['latitude'] ?? null,
                            'lon'      => $geo['longitude'] ?? null,
                        ],

                        'network'       => [
                            'asn' => $geo['asn'] ?? null,
                            'isp' => $geo['isp'] ?? null,
                        ],

                        'security'      => [
                            'vpn'     => $geo['vpn'] ?? null,
                            'proxy'   => $geo['proxy'] ?? null,
                            'tor'     => $geo['tor'] ?? null,
                            'hosting' => $geo['hosting'] ?? null,
                            'mobile'  => $geo['mobile'] ?? null,
                            'carrier' => $geo['carrier'] ?? null,
                            'bot'     => $geo['bot'] ?? null,
                        ],

                        'device'        => [
                            'browser' => $browser,
                            'device'  => $device,
                        ],
                    ],
                ]
            );
        }

        return $success;
    }
    public function updateProfile(int $userId, array $data): void
    {
        $pdo = $this->requirePdo('updateProfile');

        // 1. Fetch current user
        $stmt = $pdo->prepare("SELECT * FROM {$this->userTable} WHERE id = ?");
        $stmt->execute([$userId]);
        $currentData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $currentData) {
            throw new RuntimeException('User not found');
        }

        // 2. Detect changes
        $changes       = [];
        $changedFields = [];

        foreach ($data as $key => $newValue) {
            $oldValue = $currentData[$key] ?? null;

            if ($oldValue != $newValue) {
                $changes[] = "{$key} changed from '{$oldValue}' to '{$newValue}'";

                // SOC2 machine‑readable diff
                $changedFields[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        // 3. Update DB
        if (! empty($data)) {
            $setParts = [];
            foreach ($data as $key => $value) {
                $setParts[] = "{$key} = :{$key}";
            }
            $setQuery   = implode(', ', $setParts);
            $data['id'] = $userId;

            $stmt = $pdo->prepare("UPDATE {$this->userTable} SET {$setQuery} WHERE id = :id");
            $stmt->execute($data);
        }

        // 4. Determine tenant
        $tenantStmt = $pdo->prepare("SELECT tenant_id FROM {$this->userTable} WHERE id = ?");
        $tenantStmt->execute([$userId]);
        $tenantId = (int) $tenantStmt->fetchColumn();

        // 5. Environment context
        $ip      = $_SESSION['user_ip'] ?? cleanIP(getClientIP());
        $agent   = $_SESSION['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $browser = getBrowserName($agent);
        $device  = getDeviceType($agent);
        $geo     = $_SESSION['geo'] ?? [];

        // 6. Unified SOC2 logger
        $logger = new ActivityLogger($pdo, $tenantId);

        $logger->log(
            $userId,
            "User updated profile",
            "Profile Updated",
            [
                'tenant_id'     => $tenantId,
                'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                'ip'            => $ip,
                'browser'       => $browser,
                'device'        => $device,
                'city'          => $geo['city'] ?? null,
                'region'        => $geo['region'] ?? null,
                'country'       => $geo['country'] ?? null,
                'geo_raw'       => $geo['raw'] ?? null,

                'audit_payload' => [
                    'event'               => [
                        'type'       => 'profile_updated',
                        'identifier' => "user:{$userId}",
                        'success'             => true,
                        'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                        'event_time_local'    => (new DateTime('now', new DateTimeZone($_SESSION['user_timezone'] ?? 'UTC')))
                            ->format('Y-m-d H:i:s'),
                        'event_user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                        'session_id'          => session_id(),
                        'ip'                  => $ip,
                    ],

                    'user'          => [
                        'user_id'   => $userId,
                        'tenant_id' => $tenantId,
                    ],

                    // MACHINE‑READABLE FIELD‑LEVEL CHANGES
                    'changes'       => $changedFields,

                    'location'      => [
                        'city'     => $geo['city'] ?? null,
                        'region'   => $geo['region'] ?? null,
                        'country'  => $geo['country'] ?? null,
                        'timezone' => $geo['timezone'] ?? null,
                        'lat'      => $geo['latitude'] ?? null,
                        'lon'      => $geo['longitude'] ?? null,
                    ],

                    'network'       => [
                        'asn' => $geo['asn'] ?? null,
                        'isp' => $geo['isp'] ?? null,
                    ],

                    'security'      => [
                        'vpn'     => $geo['vpn'] ?? null,
                        'proxy'   => $geo['proxy'] ?? null,
                        'tor'     => $geo['tor'] ?? null,
                        'hosting' => $geo['hosting'] ?? null,
                        'mobile'  => $geo['mobile'] ?? null,
                        'carrier' => $geo['carrier'] ?? null,
                        'bot'     => $geo['bot'] ?? null,
                    ],

                    'device'        => [
                        'browser' => $browser,
                        'device'  => $device,
                    ],
                ],
            ]
        );
    }
    public function track(int $userId, string $action): void
    {
        $pdo = $this->requirePdo('track');

        // 1. Tenant from session (Option A)
        $tenantId = $_SESSION['tenant_id'] ?? null;

        // Fallback only if session missing (API, cron, webhooks)
        if (! $tenantId) {
            $stmt = $pdo->prepare("SELECT tenant_id FROM {$this->userTable} WHERE id = ?");
            $stmt->execute([$userId]);
            $tenantId = (int) $stmt->fetchColumn();
        }

        // 2. Environment context
        $ip      = $_SESSION['user_ip'] ?? cleanIP(getClientIP());
        $agent   = $_SESSION['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $browser = getBrowserName($agent);
        $device  = getDeviceType($agent);
        $geo     = $_SESSION['geo'] ?? [];

        // 3. Unified logger
        $logger = new ActivityLogger($pdo, $tenantId);

        // 4. Build SOC2-compliant log
        $logger->log(
            $userId,
            "Action: {$action}",
            $action,
            [
                'user_name'     => $_SESSION['user_name'] ?? null,
                'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                'tenant_id'     => $tenantId,

                // Raw geo data (forensics)
                'geo_raw'       => $geo['raw'] ?? null,
                'ip'            => $ip,
                'browser'       => $browser,
                'device'        => $device,
                'city'          => $geo['city'] ?? null,
                'region'        => $geo['region'] ?? null,
                'country'       => $geo['country'] ?? null,

                // ---- SOC2 STRUCTURED PAYLOAD ----
                'audit_payload' => [

                    // EVENT BLOCK
                    'event'               => [
                        'type'       => strtolower(str_replace(' ', '_', $action)),
                        'identifier' => "user_action:{$userId}",
                        'success'             => true,
                        'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                        'event_time_local'    => (new DateTime('now', new DateTimeZone($_SESSION['user_timezone'] ?? 'UTC')))
                            ->format('Y-m-d H:i:s'),
                        'event_user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                        'session_id'          => session_id(),
                        'ip'                  => $ip,
                    ],

                    // USER BLOCK
                    'user'          => [
                        'user_id'    => $userId,
                        'username'   => $_SESSION['user_email'] ?? null,
                        'first_name' => $_SESSION['user_name'] ?? null,
                        'tenant_id'  => $tenantId,
                    ],

                    // OBJECT BLOCK (GENERIC)
                    'object'        => [
                        'action' => $action,
                    ],

                    // LOCATION BLOCK
                    'location'      => [
                        'city'     => $geo['city'] ?? null,
                        'region'   => $geo['region'] ?? null,
                        'country'  => $geo['country'] ?? null,
                        'timezone' => $geo['timezone'] ?? null,
                        'lat'      => $geo['latitude'] ?? null,
                        'lon'      => $geo['longitude'] ?? null,
                    ],

                    // NETWORK BLOCK
                    'network'       => [
                        'asn' => $geo['asn'] ?? null,
                        'isp' => $geo['isp'] ?? null,
                    ],

                    // SECURITY BLOCK
                    'security'      => [
                        'vpn'     => $geo['vpn'] ?? null,
                        'proxy'   => $geo['proxy'] ?? null,
                        'tor'     => $geo['tor'] ?? null,
                        'hosting' => $geo['hosting'] ?? null,
                        'mobile'  => $geo['mobile'] ?? null,
                        'carrier' => $geo['carrier'] ?? null,
                        'bot'     => $geo['bot'] ?? null,
                    ],

                    // DEVICE BLOCK
                    'device'        => [
                        'browser' => $browser,
                        'device'  => $device,
                    ],
                ],
            ]
        );
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
            header('Location: /login.php');
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

        // Create user with PDO from global Database singleton
        $pdo  = Database::getInstance();
        $user = new User($pdo);

        // Required fields
        $user->id        = (int) ($_SESSION['user_id'] ?? 0);
        $user->full_name = (string) ($_SESSION['user_name'] ?? '');
        $user->timezone  = (string) ($_SESSION['user_timezone'] ?? 'UTC');

        // Critical for multi‑tenant SaaS
        $user->tenant_id = (int) ($_SESSION['tenant_id'] ?? 0);

        // Additional useful fields
        $user->email     = (string) ($_SESSION['user_email'] ?? '');
        $user->firstName = (string) ($_SESSION['first_name'] ?? '');
        $user->lastName  = (string) ($_SESSION['last_name'] ?? '');

        // Optional: roles, permissions, etc.
        $user->roles       = $_SESSION['user_roles'] ?? [];
        $user->permissions = $_SESSION['user_permissions'] ?? [];

        return $user;
    }

}
