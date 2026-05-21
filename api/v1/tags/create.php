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

// ---- AUTH CHECK ----
$tenantId = $_SESSION['tenant_id'] ?? null;

if (! $tenantId) {
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

try {
    $pdo = Database::getInstance();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

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
        'tag_id'   => $existingId,
        'existing' => true,
    ]);
    exit;
}

// ---- INSERT NEW TAG ----
$stmt = $pdo->prepare("
    INSERT INTO zentra_event_tags (tenant_id, tag_name, tag_slug)
    VALUES (?, ?, ?)
");
$stmt->execute([$tenantId, $name, $slug]);

$newTagId = (int) $pdo->lastInsertId();

// ---- OPTIONAL: LOG ACTIVITY ----
ActivityLogger::log(
    $tenantId,
    $_SESSION['user_id'] ?? null,
    "Created new tag: {$name}"
);

echo json_encode([
    'success'  => true,
    'tag_id'   => $newTagId,
    'existing' => false,
]);
exit;
