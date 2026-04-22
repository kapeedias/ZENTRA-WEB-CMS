<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

if (! isset($_POST['type_key']) || ! isset($_POST['is_enabled'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$typeKey   = $_POST['type_key'];
$isEnabled = (int) $_POST['is_enabled'];

try {
    $pdo  = Database::getInstance();
    $stmt = $pdo->prepare("UPDATE zentra_module_types SET is_enabled = :enabled WHERE type_key = :key");
    $stmt->execute([
        'enabled' => $isEnabled,
        'key'     => $typeKey,
    ]);

    echo json_encode([
        'success' => true,
        'status'  => $isEnabled == 1 ? 'Active' : 'Inactive',
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
