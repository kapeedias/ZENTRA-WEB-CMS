<?php
// Database connection

<  ? php;

// ==== CONFIG FIRST (order matters) ====
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/helpers.php';
secureSessionStart();

require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/config/db.php';

try {
    $pdo = Database::getInstance();
} catch (PDOException $e) {
    $error[] = "Database connection failed: " . $e->getMessage();
    return;
}

// Load JSON from the Font Awesome 4.7 gist
$json = file_get_contents('https://gist.githubusercontent.com/khanzadimahdi/5f0e9327f1c7abe551f043b3f9259d63/raw/57dcf7abdf5d29268614887ecbb13e1e35236965/fontwawesome-icons-4.7.json');
$data = json_decode($json, true);

// Prepare insert statement
$stmt = $pdo->prepare("
    INSERT INTO zentra_icons (icon_class, icon_name, fa_version, category, is_enabled, created_at)
    VALUES (:icon_class, :icon_name, :fa_version, :category, :is_enabled, CURRENT_TIMESTAMP)
");

// Loop through icons
foreach ($data['icons'] as $icon) {
    // Extract icon name (everything after 'fa fa-')
    $iconName = str_replace('fa fa-', '', $icon);

    $stmt->execute([
        ':icon_class' => $icon,
        ':icon_name'  => $iconName,
        ':fa_version' => '4.7',
        ':category'   => null, // optional, can be set later
        ':is_enabled' => 1,
    ]);
}

echo "✅ Icons inserted successfully!";
