<?php
declare (strict_types = 1);

// ==== CONFIG FIRST (order matters) ====
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/helpers.php';
require_once __DIR__ . '/../../../config/init.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../classes/User.php';
require_once __DIR__ . '/../../../classes/MenuManager.php';
require_once __DIR__ . '/../../../_include/nav_renderer.php';
require_once __DIR__ . '/../../../classes/ModuleManager.php';
require_once __DIR__ . '/../../../classes/EventsModule.php';
require_once __DIR__ . '/../../../classes/ActivityLogger.php';

try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

header('Content-Type: application/json');

$tenantId = $_SESSION['tenant_id'] ?? null;
$q        = trim($_GET['q'] ?? '');

if (! $tenantId) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT tag_id, tag_name, tag_slug
    FROM zentra_event_tags
    WHERE tenant_id = ?
      AND tag_name LIKE ?
    ORDER BY tag_name ASC
    LIMIT 20
");

$stmt->execute([$tenantId, "%$q%"]);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($tags);
exit;
