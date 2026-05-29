<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
secureSessionStart();

ob_clean();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/ActivityLogger.php';
require_once __DIR__ . '/../classes/EventsModule.php';

// Validate request
if (! isset($_POST['title']) || ! isset($_POST['start_dt'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$title   = trim($_POST['title']);
$startDT = trim($_POST['start_dt']);

if ($title === '' || $startDT === '') {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

// Split datetime-local
list($date, $time) = explode('T', $startDT);

// Connect to DB
try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// Init logger + events module
$tenantId = $_SESSION['tenant_id'] ?? 0;
$logger   = new ActivityLogger($pdo, $tenantId);
$events   = new EventsModule($pdo, $tenantId, $logger);

// Generate slug using unified backend logic
$slug = $events->generateSlug($title, $date);

// Build preview URL (same as getEventUrl())
$baseUrl = rtrim(getenv('APP_URL') ?: 'https://mywebsite.com', '/');

$year  = date("Y", strtotime($date));
$month = date("m", strtotime($date));
$day   = date("d", strtotime($date));

$url = "{$baseUrl}/events/{$year}/{$month}/{$day}/{$slug}";

echo json_encode([
    'success' => true,
    'slug'    => $slug,
    'url'     => $url,
]);