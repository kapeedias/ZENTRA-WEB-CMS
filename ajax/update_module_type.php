<?php
require_once __DIR__ . '/../config/helpers.php';
secureSessionStart();

ob_clean();
header('Content-Type: application/json; charset=utf-8');

error_log("AJAX SESSION: " . print_r($_SESSION, true));

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/ActivityLogger.php';

if (! isset($_POST['type_key']) || ! isset($_POST['is_enabled'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$typeKey   = $_POST['type_key'];
$isEnabled = (int) $_POST['is_enabled'];

$user         = User::loadFromSession();
$userId       = $user->id;
$userName     = $user->full_name;
$userTimezone = $user->timezone;
/*
$userId       = $_SESSION['user_id'] ?? null;
$userName     = $_SESSION['user_name'] ?? 'Unknown User';
$userTimezone = $_SESSION['user_timezone'] ?? 'UTC';
*/
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

    // Fetch old value + type_name
    $stmtOld = $pdo->prepare("SELECT type_name, is_enabled FROM zentra_module_types WHERE type_key = :key");
    $stmtOld->execute(['key' => $typeKey]);
    $oldRow = $stmtOld->fetch(PDO::FETCH_ASSOC);

    $oldValue = (int) $oldRow['is_enabled'];
    $typeName = $oldRow['type_name'];

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
    // Create logger instance
    $logger = new ActivityLogger($pdo);

    // Log activity (NO custom message)
    $logger->log(
        $userId,
        "Module Type Updated",
        "UPDATE",
        [
            'field_changed' => "App Config → {$typeName}",
            'old_value' => $oldValue,
            'new_value' => $isEnabled,
            'ip'        => $ip,
            'browser'   => $browser,
            'device'    => $device,
            'city'      => $geo['city'],
            'region'    => $geo['region'],
            'country'   => $geo['country'],
            'geo_raw'   => $geo['raw'],
        ]
    );

    echo json_encode([
        'success' => true,
        'status'  => $isEnabled == 1 ? 'Active' : 'Inactive',
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
