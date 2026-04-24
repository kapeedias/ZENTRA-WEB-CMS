<?php
require_once __DIR__ . '/../config/db.php'; // adjust path as needed

header('Content-Type: application/json');

$slug      = $_POST['slug'] ?? '';
$startDate = $_POST['start_date'] ?? '';
$startTime = $_POST['start_time'] ?? null;

// Basic validation
if (! $slug || ! $startDate) {
    echo json_encode(['exists' => false]);
    exit;
}

/*
    We check for duplicates based on your composite uniqueness rule:

    UNIQUE(event_slug, event_start_date, event_start_time)

    If start_time is NULL (all-day event), we must check:
        event_start_time IS NULL
*/
$sql = "
    SELECT COUNT(*) AS cnt
    FROM zentra_events
    WHERE event_slug = :slug
      AND event_start_date = :start_date
      AND (
            (event_start_time = :start_time)
            OR (event_start_time IS NULL AND :start_time IS NULL)
          )
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':slug'       => $slug,
    ':start_date' => $startDate,
    ':start_time' => $startTime,
]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'exists' => ($row['cnt'] > 0),
]);
