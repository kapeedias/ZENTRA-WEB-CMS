<?php

    require_once __DIR__ . '/config/helpers.php';
    secureSessionStart();

    // ==== CONFIG & DEPENDENCIES ====
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/init.php';
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/classes/User.php';
    require_once __DIR__ . '/classes/MenuManager.php';
    require_once __DIR__ . '/_include/nav_renderer.php';
    require_once __DIR__ . '/classes/ModuleManager.php';
    require_once __DIR__ . '/classes/ActivityLogger.php';

    enforceSessionSecurity();
    $ip = getClientIP();

    $errors  = [];
    $success = [];

    try {
    $pdo     = Database::getInstance();
    $userObj = new User($pdo);
    } catch (Throwable $e) {
    $errors[] = "Database connection failed: " . $e->getMessage();
    }

    $moduleManager = new ModuleManager($pdo); // ← REQUIRED

    // ==== HANDLE POST (UPDATE SETTINGS) ====
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {

    $tenantId = (int) ($_SESSION['tenant_id'] ?? 0);
    $userId   = (int) ($_SESSION['user_id'] ?? 0);
    $geo      = $_SESSION['geo'] ?? [];
    $browser  = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $device   = $_SESSION['device'] ?? null;
    $userTz   = $_SESSION['user_timezone'] ?? 'UTC';

    foreach ($_POST['settings'] as $setting_key => $setting_value) {

        // Sanitize setting_key (ensure it's alphanumeric and underscores or dashes only)
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $setting_key)) {
            $errors[] = "Invalid setting key: $setting_key<br>";
            continue;
        }

        // Trim and sanitize setting_value
        $setting_value = trim($setting_value);
        $setting_value = htmlspecialchars($setting_value, ENT_QUOTES, 'UTF-8');

        // Check if the setting is enabled (checkbox is checked)
        $is_enabled = isset($_POST['enabled'][$setting_key]) ? 1 : 0;

        try {
            // Fetch old value BEFORE update for audit
            $stmtOld = $pdo->prepare("
                    SELECT setting_value, is_enabled
                    FROM zentra_system_settings
                    WHERE setting_key = :setting_key
                ");
            $stmtOld->execute([':setting_key' => $setting_key]);
            $oldRow = $stmtOld->fetch(PDO::FETCH_ASSOC) ?: ['setting_value' => null, 'is_enabled' => null];

            // Update setting
            $stmt = $pdo->prepare("
                    UPDATE `zentra_system_settings`
                    SET
                        `setting_value` = :setting_value,
                        `is_enabled`    = :is_enabled,
                        `updated_by`    = :updated_by
                    WHERE
                        `setting_key`   = :setting_key
                ");

            $stmt->execute([
                ':setting_value' => $setting_value,
                ':is_enabled'    => $is_enabled,
                ':updated_by'    => $userId,
                ':setting_key'   => $setting_key,
            ]);

            // SOC2 AUDIT LOG
            $logger = new ActivityLogger($pdo, $tenantId);

            $logger->log(
                $userId,
                $setting_key,
                'System Setting Updated',
                [
                    'user_name'     => $_SESSION['first_name'] ?? null,
                    'user_timezone' => $userTz,
                    'tenant_id'     => $tenantId,

                    // Raw geo data (forensics)
                    'geo_raw'       => $geo['raw'] ?? null,
                    'ip'            => $ip,
                    'browser'       => $browser,
                    'device'        => $device,
                    'city'          => $geo['city'] ?? null,
                    'region'        => $geo['region'] ?? null,
                    'country'       => $geo['country'] ?? null,

                    // Structured SOC2 JSON
                    'audit_payload' => [
                        'event'    => [
                            'type'                => 'system_setting_update',
                            'identifier'          => $setting_key,
                            'success'             => true,
                            'event_time_utc'      => gmdate('Y-m-d H:i:s'),
                            'event_time_local'    => (new DateTime('now', new DateTimeZone($userTz)))->format('Y-m-d H:i:s'),
                            'event_user_timezone' => $userTz,
                            'session_id'          => session_id(),
                            'ip'                  => $ip,
                        ],

                        'user'     => [
                            'user_id'    => $userId,
                            'username'   => $_SESSION['email'] ?? null,
                            'first_name' => $_SESSION['first_name'] ?? null,
                            'tenant_id'  => $tenantId,
                        ],

                        'change'   => [
                            'setting_key' => $setting_key,
                            'old_value'   => $oldRow['setting_value'],
                            'new_value'   => $setting_value,
                            'old_enabled' => (int) ($oldRow['is_enabled'] ?? 0),
                            'new_enabled' => $is_enabled,
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
                            'hosting' => $geo['hosting'] ?? null,
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

            $success[] = "Setting '$setting_key' updated successfully.<br>";

        } catch (Exception $e) {
            $errors[] = "Error updating setting '$setting_key': " . $e->getMessage() . "<br>";
        }
    }

    if (empty($errors)) {
        header("Location: system-settings.php");
        exit;
    }
    }

    // ==== LOAD SETTINGS FOR VIEW ====
    try {
    $stmt     = $pdo->query("SELECT * FROM zentra_system_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
    $errors[] = "Database query failed: " . $e->getMessage();
    $settings = [];
    }

    $pageTitle   = "System Settings";
    $breadcrumbs = [
    ['label' => 'Home', 'url' => '/myaccount.php'],
    ['label' => 'Admin Settings', 'url' => '#'],
    ['label' => 'System Settings', 'url' => "#"],
    ];
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo getenv('APP_NAME') ?> - System Settings</title>
    <?php include '_include/head.php'; ?>
</head>

<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <?php include '_include/nav_side.php'; ?>
            <div class="col-md-9 col-xl-10 bg-body-tertiary px-0">
                <div class="d-md-none p-2 sticky-top">
                    <?php include '_include/nav_top_branding.php'; ?>
                </div>
                <main class="px-3 px-md-4">
                    <?php include '_include/nav_top.php'; ?>
                    <!-- Start: main content -->
                    <div>
                        <!-- Start: alert -->
                        <?php if (! empty($success)): ?>
                        <div class="w-100 alert-success shadow">
                            <?php echo implode('<br>', $success) ?>
                        </div>
                        <?php endif; ?>

                        <?php if (! empty($errors)): ?>
                        <div class="w-100 alert-error shadow">
                            <?php echo implode('<br>', $errors) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Start: Table Card -->
                        <div class="card">
                            <div
                                class="card-header d-flex justify-content-between align-items-center flex-wrap flex-xl-nowrap pb-0 py-3">
                                <h5 class="fw-bold w-100 mb-3 mb-xl-0">System Settings</h5>
                                <form class="position-relative flex-grow-1 flex-shrink-0 flex-xl-grow-0 ms-auto"><input
                                        class="form-control pe-4" type="search" placeholder="Search System Settings"
                                        name="searchAppConfig" autofocus="" autocomplete="off"><button
                                        class="btn border-0 position-absolute top-50 end-0 translate-middle-y"
                                        type="submit"><i class="fa fa-search"></i></button></form>
                                <div class="dropdown"><button class="btn btn-link" data-bs-toggle="dropdown"
                                        aria-expanded="false" type="button"><i class="fa fa-ellipsis-v"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end shadow"><a class="dropdown-item"
                                            href="#">Refresh</a>
                                        <div class="dropdown-divider"></div><a class="dropdown-item" href="#">Export
                                            View to CSV</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <form id="settingsForm" method="POST" action="">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="AppConfigData" data-search="true">
                                            <thead>
                                                <tr>
                                                    <th class="text-end me-3">Setting</th>
                                                    <th class=" text-center">Value</th>
                                                    <th class="text-start">Enabled</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($settings as $row) {?>
                                                <tr valign="middle">
                                                    <td class="text-end pe-3">
                                                        <div>
                                                            <p class="mb-0"><?php echo $row['setting_key']; ?></p>
                                                        </div>
                                                    </td>
                                                    <td><input type="text" class="form-control text-warning"
                                                            name="settings[<?php echo $row['setting_key']; ?>]"
                                                            value="<?php echo $row['setting_value']; ?>">
                                                    </td>
                                                    <td>
                                                        <div class="form-check form-switch d-inline-flex ms-5 badge">
                                                            <input class="form-check-input form-check sai"
                                                                type="checkbox"
                                                                <?php echo($row['is_enabled'] == 1) ? 'checked' : ''; ?>
                                                                id="check_<?php echo $row['setting_key']; ?>"
                                                                name="enabled[<?php echo $row['setting_key']; ?>]"
                                                                role="switch">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php }?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-center save-btn-holder"><button
                                            class="btn btn-primary btn-sm align-self-end" type="submit">Save
                                            Settings</button></div>
                                </form>
                            </div>
                        </div><!-- End: Table Card -->
                    </div><!-- End: main content -->
                    <!-- Start: Footer Centered -->
                    <?php include '_include/inner-footer.php'; ?>
                    <!-- End: Footer Centered -->
                </main>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {

        let table = $('#AppConfigData').DataTable({
            paging: true,
            ordering: true,
            info: true,
            searching: true,
            dom: 'lrtip', // removes the default search box from DOM
            lengthChange: false,
            pageLength: 25,
            language: {
                search: "",
                searchPlaceholder: "Search settings..."
            }
        });

        // Bind your custom search box
        $('input[name="searchAppConfig"]').on('keyup', function() {
            table.search(this.value).draw();
        });

    });
    </script>

    <?php include '_include/body_end_plugins.php'; ?>
</body>

</html>