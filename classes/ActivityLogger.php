<?php

class ActivityLogger
{
    protected $pdo;
    protected $activityTable = "zentra_useractivityaudit";

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function log($userId, string $identifier, string $action, array $context = []): void
    {
        $ip      = $context['ip'] ?? 'unknown';
        $city    = $context['city'] ?? null;
        $region  = $context['region'] ?? null;
        $country = $context['country'] ?? null;
        $browser = $context['browser'] ?? null;
        $device  = $context['device'] ?? null;
        $geo_raw = $context['geo_raw'] ?? null;

        $userName = $context['user_name'] ?? 'Unknown User';
        $userTz   = $context['user_timezone'] ?? 'UTC';

        $sessionId    = session_id();
        $timestampUTC = gmdate('Y-m-d H:i:s');

        // Convert UTC → user local time
        $dt = new DateTime($timestampUTC, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone($userTz));
        $timestampLocal = $dt->format('Y-m-d H:i:s');

        // Build readable location string
        $locationParts = array_filter([$city, $region, $country]);
        $location      = $locationParts ? implode(', ', $locationParts) : 'Unknown Location';

        // Device/browser string
        $agentInfo = trim(($browser ?: '') . ($device ? " on {$device}" : ''));
        if (! $agentInfo) {
            $agentInfo = 'Unknown Device';
        }

        // Final activity text
        $activity_text =
            "User: {$userName} | UserID: {$userId} | {$identifier} at {$timestampUTC} UTC " .
            "(Local Time: {$timestampLocal}) from IP {$ip} ({$location}) using {$agentInfo}";

        $stmt = $this->pdo->prepare("
        INSERT INTO {$this->activityTable}
        (user_id, action, field_changed, old_value, new_value, created_at, session_id, activity_text, geo_raw)
        VALUES (:user_id, :action, :field_changed, :old_value, :new_value, :created_at, :session_id, :activity_text, :geo_raw)
    ");

        $stmt->execute([
            'user_id'       => $userId,
            'action'        => ucfirst($action),
            'field_changed' => $context['field_changed'] ?? null,
            'old_value'     => $context['old_value'] ?? null,
            'new_value'     => $context['new_value'] ?? null,
            'created_at'    => $timestampUTC,
            'session_id'    => $sessionId,
            'activity_text' => $activity_text,
            'geo_raw'       => $context['geo_raw'] ?? null,
        ]);

    }
}
