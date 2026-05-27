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

        $identifier = "User {$userFullName} logged out";
        $logger->log(
            $userId,
            $identifier,
            'Logout',
            [
                'user_name'     => $userFullName,
                'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                'tenant_id'     => $tenantId,
                // Raw geo data (forensics)
                'geo_raw'       => $geo['raw'] ?? null,
                'ip'            => $ip,
                'browser'       => $browser,
                'device'        => $device,
                'city'          => $geo['city'],
                'region'        => $geo['region'],
                'country'       => $geo['country'],
                // Structured SOC2 JSON
                'audit_payload' => [
                    'event'    => [
                        'type'                => 'logout',
                        'identifier'          => $identifier,
                        'success'             => true, // or true on failure
                        'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                        'event_time_local'    => (new DateTime('now', new DateTimeZone($_SESSION['user_timezone'] ?? 'UTC')))->format('Y-m-d H:i:s'),
                        'event_user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                        'session_id'          => session_id(),
                        'ip'                  => $ip,
                    ],

                    'user'     => [
                        'user_id'    => $userId,
                        'username'   => $userEmail ?? null,
                        'first_name' => $user['first_name'] ?? null,
                        'tenant_id'  => $user['tenant_id'] ?? null,
                    ],

                    'location' => [
                        'city'     => $geo['city'] ?? null,
                        'region'   => $geo['region'] ?? null,
                        'country'  => $geo['country'] ?? null,
                        'timezone' => $geo['timezone'] ?? null,
                        'lat'      => $geo['latitude'] ?? null,
                        'lon'      => $geo['longitude'] ?? null,
                    ],

                    'network'  => [
                        'asn' => $geo['asn'] ?? null,
                        'isp' => $geo['isp'] ?? null,
                    ],

                    'security' => [
                        'vpn'     => $geo['vpn'] ?? null,
                        'proxy'   => $geo['proxy'] ?? null,
                        'tor'     => $geo['tor'] ?? null,
                        'hosting' => $geo['hosting'] ?? null, // datacenter
                        'mobile'  => $geo['mobile'] ?? null,
                        'carrier' => $geo['carrier'] ?? null,
                        'bot'     => $geo['bot'] ?? null,
                    ],

                    'device'   => [
                        'browser' => $browser,
                        'device'  => $device,
                    ],
                ],
            ]
        );

    } else {

        error_log("Logout skipped: tenant_id missing for user {$userId}");

        $identifier = "Logout skipped: tenant_id missing for user {$userId}";
        $logger->log(
            $userId,
            $identifier,
            'Logout',
            [
                'user_name'     => $userFullName,
                'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                'tenant_id'     => $tenantId,
                // Raw geo data (forensics)
                'geo_raw'       => $geo['raw'] ?? null,
                'ip'            => $ip,
                'browser'       => $browser,
                'device'        => $device,
                'city'          => $geo['city'],
                'region'        => $geo['region'],
                'country'       => $geo['country'],
                // Structured SOC2 JSON
                'audit_payload' => [
                    'event'    => [
                        'type'                => 'logout',
                        'identifier'          => $identifier,
                        'success'             => false, // or true on failure
                        'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                        'event_time_local'    => (new DateTime('now', new DateTimeZone($_SESSION['user_timezone'] ?? 'UTC')))->format('Y-m-d H:i:s'),
                        'event_user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                        'session_id'          => session_id(),
                        'ip'                  => $ip,
                    ],

                    'user'     => [
                        'user_id'    => $userId,
                        'username'   => $userEmail ?? null,
                        'first_name' => $user['first_name'] ?? null,
                        'tenant_id'  => $user['tenant_id'] ?? null,
                    ],

                    'location' => [
                        'city'     => $geo['city'] ?? null,
                        'region'   => $geo['region'] ?? null,
                        'country'  => $geo['country'] ?? null,
                        'timezone' => $geo['timezone'] ?? null,
                        'lat'      => $geo['latitude'] ?? null,
                        'lon'      => $geo['longitude'] ?? null,
                    ],

                    'network'  => [
                        'asn' => $geo['asn'] ?? null,
                        'isp' => $geo['isp'] ?? null,
                    ],

                    'security' => [
                        'vpn'     => $geo['vpn'] ?? null,
                        'proxy'   => $geo['proxy'] ?? null,
                        'tor'     => $geo['tor'] ?? null,
                        'hosting' => $geo['hosting'] ?? null, // datacenter
                        'mobile'  => $geo['mobile'] ?? null,
                        'carrier' => $geo['carrier'] ?? null,
                        'bot'     => $geo['bot'] ?? null,
                    ],

                    'device'   => [
                        'browser' => $browser,
                        'device'  => $device,
                    ],
                ],
            ]
        );
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
