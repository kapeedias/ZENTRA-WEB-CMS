<?php
declare (strict_types = 1);
class ActivityLogger
{
    protected PDO $pdo;
    protected $activityTable = "zentra_useractivityaudit";
    protected int $tenant_id;

    public function __construct(PDO $pdo, int $tenant_id)
    {
        $this->pdo       = $pdo;
        $this->tenant_id = $tenant_id;
    }

    public function log(int $userId, string $identifier, string $action, array $context = []): void
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

        // NEW: SOC2 JSON audit payload
        $auditPayload     = $context['audit_payload'] ?? null;
        $auditPayloadJson = $auditPayload ? json_encode($auditPayload, JSON_UNESCAPED_SLASHES) : null;

        $stmt = $this->pdo->prepare("
        INSERT INTO {$this->activityTable}
        (user_id, tenant_id, action, field_changed, old_value, new_value,
         created_on_utc, created_at_localtime, session_id, activity_text, geo_raw, audit_payload)
        VALUES
        (:user_id, :tenant_id, :action, :field_changed, :old_value, :new_value,
         :created_on_utc, :created_at_localtime, :session_id, :activity_text, :geo_raw, :audit_payload)
    ");

        $stmt->execute([
            'user_id'              => $userId,
            'tenant_id'            => $this->tenant_id,
            'action'               => ucfirst($action),
            'field_changed'        => $context['field_changed'] ?? null,
            'old_value'            => $context['old_value'] ?? null,
            'new_value'            => $context['new_value'] ?? null,
            'created_on_utc'       => $timestampUTC,
            'created_at_localtime' => $timestampLocal,
            'session_id'           => $sessionId,
            'activity_text'        => $activity_text,
            'geo_raw'              => $context['geo_raw'] ?? null,
            'audit_payload'        => $auditPayloadJson,
        ]);
    }

}
