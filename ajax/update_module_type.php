<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
secureSessionStart();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/ActivityLogger.php';

if (! isset($_POST['type_key']) || ! isset($_POST['is_enabled'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$typeKey   = $_POST['type_key'];
$isEnabled = (int) $_POST['is_enabled'];

$userId       = $_SESSION['user_id'] ?? null;
$userName     = $_SESSION['user_name'] ?? 'Unknown User';
$userTimezone = $_SESSION['user_timezone'] ?? 'UTC';

// Capture context
$ip      = getClientIP();
$ua      = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$browser = getBrowserName($ua);
$device  = getDeviceType($ua);
$geo     = $_SESSION['geo'] ?? [
    'city'    => null,
    'region'  => null,
    'country' => null,
    'raw'     => null,
];

try {
    $pdo = Database::getInstance();

    // Get old value for audit
    $stmtOld = $pdo->prepare("SELECT is_enabled FROM zentra_module_types WHERE type_key = :key");
    $stmtOld->execute(['key' => $typeKey]);
    $oldValue = (int) $stmtOld->fetchColumn();

    // Update module type
    $stmt = $pdo->prepare("
        UPDATE zentra_module_types SET
            is_enabled = :enabled,
            last_updated_by = :user_id,
            last_updated_on = UTC_TIMESTAMP()
        WHERE type_key = :key
    ");
    $stmt->execute([
        'enabled' => $isEnabled,
        'user_id' => $userId,
        'key'     => $typeKey,
    ]);

    // Log activity
    $logger = new ActivityLogger($pdo);
    $logger->log(
        $userId,
        "Module Type Updated",
        "UPDATE",
        [
            'user_name'     => $userName,
            'user_timezone' => $userTimezone,
            'type_key'      => $typeKey,
            'old_value'     => $oldValue,
            'new_value'     => $isEnabled,
            'ip'            => $ip,
            'browser'       => $browser,
            'device'        => $device,
            'city'          => $geo['city'],
            'region'        => $geo['region'],
            'country'       => $geo['country'],
            'geo_raw'       => $geo['raw'],
        ]
    );

    echo json_encode([
        'success' => true,
        'status'  => $isEnabled == 1 ? 'Active' : 'Inactive',
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
