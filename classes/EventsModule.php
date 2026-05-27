<?php
declare (strict_types = 1);
require_once __DIR__ . '/ActivityLogger.php';

class EventsModule
{
    private PDO $pdo;
    private int $tenant_id;
    private string $table = 'zentra_events';
    private int $object_id;

    public function __construct(PDO $pdo, int $tenant_id, int $object_id = 1)
    {
        $this->pdo       = $pdo;
        $this->tenant_id = $tenant_id;
        $this->object_id = $object_id;
    }

    public function listEvents(
        bool $activeOnly = true,
        ?string $status = null,
        ?int $nextDays = null
    ): array {
        $sql    = "SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id";
        $params = ['tenant_id' => $this->tenant_id];

        if ($activeOnly) {
            $sql .= " AND event_status = 'active'";
        }

        if ($status !== null) {
            $sql              .= " AND event_status = :status";
            $params['status']  = $status;
        }

        if ($nextDays !== null && $nextDays > 0) {
            $sql            .= " AND event_start_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)";
            $params['days']  = $nextDays;
        }

        $sql .= " ORDER BY event_start_date ASC";

        $stmt  = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getEventByHash(string $hash): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE event_hash = :hash AND tenant_id = :tenant_id
             LIMIT 1"
        );
        $stmt->execute([
            'hash'      => $hash,
            'tenant_id' => $this->tenant_id,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function saveEvent(array $data, ?string $hash = null, ?int $userId = null): string
    {
        $now    = date('Y-m-d H:i:s');
        $nowUtc = gmdate('Y-m-d H:i:s');

        $userTz   = $_SESSION['user_timezone'] ?? 'UTC';
        $dt       = new DateTime('now', new DateTimeZone($userTz));
        $nowLocal = $dt->format('Y-m-d H:i:s');

        $payload = [
            'tenant_id'            => $this->tenant_id,
            'object_id'            => $this->object_id,
            'event_slug'           => $data['event_slug'] ?? null,
            'event_title'          => $data['event_title'] ?? '',
            'event_description'    => $data['event_description'] ?? '',
            'event_location'       => $data['event_location'] ?? null,
            'event_start_date'     => $data['event_start_date'] ?? null,
            'event_end_date'       => $data['event_end_date'] ?? null,
            'event_start_time'     => $data['event_start_time'] ?? null,
            'event_end_time'       => $data['event_end_time'] ?? null,
            'start_date_utc'       => $data['start_date_utc'] ?? null,
            'end_date_utc'         => $data['end_date_utc'] ?? null,
            'event_timezone'       => $data['event_timezone'] ?? 'UTC',
            'is_event_all_day'     => $data['is_event_all_day'] ?? 0,
            'event_status'         => $data['event_status'] ?? 'Draft',
            'created_by'           => $userId,
            'created_on_utc'       => $nowUtc,
            'created_at_localtime' => $nowLocal,

        ];

        // CREATE MODE
        if ($hash === null) {

            // ⭐ Generate secure event hash
            $eventHash = substr(bin2hex(random_bytes(16)), 0, 12);

            $payload['event_hash']     = $eventHash;
            $payload['created_on_utc'] = $now;

            $columns      = implode(', ', array_keys($payload));
            $placeholders = ':' . implode(', :', array_keys($payload));

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})"
            );
            $stmt->execute($payload);

            if ($userId !== null) {
                $this->logEventActivity(
                    $userId,
                    "Created event ({$payload['event_title']})",
                    'Event Created'
                );
            }

            return $eventHash;
        }

        // UPDATE MODE
        $payload['event_hash'] = $hash;

        $setParts = [];
        foreach ($payload as $key => $value) {
            if ($key === 'tenant_id' || $key === 'event_hash') {
                continue;
            }
            $setParts[] = "{$key} = :{$key}";
        }
        $setQuery = implode(', ', $setParts);

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET {$setQuery}
             WHERE event_hash = :event_hash AND tenant_id = :tenant_id"
        );
        $stmt->execute($payload);

        if ($userId !== null) {
            $this->logEventActivity(
                $userId,
                "Created event ({$payload['event_title']})",
                'Event Created',
                [
                    'user_name'     => $_SESSION['user_name'] ?? 'Unknown',
                    'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                    'tenant_id'     => $_SESSION['tenant_id'] ?? 0,
                    'ip'            => $_SESSION['user_ip'] ?? null,
                    'browser'       => getBrowserName($_SESSION['user_agent'] ?? ''),
                    'device'        => getDeviceType($_SESSION['user_agent'] ?? ''),
                    'city'          => $_SESSION['geo']['city'] ?? null,
                    'region'        => $_SESSION['geo']['region'] ?? null,
                    'country'       => $_SESSION['geo']['country'] ?? null,
                    'geo_raw'       => $_SESSION['geo']['raw'] ?? null,
                ]
            );

        }

        return $hash;
    }

    public function deleteEvent(string $hash, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table}
             WHERE event_hash = :hash AND tenant_id = :tenant_id"
        );
        $stmt->execute([
            'hash'      => $hash,
            'tenant_id' => $this->tenant_id,
        ]);

        if ($userId !== null) {
            $this->logEventActivity(
                $userId,
                "Deleted event ({$hash})",
                'Event Deleted'
            );
        }
    }

    private function logEventActivity(
        int $userId,
        string $identifier,
        string $action,
        array $context = []
    ): void {
        try {
            // Merge default context with passed context
            $defaultContext = [
                'user_name'     => $_SESSION['user_name'] ?? 'Unknown',
                'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                'tenant_id'     => $_SESSION['tenant_id'] ?? 0,
                'ip'            => cleanIP(getClientIP()),
                'browser'       => getBrowserName($_SESSION['user_agent'] ?? ''),
                'device'        => getDeviceType($_SESSION['user_agent'] ?? ''),
                'city'          => $_SESSION['geo']['city'] ?? null,
                'region'        => $_SESSION['geo']['region'] ?? null,
                'country'       => $_SESSION['geo']['country'] ?? null,
                'geo_raw'       => $_SESSION['geo']['raw'] ?? null,
            ];

            $logger = new ActivityLogger($this->pdo, $this->tenant_id);

            $logger->log(
                $userId,
                $identifier,
                $action,
                array_merge($defaultContext, $context)
            );

        } catch (Throwable $e) {
            error_log("Event logging failed: " . $e->getMessage());
        }
    }

    public function getEventUrl(string $hash): ?string
    {
        $event = $this->getEventByHash($hash);
        if (! $event) {
            return null;
        }

        // Base URL (you can also load from config)
        $baseUrl = rtrim(getenv('APP_URL') ?: 'https://mywebsite.com', '/');

        // Extract date parts
        $year  = date('Y', strtotime($event['event_start_date']));
        $month = date('m', strtotime($event['event_start_date']));
        $day   = date('d', strtotime($event['event_start_date']));

        // Slug from DB
        $slug = $event['event_slug'];

        return "{$baseUrl}/events/{$year}/{$month}/{$day}/{$slug}";
    }

    public function getStatusBadge(string $status): array
    {
        $map = [
            'Draft'     => [
                'icon'  => 'fa-pencil text-secondary',
                'label' => 'Draft',
                'class' => 'bg-secondary-subtle text-secondary',
            ],
            'Scheduled' => [
                'icon'  => 'fa-clock text-info',
                'label' => 'Scheduled',
                'class' => 'bg-info-subtle text-info',
            ],
            'Published' => [
                'icon'  => 'fa-check-circle text-success',
                'label' => 'Published',
                'class' => 'bg-success-subtle text-success',
            ],
            'Archived'  => [
                'icon'  => 'fa-archive text-muted',
                'label' => 'Archived',
                'class' => 'bg-dark-subtle text-muted',
            ],
            'Deleted'   => [
                'icon'  => 'fa-times-circle text-danger',
                'label' => 'Deleted',
                'class' => 'bg-danger-subtle text-danger',
            ],
        ];

        return $map[$status] ?? $map['Draft'];
    }
    public function getEventLocations(): array
    {
        try {
            $sql = "SELECT
                    location_id,
                    location_name
                FROM zentra_event_locations
                WHERE tenant_id = :tenant_id
                  AND is_active = 1
                ORDER BY location_name ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':tenant_id' => $this->tenant_id,
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Error in getEventLocations(): ' . $e->getMessage());
            return [];
        }
    }
    public function updateEventPoster(int $eventId, int $libraryId, int $tenantId): bool
    {

        // Use session values set at login
        $ip      = $_SESSION['user_ip'] ?? cleanIP(getClientIP());
        $agent   = $_SESSION['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $browser = getBrowserName($agent);
        $device  = getDeviceType($agent);
        $geo     = $_SESSION['geo'] ?? [];

        // 1. Validate media belongs to tenant
        $sql = "SELECT file_url
            FROM zentra_library
            WHERE library_id = :id AND tenant_id = :tenant_id
            LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id'        => $libraryId,
            'tenant_id' => $tenantId,
        ]);

        $url = $stmt->fetchColumn();
        if (! $url) {
            return false; // media not found or not tenant-owned
        }

        // 2. Update event poster (tenant-scoped)
        $sql = "UPDATE zentra_events
            SET poster_library_id = :lib_id,
                poster_url = :url
            WHERE event_id = :event_id
              AND tenant_id = :tenant_id";

        $stmt    = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            'lib_id'    => $libraryId,
            'url'       => $url,
            'event_id'  => $eventId,
            'tenant_id' => $tenantId,
        ]);

        // 3. Log activity (tenant-aware)
        if ($success) {
            $this->logEventActivity(
                (int) $_SESSION['user_id'],
                'Event Poster Updated for Event ID ' . $eventId . ' (Media ID ' . $libraryId . ')' . ' by userid' . $_SESSION['user_id'],
                'Event posterupdated',
                [
                    'event_id'          => $eventId,
                    'poster_library_id' => $libraryId,
                    'poster_url'        => $url,
                    'tenant_id'         => $tenantId,
                    'user_name'         => $_SESSION['user_name'] ?? null,
                    'user_timezone'     => $_SESSION['user_timezone'] ?? 'UTC',
                    'ip'                => $ip,
                    'browser'           => $browser,
                    'device'            => $device,
                    'city'              => $geo['city'] ?? null,
                    'region'            => $geo['region'] ?? null,
                    'country'           => $geo['country'] ?? null,
                    'geo_raw'           => $geo['raw'] ?? null,
                ],
            );
        }

        return $success;
    }

}
