<?php
declare (strict_types = 1);

// ==== CONFIG FIRST (order matters) ====
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../config/init.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../classes/User.php';
require_once __DIR__ . '/../../../classes/ModuleManager.php';
require_once __DIR__ . '/../../../classes/ActivityLogger.php';
secureSessionStart();

header('Content-Type: application/json');

// ---- AUTH CHECK ----
$tenantId = $_SESSION['tenant_id'] ?? null;
$userId   = $_SESSION['user_id'] ?? null;

if (! $tenantId || ! $userId) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// ---- READ JSON INPUT ----
$input = json_decode(file_get_contents('php://input'), true);

$name = trim($input['name'] ?? '');
$slug = trim($input['slug'] ?? '');

if ($name === '' || $slug === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// ---- DB CONNECTION ----
try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}
$ip      = cleanIP(getClientIP());
$agent   = getUserAgent();
$browser = getBrowserName($agent);
$device  = getDeviceType($agent);
$geo     = getGeoLocation($ip);

$_SESSION['geo'] = [
    'city'    => $geo['city'],
    'region'  => $geo['region'],
    'country' => $geo['country'],
    'postal'  => $geo['postal'],
    'raw'     => $geo['raw'],
];
$_SESSION['user_ip']       = $ip;
$_SESSION['user_agent']    = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$_SESSION['user_timezone'] = $geo['timezone'];

// ---- CHECK IF TAG ALREADY EXISTS ----
$stmt = $pdo->prepare("
    SELECT tag_id
    FROM zentra_event_tags
    WHERE tenant_id = ? AND tag_slug = ?
    LIMIT 1
");
$stmt->execute([$tenantId, $slug]);
$existingId = $stmt->fetchColumn();

if ($existingId) {
    echo json_encode([
        'success'  => true,
        'tag_id'   => (int) $existingId,
        'existing' => true,
    ]);
    exit;
}

// ---- INSERT NEW TAG ----
$stmt = $pdo->prepare("
    INSERT INTO zentra_event_tags (tenant_id, tag_name, tag_slug, created_by)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$tenantId, $name, $slug, $userId]);

$newTagId = (int) $pdo->lastInsertId();

// ---- LOG ACTIVITY (INSTANCE-BASED, TYPE-SAFE) ----
$logger = new ActivityLogger($pdo, $tenantId);

$logger->log(
    (int) $userId,
    "Created new Tag with id: {$newTagId} with slug {$slug} and name {$name}",
    "Tag Created",
    [
        'tag_name'      => $name,
        'tag_slug'      => $slug,
        'user_name'     => $_SESSION['user_name'] ?? null,
        'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
        'tenant_id'     => (string) $tenantId,
        'ip'            => $ip,
        'browser'       => $browser,
        'device'        => $device,
        'city'          => $geo['city'] ?? null,
        'region'        => $geo['region'] ?? null,
        'country'       => $geo['country'] ?? null,
        'geo_raw'       => $geo['raw'] ?? null,
    ]
);

// ---- RESPONSE ----
echo json_encode([
    'success'  => true,
    'tag_id'   => $newTagId,
    'existing' => false,
]);
exit;
