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
secureSessionStart();

header('Content-Type: application/json');
// 1️⃣ Tenant ID (from session or config)
$tenant_id = $_SESSION['tenant_id'] ?? null;
if (! $tenant_id) {
    echo json_encode([]);
    exit;
}
// 2️⃣ Inputs
$search = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$page   = max(1, intval($_GET['page'] ?? 1));
$limit  = 40;
$offset = ($page - 1) * $limit;

// 3️⃣ Base WHERE clause (SOC2-compliant)
$where = "WHERE (tenant_id = :tenant_id OR is_global = 1)";

// 4️⃣ Filter by file type
if ($filter === "images") {
    $where .= " AND file_type LIKE 'image/%'";
} elseif ($filter === "documents") {
    $where .= " AND file_type IN (
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    )";
} elseif ($filter === "videos") {
    $where .= " AND file_type LIKE 'video/%'";
}

// 5️⃣ Search by filename or tags
if ($search !== '') {
    $where .= " AND (
        file_name LIKE :search
        OR manual_tags LIKE :search
        OR auto_tags LIKE :search
    )";
}

// 6️⃣ Final SQL
$sql = "
    SELECT
        library_id AS id,
        file_name,
        file_extension,
        file_url AS url,
        file_type,
        file_size,
        uploaded_at,
        uploaded_by,
        uploaded_from
    FROM zentra_library
    $where
    ORDER BY uploaded_at DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);

// Bind required params
$stmt->bindValue(':tenant_id', $tenant_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// Bind search if needed
if ($search !== '') {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}

$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 7️⃣ Output JSON
echo json_encode($files);
exit;
