<?php
    // ==== CONFIG & DEPENDENCIES ====
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/init.php';
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/config/helpers.php';
    require_once __DIR__ . '/classes/User.php';
    $ip = getClientIP();
    secureSessionStart();
    enforceSessionSecurity();

    try {
    $pdo     = Database::getInstance();
    $userObj = new User($pdo);
    } catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
    }

    try {
    $stmt     = $pdo->query("SELECT * FROM zentra_system_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
    }

    // Check if the form was submitted
    //if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //}

    // Check if the form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize error and success message variables
    $error_message   = '';
    $success_message = '';

    // Loop through the settings and print them out
    echo '<h3>Submitted Settings:</h3>';
    foreach ($_POST['settings'] as $key => $value) {
        echo "Setting Key: $key <br>";
        echo "Setting Value: $value <br>";

        // Display the enabled status
        $is_enabled = isset($_POST['enabled'][$key]) ? $_POST['enabled'][$key] : 0;
        echo "Enabled: " . ($is_enabled == 1 ? "Checked" : "Unchecked") . "<br><br>";
    }

    // Display the submitted data for testing
    echo '<pre>';
    print_r($_POST); // Print the entire $_POST array
    echo '</pre>';
    }

?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo getenv('APP_NAME') ?> - App Configuration</title>
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
                    <!-- Start: top-nav-and-details -->
                    <div
                        class="d-flex flex-column justify-content-between flex-xl-row-reverse align-items-xl-start pt-3 mb-3 border-bottom">
                        <div class="d-flex align-items-center mb-3 mb-xl-0">
                            <form class="position-relative flex-grow-1 me-1"><input class="form-control pe-4"
                                    type="search" placeholder="Search" name="search"><button
                                    class="btn border-0 position-absolute top-50 end-0 translate-middle-y"
                                    type="submit"><i class="fa fa-search"></i></button></form>
                            <div class="dropdown"><button class="btn dropdown-toggle border-0 p-2"
                                    data-bs-toggle="dropdown" aria-expanded="false" type="button"><img
                                        class="object-fit-cover border rounded-circle"
                                        src="assets/img/team/avatar2.jpg?h=7086b181e9fb853914a2cca97301c640" width="32"
                                        height="32"></button>
                                <div class="dropdown-menu dropdown-menu-end shadow"><a class="dropdown-item" href="#"><i
                                            class="fa fa-user-o me-2"></i>&nbsp;Profile</a><a class="dropdown-item"
                                        href="#"><i class="fa fa-cog me-2"></i>&nbsp;Settings</a><a
                                        class="dropdown-item" href="#"><i class="fa fa-th-list me-2"></i>&nbsp;Activity
                                        log</a>
                                    <div class="dropdown-divider"></div><a class="dropdown-item link-danger" href="#"><i
                                            class="fa fa-sign-out me-2"></i>&nbsp;Logout</a>
                                </div>
                            </div>
                        </div>
                        <div>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#"><span>Home</span></a></li>
                                <li class="breadcrumb-item"><a href="#"><span>Admin Settings</span></a></li>
                                <li class="breadcrumb-item active"><span>System Settings</span></li>
                            </ol>
                            <h1 class="h2">System Settings</h1>
                        </div>
                    </div><!-- End: top-nav-and-details -->
                    <!-- Start: main content -->
                    <div>
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
                                            href="#">7 Days</a><a class="dropdown-item" href="#">30 Days</a>
                                        <div class="dropdown-divider"></div><a class="dropdown-item" href="#">Custom
                                            Period</a>
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
                                                    <th class="text-start">Activity</th>
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
                                                    <td><input type="text" class="form-control"
                                                            name="settings[<?php echo $row['setting_key']; ?>]"
                                                            value="<?php echo htmlspecialchars($row['setting_value']); ?> ">
                                                    </td>
                                                    <td>
                                                        <div class="form-check form-switch d-inline-flex ms-5 badge">
                                                            <input class="form-check-input form-check sai"
                                                                type="checkbox"
                                                                <?php if ($row['is_enabled'] == 1) {echo 'checked';}?>
                                                                id="check_<?php echo $row['setting_key']; ?>"
                                                                name="enabled[<?php echo $row['setting_key']; ?>]"
                                                                role="switch">
                                                        </div>
                                                    </td>
                                                    <td>Last updated on
                                                        <?php echo htmlspecialchars($row['updated_on']); ?> <br />by
                                                        <?php echo htmlspecialchars($row['updated_by']); ?>
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
    <?php include '_include/body_end_plugins.php'; ?>
</body>

</html>