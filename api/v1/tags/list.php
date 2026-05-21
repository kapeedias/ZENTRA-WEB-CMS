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

try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

// ---- FETCH ALL TAGS FOR TENANT ----
$stmt = $pdo->prepare("
    SELECT tag_id, tag_name, tag_slug
    FROM zentra_event_tags
    WHERE tenant_id = ?
    ORDER BY tag_name ASC
");

$stmt->execute([$tenantId]);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data'    => $tags,
]);
exit;
