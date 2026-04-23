<?php

    // ==== CONFIG FIRST (order matters) ====
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/helpers.php';
    secureSessionStart();

    require_once __DIR__ . '/config/init.php';
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/classes/User.php';
    require_once __DIR__ . '/classes/MenuManager.php';
    require_once __DIR__ . '/_include/nav_renderer.php';
    require_once __DIR__ . '/classes/ModuleManager.php';

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
    $moduleManager = new ModuleManager($pdo); // ← REQUIRED
    try {
    $stmt     = $pdo->query("SELECT * FROM zentra_module_types");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
    $error[] = "Database query failed: " . $e->getMessage();
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
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="AppConfigData">
                                        <thead>
                                            <tr class="bg-secondary">
                                                <th>Module Name</th>
                                                <th>Module Key</th>
                                                <th>Status</th>
                                                <th></th>
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
                                                            <small
                                                                class="text-secondary d-block"><?php echo $row['description']; ?></small>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td><?php echo $row['type_key']; ?></td>
                                                <td id="status_<?php echo $row['type_key']; ?>">
                                                    <?php if ($row['is_enabled'] == 1): ?>
                                                    <span class="badge bg-light d-inline-flex gap-1"><i
                                                            class="material-icons text-success">check_circle</i>&nbsp;Active</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-light d-inline-flex gap-1"><i
                                                            class="material-icons text-danger">cancel</i>&nbsp;Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch d-inline-flex ms-5 badge">
                                                        <input class="form-check-input form-check sai" type="checkbox"
                                                            <?php echo($row['is_enabled'] == 1) ? 'checked' : ''; ?>
                                                            id="check_<?php echo $row['type_key']; ?>"
                                                            name="enabled[<?php echo $row['type_key']; ?>]"
                                                            role="switch">
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
    <script>
    document.querySelectorAll('.sai').forEach(function(toggle) {
        toggle.addEventListener('change', function() {

            const typeKey = this.id.replace('check_', '');
            const isEnabled = this.checked ? 1 : 0;

            fetch('ajax/update_module_type.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `type_key=${typeKey}&is_enabled=${isEnabled}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const statusCell = document.getElementById(`status_${typeKey}`);

                        if (isEnabled === 1) {
                            statusCell.innerHTML =
                                `<span class="badge bg-light d-inline-flex gap-1"><i
                                                            class="material-icons text-success">check_circle</i>&nbsp;Active</span>`;
                        } else {
                            statusCell.innerHTML =
                                `<span class="badge bg-light d-inline-flex gap-1"><i
                                                            class="material-icons text-danger">cancel</i>&nbsp;Inactive</span>`;
                        }
                    } else {
                        alert("Error: " + data.message);
                    }
                });
        });
    });
    </script>

</body>

</html>