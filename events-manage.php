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
    } catch (Throwable $e) {
    $error[] = "Database connection failed: " . $e->getMessage();
    return;
    }
    $moduleManager = new ModuleManager($pdo); // ← REQUIRED
    try {
    $stmt   = $pdo->query("SELECT * FROM zentra_events ORDER BY event_start_date DESC, event_start_time DESC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
    $error[] = "Database or Query Error - failed: " . $e->getMessage();
    }

    $pageTitle   = "Manage Events";
    $breadcrumbs = [
    ['label' => 'Home', 'url' => '/myaccount.php'],
    ['label' => 'Events', 'url' => '/events-manage.php'],
    ['label' => 'Manage Events', 'url' => '#'],
    ];
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo getenv('APP_NAME') ?> - Manage Events</title>
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
                                <h5 class="fw-bold w-100 mb-3 mb-xl-0">Manage Events</h5>
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
                                                <th>Event Title</th>
                                                <th>Event Start Date</th>
                                                <th>Event End Date</th>
                                                <th>Event TimeZone</th>
                                                <th>All Day</th>
                                                <th>Event Venue</th>
                                                <th>Status</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (! empty($events)): ?>
                                            <?php foreach ($events as $row): ?>
                                            <tr valign="middle">
                                                <td>
                                                    <a class="text-decoration-none d-flex align-items-center gap-2"
                                                        href="/event/<?php echo $row['event_hash']; ?>/edit">
                                                        <div>
                                                            <p class="fw-bold mb-0"><?php echo $row['event_title']; ?>
                                                            </p>
                                                            <small
                                                                class="text-secondary d-block"><?php echo $row['event_slug']; ?></small>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td><small><?php echo $row['event_start_date']; ?><p>
                                                        </p></small>
                                                </td>
                                                <td><small><?php echo $row['event_end_date']; ?><p>
                                                        </p></small>
                                                </td>
                                                <td><small><?php echo $row['event_timezone']; ?></small>
                                                </td>
                                                <td><small><span
                                                            class="badge bg-light d-inline-flex gap-1"><?php echo $row['is_event_all_day'] ? 'Yes' : 'No'; ?></span></small>
                                                </td>
                                                <td><?php echo $row['event_location']; ?>
                                                </td>
                                                <td id="status_<?php echo $row['event_hash']; ?>">
                                                    <?php if ($row['is_event_active'] == 1): ?>
                                                    <span class="badge bg-light d-inline-flex gap-1"><i
                                                            class="fa fa-check-circle text-success"></i>&nbsp;Active</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-light d-inline-flex gap-1"><i
                                                            class="material-icons text-danger">cancel</i>&nbsp;Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch d-inline-flex ms-5 badge">
                                                        <input class="form-check-input form-check sai" type="checkbox"
                                                            <?php echo($row['is_event_active'] == 1) ? 'checked' : ''; ?>
                                                            id="check_<?php echo $row['event_hash']; ?>"
                                                            name="enabled[<?php echo $row['event_hash']; ?>]"
                                                            role="switch">
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-secondary py-4">No Events found.
                                                </td>
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

            fetch('ajax/update_event_status.php', {
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
                                                            class="fa fa-check-circle text-success">check_circle</i>&nbsp;Active</span>`;
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