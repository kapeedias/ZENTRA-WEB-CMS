<?php

require_once __DIR__ . '/ModuleBase.php';
require_once __DIR__ . '/ActivityLogger.php';

class EventsModule extends ModuleBase
{
    public function __construct($db, $object_id = null)
    {
        parent::__construct($db, 'events', $object_id);
    }

    /* -----------------------------------------------------------
     * DEFAULT SETTINGS
     * ----------------------------------------------------------- */
    public function getDefaultSettings()
    {
        return [
            'show_past_events' => '0',
            'default_view'     => 'list', // list | calendar
            'timezone'         => 'UTC',  // per-module/site timezone
            'enable_recurring' => '1',
            'events_per_page'  => '20',
        ];
    }

    protected function getTimezone(): string
    {
        return $this->getSetting('timezone', 'UTC');
    }

    /* -----------------------------------------------------------
     * CREATE EVENT (UTC + LOG)
     * ----------------------------------------------------------- */
    public function create(array $data, int $userId, array $context = [])
    {
        $this->validateRequired($data, ['title', 'event_start_date', 'event_start_time']);

        $sql = "
        INSERT INTO zentra_events
        (
            object_id,
            event_slug,
            title,
            event_description,
            event_location,
            event_start_date,
            event_end_date,
            event_start_time,
            event_end_time,
            event_timezone,
            is_event_all_day,
            is_event_active,
            event_category,
            color_code,
            created_by,
            created_on
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $this->object_id,
            $data['event_slug'],
            $this->sanitize($data['title']),
            $this->sanitize($data['event_description'] ?? ''),
            $this->sanitize($data['event_location'] ?? null),
            $data['event_start_date'],
            $data['event_end_date'],
            $data['event_start_time'],
            $data['event_end_time'],
            $data['event_timezone'] ?? 'UTC',
            isset($data['is_event_all_day']) ? (int) $data['is_event_all_day'] : 0,
            1, // default active
            $data['event_category'] ?? null,
            $data['color_code'] ?? null,
            $userId,
        ]);

        $eventId = (int) $this->db->lastInsertId();

        if ($this->logger) {
            $this->logger->log($userId, 'Event Created', 'create', array_merge($context, [
                'event_id' => $eventId,
                'title'    => $data['title'],
                'slug'     => $data['event_slug'],
                'start'    => $data['event_start_date'] . ' ' . $data['event_start_time'],
                'end'      => $data['event_end_date'] . ' ' . $data['event_end_time'],
            ]));
        }

        return $eventId;
    }

    /* -----------------------------------------------------------
     * UPDATE EVENT (UTC + LOG DIFF)
     * ----------------------------------------------------------- */
    public function update(int $id, array $data, int $userId, array $context = [])
    {
        $existing = $this->getRawEvent($id);
        if (! $existing) {
            return false;
        }

        $userTz   = $data['user_timezone'] ?? $this->getTimezone();
        $startUTC = isset($data['start_date']) && $data['start_date'] !== ''
            ? $this->toUTC($data['start_date'], $userTz)
            : $existing['start_date'];

        $endUTC = array_key_exists('end_date', $data) && $data['end_date'] !== ''
            ? $this->toUTC($data['end_date'], $userTz)
            : $existing['end_date'];

        $title       = $this->sanitize($data['title'] ?? $existing['title']);
        $description = $this->sanitize($data['description'] ?? $existing['description']);
        $isAllDay    = isset($data['is_all_day'])
            ? (int) $data['is_all_day']
            : (int) $existing['is_all_day'];
        $recurrence = array_key_exists('recurrence_rule', $data)
            ? $data['recurrence_rule']
            : $existing['recurrence_rule'];

        $sql = "
            UPDATE zentra_events
            SET title = ?, description = ?, start_date = ?, end_date = ?,
                is_all_day = ?, recurrence_rule = ?, updated_at = UTC_TIMESTAMP()
            WHERE id = ? AND object_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $ok   = $stmt->execute([
            $title,
            $description,
            $startUTC,
            $endUTC,
            $isAllDay,
            $recurrence,
            $id,
            $this->object_id,
        ]);

        if ($ok && $this->logger) {
            $changes = [
                'title'          => [$existing['title'], $title],
                'description'    => [$existing['description'], $description],
                'start_date_utc' => [$existing['start_date'], $startUTC],
                'end_date_utc'   => [$existing['end_date'], $endUTC],
                'is_all_day'     => [$existing['is_all_day'], $isAllDay],
                'recurrence'     => [$existing['recurrence_rule'], $recurrence],
            ];

            $this->logger->log($userId, 'Event Updated', 'update', array_merge($context, [
                'field_changed' => 'event',
                'old_value'     => json_encode($existing),
                'new_value'     => json_encode($changes),
            ]));
        }

        return $ok;
    }

    /* -----------------------------------------------------------
     * DELETE EVENT (LOG)
     * ----------------------------------------------------------- */
    public function delete(int $id, int $userId, array $context = [])
    {
        $existing = $this->getRawEvent($id);
        if (! $existing) {
            return false;
        }

        $stmt = $this->db->prepare("
            DELETE FROM zentra_events
            WHERE id = ? AND object_id = ?
        ");
        $ok = $stmt->execute([$id, $this->object_id]);

        if ($ok && $this->logger) {
            $this->logger->log($userId, 'Event Deleted', 'delete', array_merge($context, [
                'field_changed' => 'event',
                'old_value'     => json_encode($existing),
                'new_value'     => null,
            ]));
        }

        return $ok;
    }

    /* -----------------------------------------------------------
     * ENABLE / DISABLE (ACTIVE FLAG + LOG)
     * ----------------------------------------------------------- */
    public function setActive(int $id, bool $active, int $userId, array $context = [])
    {
        $existing = $this->getRawEvent($id);
        if (! $existing) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE zentra_events
            SET is_active = ?, updated_at = UTC_TIMESTAMP()
            WHERE id = ? AND object_id = ?
        ");
        $ok = $stmt->execute([(int) $active, $id, $this->object_id]);

        if ($ok && $this->logger) {
            $this->logger->log($userId, 'Event Status Changed', 'status', array_merge($context, [
                'field_changed' => 'is_active',
                'old_value'     => $existing['is_active'],
                'new_value'     => (int) $active,
            ]));
        }

        return $ok;
    }

    /* -----------------------------------------------------------
     * GET SINGLE EVENT (WITH LOCAL DATES)
     * ----------------------------------------------------------- */
    public function get(int $id, ?string $userTimezone = null)
    {
        $event = $this->getRawEvent($id);
        if (! $event) {
            return null;
        }

        $tz = $userTimezone ?: $this->getTimezone();

        $event['start_date_local'] = $this->fromUTC($event['start_date'], $tz);
        $event['end_date_local']   = $this->fromUTC($event['end_date'], $tz);

        return $event;
    }

    /* -----------------------------------------------------------
     * LIST EVENTS (WITH LOCAL DATES)
     * ----------------------------------------------------------- */
    public function list(array $filters = [], ?string $userTimezone = null)
    {
        $sql = "
            SELECT *
            FROM zentra_events
            WHERE object_id = ?
        ";

        $params = [$this->object_id];

        if (! empty($filters['future_only'])) {
            $sql .= " AND start_date >= UTC_TIMESTAMP()";
        }

        if (isset($filters['active_only']) && $filters['active_only']) {
            $sql .= " AND is_active = 1";
        }

        $sql .= " ORDER BY start_date ASC";

        if (! empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $tz     = $userTimezone ?: $this->getTimezone();

        foreach ($events as &$e) {
            $e['start_date_local'] = $this->fromUTC($e['start_date'], $tz);
            $e['end_date_local']   = $this->fromUTC($e['end_date'], $tz);
        }

        return $events;
    }

    /* -----------------------------------------------------------
     * RAW EVENT FETCH (UTC)
     * ----------------------------------------------------------- */
    protected function getRawEvent(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM zentra_events
            WHERE id = ? AND object_id = ?
        ");
        $stmt->execute([$id, $this->object_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* -----------------------------------------------------------
     * RECURRENCE HELPERS
     * ----------------------------------------------------------- */
    public function parseRecurrence(?string $rule): ?array
    {
        if (! $rule) {
            return null;
        }

        $parts  = explode(';', $rule);
        $parsed = [];

        foreach ($parts as $part) {
            if (strpos($part, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $part, 2);
            $parsed[$key]  = $value;
        }

        return $parsed;
    }
}
