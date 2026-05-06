<?php
declare (strict_types = 1);

class EventsModule
{
    private PDO $pdo;
    private int $tenant_id;
    private string $table = 'zentra_events';

    public function __construct(PDO $pdo, int $tenant_id)
    {
        $this->pdo       = $pdo;
        $this->tenant_id = $tenant_id;
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

    public function getEventById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE id = :id AND tenant_id = :tenant_id
             LIMIT 1"
        );
        $stmt->execute([
            'id'        => $id,
            'tenant_id' => $this->tenant_id,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function saveEvent(array $data, ?int $id = null, ?int $userId = null): int
    {
        $now = date('Y-m-d H:i:s');

        $payload = [
            'tenant_id'         => $this->tenant_id,
            'event_title'       => $data['event_title'] ?? '',
            'event_description' => $data['event_description'] ?? '',
            'event_start_date'  => $data['event_start_date'] ?? null,
            'event_end_date'    => $data['event_end_date'] ?? null,
            'event_status'      => $data['event_status'] ?? 'active',
            'updated_at'        => $now,
        ];

        if ($id === null) {
            $payload['created_at'] = $now;

            $columns      = implode(', ', array_keys($payload));
            $placeholders = ':' . implode(', :', array_keys($payload));

            $stmt = $this->pdo->prepare(
                "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})"
            );
            $stmt->execute($payload);

            $eventId = (int) $this->pdo->lastInsertId();

            if ($userId !== null) {
                $this->logEventActivity(
                    $userId,
                    "Created event #{$eventId} ({$payload['event_title']})",
                    'Event Created'
                );
            }

            return $eventId;
        }

        // update
        $payload['id'] = $id;

        $setParts = [];
        foreach ($payload as $key => $value) {
            if ($key === 'tenant_id' || $key === 'id') {
                continue;
            }
            $setParts[] = "{$key} = :{$key}";
        }
        $setQuery = implode(', ', $setParts);

        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table}
             SET {$setQuery}
             WHERE id = :id AND tenant_id = :tenant_id"
        );
        $stmt->execute($payload);

        if ($userId !== null) {
            $this->logEventActivity(
                $userId,
                "Updated event #{$id} ({$payload['event_title']})",
                'Event Updated'
            );
        }

        return $id;
    }

    public function deleteEvent(int $id, ?int $userId = null): void
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM {$this->table}
             WHERE id = :id AND tenant_id = :tenant_id"
        );
        $stmt->execute([
            'id'        => $id,
            'tenant_id' => $this->tenant_id,
        ]);

        if ($userId !== null) {
            $this->logEventActivity(
                $userId,
                "Deleted event #{$id}",
                'Event Deleted'
            );
        }
    }

    private function logEventActivity(int $userId, string $identifier, string $action): void
    {
        try {
            $logger = new ActivityLogger($this->pdo, $this->tenant_id);
            $logger->log($userId, $identifier, $action, [
                'ip' => cleanIP(getClientIP()),
            ]);
        } catch (Throwable $e) {
            error_log("Event logging failed: " . $e->getMessage());
        }
    }
}
