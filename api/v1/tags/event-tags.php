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

header('Content-Type: application/json');

// ---- AUTH CHECK ----
$tenantId = $_SESSION['tenant_id'] ?? null;

if (! $tenantId) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$eventId = intval($_GET['event_id'] ?? 0);

if ($eventId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid event ID']);
    exit;
}

try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// ---- FETCH TAGS FOR THIS EVENT ----
$stmt = $pdo->prepare("
    SELECT t.tag_id, t.tag_name, t.tag_slug
    FROM zentra_event_tags t
    INNER JOIN zentra_event_tag_map m ON m.tag_id = t.tag_id
    WHERE t.tenant_id = ?
      AND m.event_id = ?
    ORDER BY t.tag_name ASC
");

$stmt->execute([$tenantId, $eventId]);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($tags);
exit;
