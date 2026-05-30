<?php
// =========================
//  MEDIA UPLOAD ENDPOINT
// =========================

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';
secureSessionStart();

require_once __DIR__ . '/../../../config/init.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../classes/User.php';
require_once __DIR__ . '/../../../classes/ModuleManager.php';
require_once __DIR__ . '/../../../classes/ActivityLogger.php';

enforceSessionSecurity();

// -------------------------
//  CONTEXT + SESSION
// -------------------------
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

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload error code: ' . $file['error']]);
    exit;
}

if ($file['size'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'Empty file']);
    exit;
}

// -------------------------
//  ALLOWED TYPES + SIZE
// -------------------------
$allowedTypes = [
    // Images
    'image/png',
    'image/jpeg',
    'image/webp',

    // Documents
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain',

    // Videos
    'video/mp4',
    'video/quicktime',
];

$maxSize = 50 * 1024 * 1024; // 50 MB

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File exceeds 50 MB limit']);
    exit;
}

// -------------------------
//  REAL MIME DETECTION
// -------------------------
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (! in_array($mime, $allowedTypes, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid or unsupported file type']);
    exit;
}

// -------------------------
//  CLAMAV MALWARE SCAN
// -------------------------
$clamCommand = 'clamscan --stdout --no-summary ' . escapeshellarg($file['tmp_name']);
$clamOutput  = [];
$clamReturn  = 0;
@exec($clamCommand, $clamOutput, $clamReturn);

// 0 = clean, 1 = infected, 2 = error
if ($clamReturn === 1) {

    // -------------------------
    //  SOC2 LOG: MALWARE BLOCKED
    // -------------------------
    try {
        $logger = new ActivityLogger($pdo, (int) $tenantId);

        $logger->log(
            (string) $userId,
            "Media Upload Blocked",
            "Media Upload Blocked - Malware",
            [
                'user_name'     => $_SESSION['user_name'] ?? null,
                'user_timezone' => $timezone,
                'tenant_id'     => $tenantId,

                'geo_raw'       => $geo['raw'] ?? null,
                'ip'            => $ip,
                'browser'       => $browser,
                'device'        => $device,
                'city'          => $geo['city'] ?? null,
                'region'        => $geo['region'] ?? null,
                'country'       => $geo['country'] ?? null,

                'audit_payload' => [
                    'event'    => [
                        'type'                => 'media_upload',
                        'success'             => false,
                        'reason'              => 'malware_detected',
                        'file_name'           => $file['name'],
                        'file_type'           => $mime,
                        'file_size'           => $file['size'],
                        'clam_output'         => implode("\n", $clamOutput),
                        'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                        'event_time_local'    => (new DateTime('now', new DateTimeZone($timezone)))->format('Y-m-d H:i:s'),
                        'event_user_timezone' => $timezone,
                        'session_id'          => session_id(),
                        'ip'                  => $ip,
                    ],

                    'user'     => [
                        'user_id'   => $userId,
                        'username'  => $_SESSION['user_name'] ?? null,
                        'tenant_id' => $tenantId,
                    ],

                    'location' => [
                        'city'     => $geo['city'] ?? null,
                        'region'   => $geo['region'] ?? null,
                        'country'  => $geo['country'] ?? null,
                        'timezone' => $timezone,
                        'lat'      => $geo['latitude'] ?? null,
                        'lon'      => $geo['longitude'] ?? null,
                    ],

                    'network'  => [
                        'asn' => $geo['asn'] ?? null,
                        'isp' => $geo['isp'] ?? null,
                    ],

                    'security' => [
                        'vpn'     => $geo['vpn'] ?? null,
                        'proxy'   => $geo['proxy'] ?? null,
                        'tor'     => $geo['tor'] ?? null,
                        'hosting' => $geo['hosting'] ?? null,
                        'mobile'  => $geo['mobile'] ?? null,
                        'carrier' => $geo['carrier'] ?? null,
                        'bot'     => $geo['bot'] ?? null,
                    ],

                    'device'   => [
                        'browser' => $browser,
                        'device'  => $device,
                    ],
                ],
            ]
        );
    } catch (Throwable $e) {
        error_log("Malware logging failed: " . $e->getMessage());
    }

    echo json_encode(['success' => false, 'error' => 'Malicious file detected']);
    exit;
}

if ($clamReturn === 2) {
    echo json_encode(['success' => false, 'error' => 'File scan error']);
    exit;
}

// -------------------------
//  EXTENSION MAPPING
// -------------------------
$ext = match ($mime) {
    'image/png'                                                               => 'png',
    'image/jpeg'                                                              => 'jpg',
    'image/webp'                                                              => 'webp',
    'application/pdf'                                                         => 'pdf',
    'application/msword'                                                      => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'text/plain'                                                              => 'txt',
    'video/mp4'                                                               => 'mp4',
    'video/quicktime'                                                         => 'mov',
    default                                                                   => strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)),
};

// -------------------------
//  STORAGE PATH
// -------------------------
$year  = date('Y');
$month = date('m');

$storageDir = __DIR__ . "/../../../media/{$tenantId}/{$year}/{$month}/";

if (! is_dir($storageDir)) {
    mkdir($storageDir, 0775, true);
}

$filename = uuidv4() . "." . $ext;
$fullPath = $storageDir . $filename;

if (! move_uploaded_file($file['tmp_name'], $fullPath)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

$publicUrl = "/media/{$tenantId}/{$year}/{$month}/{$filename}";

// -------------------------
//  SOURCE CONTEXT
// -------------------------
$source = $_POST['source'] ?? 'media-library';

// -------------------------
//  SAVE TO DATABASE
// -------------------------
try {
    $stmt = $pdo->prepare("
        INSERT INTO zentra_library
        (tenant_id, file_name, file_extension, file_url, file_type, file_size, uploaded_by, uploaded_from, uploaded_at)
        VALUES
        (:tenant_id, :file_name, :file_extension, :file_url, :file_type, :file_size, :uploaded_by, :uploaded_from, UTC_TIMESTAMP())
    ");

    $stmt->execute([
        ':tenant_id'      => $tenantId,
        ':file_name'      => $filename,
        ':file_extension' => $ext,
        ':file_url'       => $publicUrl,
        ':file_type'      => $mime,
        ':file_size'      => $file['size'],
        ':uploaded_by'    => $userId,
        ':uploaded_from'  => $source,
    ]);

    $mediaId = $pdo->lastInsertId();

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    exit;
}

// -------------------------
//  SOC2 LOG: SUCCESSFUL UPLOAD
// -------------------------
try {
    $logger = new ActivityLogger($pdo, (int) $tenantId);

    $logger->log(
        (string) $userId,
        "Media Upload",
        "Media Uploaded",
        [
            'user_name'     => $_SESSION['user_name'] ?? null,
            'user_timezone' => $timezone,
            'tenant_id'     => $tenantId,

            'geo_raw'       => $geo['raw'] ?? null,
            'ip'            => $ip,
            'browser'       => $browser,
            'device'        => $device,
            'city'          => $geo['city'] ?? null,
            'region'        => $geo['region'] ?? null,
            'country'       => $geo['country'] ?? null,

            'audit_payload' => [
                'event'    => [
                    'type'                => 'media_upload',
                    'success'             => true,
                    'media_id'            => $mediaId,
                    'file_name'           => $filename,
                    'file_type'           => $mime,
                    'file_size'           => $file['size'],
                    'file_url'            => $publicUrl,
                    'uploaded_from'       => $source,
                    'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                    'event_time_local'    => (new DateTime('now', new DateTimeZone($timezone)))->format('Y-m-d H:i:s'),
                    'event_user_timezone' => $timezone,
                    'session_id'          => session_id(),
                    'ip'                  => $ip,
                ],

                'user'     => [
                    'user_id'   => $userId,
                    'username'  => $_SESSION['user_name'] ?? null,
                    'tenant_id' => $tenantId,
                ],

                'location' => [
                    'city'     => $geo['city'] ?? null,
                    'region'   => $geo['region'] ?? null,
                    'country'  => $geo['country'] ?? null,
                    'timezone' => $timezone,
                    'lat'      => $geo['latitude'] ?? null,
                    'lon'      => $geo['longitude'] ?? null,
                ],

                'network'  => [
                    'asn' => $geo['asn'] ?? null,
                    'isp' => $geo['isp'] ?? null,
                ],

                'security' => [
                    'vpn'     => $geo['vpn'] ?? null,
                    'proxy'   => $geo['proxy'] ?? null,
                    'tor'     => $geo['tor'] ?? null,
                    'hosting' => $geo['hosting'] ?? null,
                    'mobile'  => $geo['mobile'] ?? null,
                    'carrier' => $geo['carrier'] ?? null,
                    'bot'     => $geo['bot'] ?? null,
                ],

                'device'   => [
                    'browser' => $browser,
                    'device'  => $device,
                ],
            ],
        ]
    );

} catch (Throwable $e) {
    error_log("Media upload logging failed: " . $e->getMessage());
}

// -------------------------
//  RESPONSE
// -------------------------
echo json_encode([
    'success'     => true,
    'media_id'    => $mediaId,
    'url'         => $publicUrl,
    'file_name'   => $filename,
    'file_type'   => $mime,
    'file_size'   => $file['size'],
    'uploaded_at' => date('c'),
]);

exit;
