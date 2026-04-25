<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
secureSessionStart();

$pdo = Database::getInstance();

$title   = $_POST['title'] ?? '';
$startDT = $_POST['start_dt'] ?? '';

if (! $title || ! $startDT) {
    echo "";
    exit;
}

// Slugify
$slug = strtolower(trim($title));
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
$stmt = $pdo->prepare("SELECT COUNT(*) FROM zentra_events WHERE event_slug = ? AND event_start_date_time = ?");
$stmt->execute([$slug, $startDT]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    $slug .= "-" . ($count + 1);
}

echo "http://mywebsite.com/events/$year/$month/$day/$slug";
