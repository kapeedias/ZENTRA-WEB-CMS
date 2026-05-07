<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
secureSessionStart();

ob_clean();
header('Content-Type: application/json; charset=utf-8');

//error_log("AJAX SESSION: " . print_r($_SESSION, true));

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/ActivityLogger.php';

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

// Connect to DB
try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// Slugify
$slug = strtolower($title);
$slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
$slug = trim($slug, '-');

// Split datetime-local
list($date, $time) = explode('T', $startDT);

// Build date parts
$ts    = strtotime($date);
$year  = date("Y", $ts);
$month = date("m", $ts);
$day   = date("d", $ts);

// Duplicate check
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM zentra_events
    WHERE event_slug = ?
      AND event_start_date = ?
");
$stmt->execute([$slug, $startDT]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    $slug .= "-" . ($count + 1);
}

$url = "http://mywebsite.com/events/$year/$month/$day/$slug";

echo json_encode([
    'success' => true,
    'url'     => $url,
]);
