<?php

    // ==== CONFIG FIRST (order matters) ====
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/helpers.php';
    secureSessionStart();

    require_once __DIR__ . '/config/init.php';
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/classes/User.php';

    // ==== SESSION SECURITY ====
    enforceSessionSecurity();

    // ==== GET CLIENT IP ====
    $ip = getClientIP();

    // ==== DB CONNECTION ====
    try {
    $pdo = Database::getInstance();
    } catch (PDOException $e) {
    $error[] = "Database connection failed: " . $e->getMessage();
    return;
    }

    // ==== LOAD MODULE TYPES (cached) ====
    if (! isset($_SESSION['module_types_cache'])) {
    try {
        $stmt                           = $pdo->query("SELECT * FROM zentra_module_types");
        $_SESSION['module_types_cache'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error[] = "Database query failed: " . $e->getMessage();
    }
    }

    $settings = $_SESSION['module_types_cache'];
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
                    <?php include '_include/nav_top.php'; ?>
                    <!-- Start: main content -->
                    <div>
                        <!-- Start: Table Card -->
                        <div class="card">
                            <div
                                class="card-header d-flex justify-content-between align-items-center flex-wrap flex-xl-nowrap pb-0 py-3">
                                <h5 class="fw-bold w-100 mb-3 mb-xl-0">App Config</h5>
                                <form class="position-relative flex-grow-1 flex-shrink-0 flex-xl-grow-0 ms-auto">
                                    <input class="form-control pe-4" type="search" placeholder="Search"
                                        name="searchAppConfig">
                                    <button class="btn border-0 position-absolute top-50 end-0 translate-middle-y"
                                        type="submit"><i class="fa fa-search"></i></button>
                                </form>
                                <div class="dropdown"><button class="btn btn-link" data-bs-toggle="dropdown"
                                        aria-expanded="false" type="button"><i class="fa fa-ellipsis-v"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end shadow">
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                            data-bs-target="#addNewConfigModal"> Add New Config</a>


                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#">Export into CSV</a>
                                    </div>
                                </div>
                            </div>
                            <!-- Begin addNewConfigModal  -->
                            <div class="modal fade" id="addNewConfigModal" tabindex="-1" data-bs-backdrop="static"
                                data-bs-keyboard="false" aria-labelledby="addNewConfigModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h1 class="modal-title fs-5" id="addNewConfigModalLabel">
                                                Add New Config</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="addConfigForm" method="post">
                                                <div class="mb-3">
                                                    <label for="configKey" class="form-label">Config Key</label>
                                                    <input type="text" class="form-control" id="configKey"
                                                        name="configKey" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="configValue" class="form-label">Config Value</label>
                                                    <input type="text" class="form-control" id="configValue"
                                                        name="configValue" required>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-bs-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary">Save
                                                changes</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- End addNewConfigModal  -->
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="AppConfigData">
                                        <thead>
                                            <tr class="bg-secondary">
                                                <th>Name</th>
                                                <th>Status</th>
                                                <th>Lifetime Value</th>
                                                <th>Join Date</th>
                                                <th width="40px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (! empty($settings)): ?>
                                            <?php foreach ($settings as $row): ?>
                                            <tr valign="middle">
                                                <td>

                                                    <a class="text-decoration-none d-flex align-items-center gap-2"
                                                        href="#">
                                                        <div>
                                                            <p class="fw-bold mb-0"><?php echo $row['type_name']; ?></p>
                                                            <small class="text-secondary d-block">Marketing
                                                                Manager</small>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td><span class="badge bg-light d-inline-flex gap-1"><i
                                                            class="material-icons text-success">check_circle</i>&nbsp;Active</span>
                                                </td>
                                                <td>$123.45</td>
                                                <td>21 Jul, 2025</td>
                                                <td class="text-center">
                                                    <div class="dropstart"><a class="btn" data-bs-toggle="dropdown"
                                                            aria-expanded="false" role="button"><i
                                                                class="fa fa-ellipsis-v"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-end"><a
                                                                class="dropdown-item" href="#"><i
                                                                    class="material-icons me-2">remove_red_eye</i>View</a><a
                                                                class="dropdown-item" href="#"><i
                                                                    class="material-icons me-2">create</i>Edit</a>
                                                            <div class="dropdown-divider"></div><a
                                                                class="dropdown-item link-danger" href="#"><i
                                                                    class="material-icons me-2">delete</i>Delete</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-secondary py-4">No configuration
                                                    settings found.</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

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