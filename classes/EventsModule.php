<?php
declare (strict_types = 1);
require_once __DIR__ . '/ActivityLogger.php';

class EventsModule
{
    private PDO $pdo;
    private int $tenant_id;
    private string $table = 'zentra_events';
    private int $object_id;
    private ActivityLogger $logger;

    public function __construct(PDO $pdo, int $tenant_id, ActivityLogger $logger, int $object_id = 1)
    {
        $this->pdo       = $pdo;
        $this->tenant_id = $tenant_id;
        $this->object_id = $object_id;
        $this->logger    = $logger;
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
    public function saveEvent(array $data, ?string $hash = null, ?int $userId = null, array $tags = []): string
    {
        // ---------------------------------------------------------
        // 1) Timestamps (strict UTC + user local)
        // ---------------------------------------------------------
        $nowUtc                          = gmdate('Y-m-d H:i:s');
        $userTz                          = $_SESSION['user_timezone'] ?? 'UTC';
        $dt                              = new DateTime('now', new DateTimeZone($userTz));
        $nowLocal                        = $dt->format('Y-m-d H:i:s');
        $payload['updated_at_utc']       = $nowUtc;
        $payload['updated_at_localtime'] = $nowLocal;

        // ---------------------------------------------------------
        // 2) Load existing event ONLY in update mode
        // ---------------------------------------------------------
        $existingEvent = null;
        if ($hash !== null) {
            // SOC2: hash-based lookup prevents ID enumeration
            $existingEvent = $this->getEventByHash($hash);
        }

        // ---------------------------------------------------------
        // 2.1) Normalize datetime-local fields BEFORE diff comparison
        // Ensures consistent DB format and accurate diff detection
        // ---------------------------------------------------------
        $datetimeFields = ['event_start_date', 'event_end_date'];
        $timeFields     = ['event_start_time', 'event_end_time'];

        foreach ($datetimeFields as $field) {
            if (! empty($data[$field]) && str_contains($data[$field], 'T')) {
                $data[$field] = str_replace('T', ' ', $data[$field]) . ':00';
            }
        }

        foreach ($timeFields as $field) {
            if (! empty($data[$field]) && str_contains($data[$field], 'T')) {
                $parts        = explode('T', $data[$field]);
                $data[$field] = $parts[1] . ':00';
            }
        }

        // ---------------------------------------------------------
        // 3) FIELD‑LEVEL DIFF LOGGING (SOC2 audit requirement)
        // ---------------------------------------------------------
        $changes = [];

        if ($existingEvent !== null) {
            foreach ($data as $key => $newValue) {
                $oldValue = $existingEvent[$key] ?? null;

                if ($oldValue === '') {
                    $oldValue = null;
                }

                if ($newValue === '') {
                    $newValue = null;
                }

                if ($oldValue != $newValue) {
                    $changes[$key] = ['old' => $oldValue, 'new' => $newValue];
                }
            }
        }

        $hasChanges = ! empty($changes);

        // ---------------------------------------------------------
        // 4) Slug handling (edit-safe, SOC2: deterministic slug rules)
        // ---------------------------------------------------------
        if ($existingEvent !== null) {

            $oldTitle = $existingEvent['event_title'] ?? null;
            $newTitle = $data['event_title'] ?? null;

            if ($oldTitle !== $newTitle) {
                // Title changed → regenerate slug
                $data['event_slug'] = $this->generateSlug(
                    $data['event_title'],
                    $data['event_start_date']
                );

                $changes['event_slug'] = [
                    'old' => $existingEvent['event_slug'],
                    'new' => $data['event_slug'],
                ];
                $hasChanges = true;

            } else {
                // Title unchanged → preserve slug
                $data['event_slug'] = $existingEvent['event_slug'];
            }

        } else {
            // CREATE MODE → always generate slug
            $data['event_slug'] = $this->generateSlug(
                $data['event_title'],
                $data['event_start_date']
            );
        }

        // ---------------------------------------------------------
        // 5) Build payload (CREATE defaults)
        // ---------------------------------------------------------
        $payload = [
            'tenant_id'            => $this->tenant_id,
            'object_id'            => $this->object_id,
            'event_slug'           => $data['event_slug'],
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
            'event_category'       => $data['event_category'] ?? 'event',
            'created_by'           => $userId,

            // Corrected names
            'created_at_utc'       => $nowUtc,
            'created_at_localtime' => $nowLocal,
        ];

        // ---------------------------------------------------------
        // 6) CREATE MODE (hash-based, SOC2-safe)
        // ---------------------------------------------------------
        if ($hash === null) {

            $eventHash             = substr(bin2hex(random_bytes(16)), 0, 12);
            $payload['event_hash'] = $eventHash;

            $columns      = implode(', ', array_keys($payload));
            $placeholders = ':' . implode(', :', array_keys($payload));

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})"
            );
            $stmt->execute($payload);

            // DB-generated PK (never exposed to UI)
            $eventId = (int) $this->pdo->lastInsertId();

            // Save tag mappings (SOC2: internal-only)
            $this->saveEventTags($eventId, $this->tenant_id, $tags, $userId);

            $this->logEventAudit(
                $userId,
                "Event Created ({$payload['event_title']})",
                'event_create',
                $eventHash,
                $payload,
                $changes
            );

            return $eventHash;
        }

        // ---------------------------------------------------------
        // 7) UPDATE MODE — detect tag changes (hash-based)
        // ---------------------------------------------------------
        $existingTagIds = $this->getEventTagsByHash($hash);
        $newTagIds      = array_map(fn($t) => (int) ($t['tagId'] ?? 0), $tags);

        sort($existingTagIds);
        sort($newTagIds);

        $tagsChanged = ($existingTagIds !== $newTagIds);

        // ---------------------------------------------------------
        // 7.1) Nothing changed → skip DB writes (SOC2: minimal writes)
        // ---------------------------------------------------------
        if (! $hasChanges && ! $tagsChanged) {
            return $hash;
        }

        // ---------------------------------------------------------
        // 7.2) Only tags changed → update tags, skip event update
        // ---------------------------------------------------------
        if (! $hasChanges && $tagsChanged) {
            $eventId = (int) $existingEvent['event_id'];
            $this->saveEventTags($eventId, $this->tenant_id, $tags, $userId);
            return $hash;
        }

        // ---------------------------------------------------------
        // 7.3) Event fields changed → update event + tags
        // ---------------------------------------------------------
        $nowUtc                          = gmdate('Y-m-d H:i:s');
        $dt                              = new DateTime('now', new DateTimeZone($userTz));
        $nowLocal                        = $dt->format('Y-m-d H:i:s');
        $payload['updated_at_utc']       = $nowUtc;
        $payload['updated_at_localtime'] = $nowLocal;

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

        $eventId = (int) $existingEvent['event_id'];

        // Save updated tag mappings
        $this->saveEventTags($eventId, $this->tenant_id, $tags, $userId);

        $this->logEventAudit(
            $userId,
            "Event Updated ({$payload['event_title']})",
            'event_update',
            $hash,
            $payload,
            $changes
        );

        return $hash;
    }

    private function logEventAudit(
        int $userId,
        string $identifier,
        string $eventType,
        string $eventHash,
        array $payload,
        array $changes
    ): void {

        $geo     = $_SESSION['geo'] ?? [];
        $ip      = $_SESSION['user_ip'] ?? getClientIP();
        $browser = getBrowserName($_SESSION['user_agent'] ?? '');
        $device  = getDeviceType($_SESSION['user_agent'] ?? '');

        $this->logger->log(
            $userId,
            $identifier,
            ucfirst(str_replace('_', ' ', $eventType)),
            [
                'user_name'     => $_SESSION['user_name'] ?? 'Unknown',
                'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                'tenant_id'     => $_SESSION['tenant_id'] ?? 0,

                'geo_raw'       => $geo['raw'] ?? null,
                'ip'            => $ip,
                'browser'       => $browser,
                'device'        => $device,
                'city'          => $geo['city'] ?? null,
                'region'        => $geo['region'] ?? null,
                'country'       => $geo['country'] ?? null,

                'audit_payload' => [
                    'event'         => [
                        'type'                => $eventType,
                        'identifier'          => $identifier,
                        'success'             => true,
                        'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                        'event_time_local'    => (new DateTime('now', new DateTimeZone($_SESSION['user_timezone'] ?? 'UTC')))
                            ->format('Y-m-d H:i:s'),
                        'event_user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                        'session_id'          => session_id(),
                        'ip'                  => $ip,
                    ],

                    'event_details' => [
                        'event_hash'     => $eventHash,
                        'event_title'    => $payload['event_title'],
                        'event_slug'     => $payload['event_slug'],
                        'event_category' => $payload['event_category'],
                        'start_date'     => $payload['event_start_date'],
                        'end_date'       => $payload['event_end_date'],
                        'all_day'        => $payload['is_event_all_day'],
                        'timezone'       => $payload['event_timezone'],
                    ],

                    'changes'       => $changes,

                    'user'          => [
                        'user_id'    => $userId,
                        'username'   => $_SESSION['user_email'] ?? null,
                        'first_name' => $_SESSION['user_name'] ?? null,
                        'tenant_id'  => $_SESSION['tenant_id'] ?? null,
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
    public function generateSlug(string $title, string $startDate): string
    {
        // 1) Normalize base slug from title
        $baseSlug = strtolower($title);
        $baseSlug = preg_replace('/[^a-z0-9]+/', '-', $baseSlug);
        $baseSlug = trim($baseSlug, '-');

        // 2) Look for existing slugs for THIS tenant on the SAME date
        //    - Same tenant only
        //    - Same calendar date (ignores time component)
        //    - Same slug prefix (for -2, -3, etc.)
        //    - Ignore hard-deleted events if you ever use that status
        $stmt = $this->pdo->prepare("
        SELECT event_slug
        FROM zentra_events
        WHERE tenant_id = :tenant_id
          AND DATE(event_start_date) = DATE(:start_date)
          AND event_slug LIKE :slug_pattern
          AND event_status <> 'Deleted'
    ");
        $stmt->execute([
            'tenant_id'    => $this->tenant_id,
            'start_date'   => $startDate,
            'slug_pattern' => $baseSlug . '%',
        ]);

        $existingSlugs = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 3) If no conflict, use base slug as-is
        if (! in_array($baseSlug, $existingSlugs, true)) {
            return $baseSlug;
        }

        // 4) Find the next available numeric suffix (-2, -3, ...)
        $suffix = 2;
        while (in_array($baseSlug . '-' . $suffix, $existingSlugs, true)) {
            $suffix++;
        }

        return $baseSlug . '-' . $suffix;
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
        // ---------------------------------------------------------
        // 0. Resolve environment + session metadata
        // ---------------------------------------------------------
        $ip      = $_SESSION['user_ip'] ?? cleanIP(getClientIP());
        $agent   = $_SESSION['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        $browser = getBrowserName($agent);
        $device  = getDeviceType($agent);
        $geo     = $_SESSION['geo'] ?? [];

        $userId   = (int) ($_SESSION['user_id'] ?? 0);
        $userName = $_SESSION['user_name'] ?? 'Unknown';
        $userTz   = $_SESSION['user_timezone'] ?? 'UTC';

        // Timestamps
        $nowUtc   = gmdate('Y-m-d H:i:s');
        $nowLocal = (new DateTime('now', new DateTimeZone($userTz)))->format('Y-m-d H:i:s');

        // ---------------------------------------------------------
        // 1. Validate media belongs to tenant
        // ---------------------------------------------------------
        $stmt = $this->pdo->prepare("
        SELECT file_url
        FROM zentra_library
        WHERE library_id = :id
        AND (tenant_id = :tenant_id OR is_global = 1)
        LIMIT 1
    ");
        $stmt->execute([
            'id'        => $libraryId,
            'tenant_id' => $tenantId,
        ]);

        $url = $stmt->fetchColumn();
        if (! $url) {
            return false; // media not found or not tenant-owned
        }

        // ---------------------------------------------------------
        // 2. Update event poster (tenant-scoped)
        // ---------------------------------------------------------
        $stmt = $this->pdo->prepare("
        UPDATE zentra_events
        SET poster_library_id   = :lib_id,
            poster_url          = :url,
            updated_at_utc      = :updated_at_utc,
            updated_at_localtime = :updated_at_localtime
        WHERE event_id = :event_id
          AND tenant_id = :tenant_id
    ");

        $success = $stmt->execute([
            'lib_id'               => $libraryId,
            'url'                  => $url,
            'event_id'             => $eventId,
            'tenant_id'            => $tenantId,
            'updated_at_utc'       => $nowUtc,
            'updated_at_localtime' => $nowLocal,
        ]);

        // ---------------------------------------------------------
        // 3. Log activity (SOC2-compliant unified audit)
        // ---------------------------------------------------------
        if ($success) {

            $eventType  = 'event_poster_updated';
            $identifier = "Event Poster Updated (Event ID {$eventId}, Media ID {$libraryId}, User {$userId} | {$userName})";

            $this->logEventActivity(
                $userId,
                $identifier,
                ucfirst(str_replace('_', ' ', $eventType)),
                [
                    'user_name'     => $userName,
                    'user_timezone' => $userTz,
                    'tenant_id'     => $tenantId,

                    'geo_raw'       => $geo['raw'] ?? null,
                    'ip'            => $ip,
                    'browser'       => $browser,
                    'device'        => $device,
                    'city'          => $geo['city'] ?? null,
                    'region'        => $geo['region'] ?? null,
                    'country'       => $geo['country'] ?? null,

                    'audit_payload' => [

                        // ---- EVENT METADATA ----
                        'event'         => [
                            'type'                => $eventType,
                            'identifier'          => $identifier,
                            'success'             => true,
                            'event_time_utc'      => $nowUtc,
                            'event_time_local'    => $nowLocal,
                            'event_user_timezone' => $userTz,
                            'session_id'          => session_id(),
                            'ip'                  => $ip,
                        ],

                        // ---- POSTER DETAILS ----
                        'event_details' => [
                            'event_id'          => $eventId,
                            'poster_library_id' => $libraryId,
                            'poster_url'        => $url,
                        ],

                        // ---- USER ----
                        'user'          => [
                            'user_id'    => $userId,
                            'username'   => $_SESSION['user_email'] ?? null,
                            'first_name' => $userName,
                            'tenant_id'  => $tenantId,
                        ],

                        // ---- LOCATION ----
                        'location'      => [
                            'city'     => $geo['city'] ?? null,
                            'region'   => $geo['region'] ?? null,
                            'country'  => $geo['country'] ?? null,
                            'timezone' => $geo['timezone'] ?? null,
                            'lat'      => $geo['latitude'] ?? null,
                            'lon'      => $geo['longitude'] ?? null,
                        ],

                        // ---- NETWORK ----
                        'network'       => [
                            'asn' => $geo['asn'] ?? null,
                            'isp' => $geo['isp'] ?? null,
                        ],

                        // ---- SECURITY ----
                        'security'      => [
                            'vpn'     => $geo['vpn'] ?? null,
                            'proxy'   => $geo['proxy'] ?? null,
                            'tor'     => $geo['tor'] ?? null,
                            'hosting' => $geo['hosting'] ?? null,
                            'mobile'  => $geo['mobile'] ?? null,
                            'carrier' => $geo['carrier'] ?? null,
                            'bot'     => $geo['bot'] ?? null,
                        ],

                        // ---- DEVICE ----
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
    public function saveEventTags(int $eventId, int $tenantId, array $tags, ?int $userId = null): void
    {
        $tagIds = [];

        foreach ($tags as $tag) {
            $userTz    = $_SESSION['user_timezone'] ?? 'UTC';
            $dt        = new DateTime('now', new DateTimeZone($userTz));
            $localTime = $dt->format('Y-m-d H:i:s');

            $name = trim($tag['name']);
            $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));

            // 1) Check if tag exists
            $stmt = $this->pdo->prepare("
            SELECT tag_id
            FROM zentra_event_tags
            WHERE tenant_id = ? AND tag_slug = ?
            LIMIT 1
        ");
            $stmt->execute([$tenantId, $slug]);
            $row = $stmt->fetch();

            if ($row) {
                $tagId = $row['tag_id'];
            } else {
                // 2) Insert new tag
                $insert = $this->pdo->prepare("
                INSERT INTO zentra_event_tags
                (tenant_id, tag_name, tag_slug, created_at_utc, created_at_localtime, created_by)
                VALUES (?, ?, ?, UTC_TIMESTAMP(), ?, ?)
            ");
                $insert->execute([
                    $tenantId,
                    $name,
                    $slug,
                    $localTime,
                    $userId,
                ]);

                $tagId = (int) $this->pdo->lastInsertId();
            }

            $tagIds[] = $tagId;

            $map = $this->pdo->prepare("
            INSERT INTO zentra_event_tag_map
            (tenant_id, event_id, tag_id, created_at_utc, created_at_localtime, created_by)
            VALUES (?, ?, ?, UTC_TIMESTAMP(), ?, ?)
            ON DUPLICATE KEY UPDATE
                updated_at_utc = UTC_TIMESTAMP(),
                updated_at_localtime = VALUES(updated_at_localtime),
                updated_by = VALUES(updated_by)
           ");

            $map->execute([
                $tenantId,  // ?
                $eventId,   // ?
                $tagId,     // ?
                $localTime, // ?
                $userId,    // created_by ?
            ]);

        }

        // 4) Remove tags that are no longer selected
        $delete = $this->pdo->prepare("
        DELETE FROM zentra_event_tag_map
        WHERE event_id = ? AND tenant_id = ?
        AND tag_id NOT IN (" . implode(',', $tagIds ?: [0]) . ")
    ");
        $delete->execute([$eventId, $tenantId]);
    }
    public function getEventTagsByHash(string $eventHash): array
    {
        $stmt = $this->pdo->prepare("
        SELECT m.tag_id
        FROM zentra_event_tag_map m
        INNER JOIN {$this->table} e
            ON e.event_id = m.event_id
           AND e.tenant_id = m.tenant_id
        WHERE e.tenant_id = ?
          AND e.event_hash = ?
        ORDER BY m.tag_id ASC
    ");
        $stmt->execute([$this->tenant_id, $eventHash]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

}
