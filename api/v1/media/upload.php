<?php
// =========================
//  MEDIA UPLOAD ENDPOINT
// =========================

// Load config + classes
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../config/init.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../classes/User.php';
require_once __DIR__ . '/../../../classes/ModuleManager.php';
require_once __DIR__ . '/../../../classes/ActivityLogger.php';

// Start session
secureSessionStart();

$ip      = $_SESSION['user_ip'] ?? cleanIP(getClientIP());
$agent   = $_SESSION['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
$browser = getBrowserName($agent);
$device  = getDeviceType($agent);

$geo = $_SESSION['geo'] ?? [
    'city'    => null,
    'region'  => null,
    'country' => null,
    'postal'  => null,
    'raw'     => null,
];

$timezone = $_SESSION['user_timezone'] ?? 'UTC';

// -------------------------
//  AUTH CHECK
// -------------------------
$tenantId = $_SESSION['tenant_id'] ?? null;
$userId   = $_SESSION['user_id'] ?? null;

if (! $tenantId || ! $userId) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// -------------------------
//  VALIDATE FILE
// -------------------------
if (! isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];

$allowedTypes = ['image/png', 'image/jpeg'];
$maxSize      = 2 * 1024 * 1024; // 2 MB

if (! in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Only PNG or JPG allowed']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File exceeds 2 MB limit']);
    exit;
}

// -------------------------
//  BUILD STORAGE PATH
// -------------------------
$year  = date('Y');
$month = date('m');

$storageDir = __DIR__ . "/../../../media/{$tenantId}/{$year}/{$month}/";

if (! is_dir($storageDir)) {
    mkdir($storageDir, 0775, true);
}

// Generate safe filename
$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('media_', true) . '.' . strtolower($ext);

$fullPath = $storageDir . $filename;

// -------------------------
//  MOVE FILE
// -------------------------
if (! move_uploaded_file($file['tmp_name'], $fullPath)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

// Public URL
$publicUrl = "/media/{$tenantId}/{$year}/{$month}/{$filename}";

// -------------------------
//  SAVE TO DATABASE
// -------------------------
try {
    $stmt = $pdo->prepare("
        INSERT INTO zentra_media
        (tenant_id, file_name, file_path, file_type, file_size, uploaded_by, uploaded_on_utc)
        VALUES
        (:tenant_id, :file_name, :file_path, :file_type, :file_size, :uploaded_by, UTC_TIMESTAMP())
    ");

    $stmt->execute([
        ':tenant_id'   => $tenantId,
        ':file_name'   => $filename,
        ':file_path'   => $publicUrl,
        ':file_type'   => $file['type'],
        ':file_size'   => $file['size'],
        ':uploaded_by' => $userId,
    ]);

    $mediaId = $pdo->lastInsertId();

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    exit;
}

// -------------------------
//  ACTIVITY LOG
// -------------------------
try {
    $logger = new ActivityLogger($pdo, $tenantId);

    $logger->log(
        (string) $userId,
        "Media Uploaded with id: {$mediaId} and filename {$filename} at path {$publicUrl} and type {$file['type']} and size {$file['size']} bytes",
        (string) $mediaId,
        [
            'file_name' => $filename,
            'file_type' => $file['type'],
            'file_size' => $file['size'],
            'file_path' => $publicUrl,
            'user_name' => $_SESSION['user_name'] ?? null,
            'ip'        => cleanIP(getClientIP()),
            'browser'   => getBrowserName($_SESSION['user_agent'] ?? ''),
            'device'    => getDeviceType($_SESSION['user_agent'] ?? ''),
            'city'      => $_SESSION['geo']['city'] ?? null,
            'region'    => $_SESSION['geo']['region'] ?? null,
            'country'   => $_SESSION['geo']['country'] ?? null,
            'geo_raw'   => $_SESSION['geo']['raw'] ?? null,
        ]
    );

} catch (Throwable $e) {
    // Logging failure should not break upload
    error_log("Media upload logging failed: " . $e->getMessage());
}

// -------------------------
//  RESPONSE
// -------------------------
echo json_encode([
    'success'  => true,
    'media_id' => $mediaId,
    'url'      => $publicUrl,
]);

exit;
