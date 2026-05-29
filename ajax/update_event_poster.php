<?php
require_once '../config/config.php';
require_once '../config/db.php';
require_once '../config/helpers.php';
require_once '../classes/EventsModule.php';

secureSessionStart();
enforceSessionSecurity();

$input = json_decode(file_get_contents("php://input"), true);

$eventId   = (int) $input['event_id'];
$libraryId = (int) $input['library_id'];
$tenantId  = (int) $_SESSION['tenant_id'] ?? 0;

$pdo    = Database::getInstance();
$logger = new ActivityLogger($pdo, $tenantId);
$events = new EventsModule($pdo, $tenantId, $logger);

$success = $events->updateEventPoster($eventId, $libraryId, $tenantId);

echo json_encode(['success' => $success]);