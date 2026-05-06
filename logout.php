<?php
declare (strict_types = 1);

require_once __DIR__ . '/config/config.php'; // session already started here
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/ActivityLogger.php';
secureSessionStart();
// ------------------------------------------------------------
// Validate active session
// ------------------------------------------------------------
if (
    empty($_SESSION['user_id']) ||
    empty($_SESSION['user_email'])
) {
    header("Location: /login.php");
    exit;
}

$userId       = (int) $_SESSION['user_id'];
$userEmail    = (string) $_SESSION['user_email'];
$userFullName = (string) ($_SESSION['user_name'] ?? 'Unknown User');
$userTimezone = (string) ($_SESSION['user_timezone'] ?? 'UTC');
$tenantId     = (int) ($_SESSION['tenant_id'] ?? 0);

// ------------------------------------------------------------
// Capture IP, Browser, Device, Geo
// ------------------------------------------------------------
$ip      = cleanIP(getClientIP());
$ua      = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$browser = getBrowserName($ua);
$device  = getDeviceType($ua);

$geo = $_SESSION['geo'] ?? [
    'city'    => null,
    'region'  => null,
    'country' => null,
    'raw'     => null,
];
// ------------------------------------------------------------
// Log logout activity (SOC2 audit)
// ------------------------------------------------------------
try {
    $pdo = Database::getInstance();

    if ($tenantId > 0) {
        $logger = new ActivityLogger($pdo, $tenantId);

        $logger->log(
            $userId,
            "User {$userFullName} logged out",
            'Logout',
            [
                'user_name'     => $userFullName,
                'user_timezone' => $userTimezone,
                'ip'            => $ip,
                'browser'       => $browser,
                'device'        => $device,
                'city'          => $geo['city'],
                'region'        => $geo['region'],
                'country'       => $geo['country'],
                'geo_raw'       => $geo['raw'],
            ]
        );
    } else {
        error_log("Logout skipped: tenant_id missing for user {$userId}");
    }

} catch (Throwable $e) {
    error_log("Logout logging failed: " . $e->getMessage());
}

// ------------------------------------------------------------
// Destroy session securely
// ------------------------------------------------------------
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

// ------------------------------------------------------------
// Redirect to login
// ------------------------------------------------------------
header('Location: /login.php');
exit;
