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
    <title><?php echo getenv('APP_NAME') ?> - Add Event</title>
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
                    <div>
                        <div class="row">
                            <div class="col-xl-7 mb-4">
                                <div class="alert alert-danger alert-dismissible" role="alert"><button
                                        class="btn-close small" type="button" aria-label="Close"
                                        data-bs-dismiss="alert"></button><span class="alert-text"><strong>Alert</strong>
                                        text.</span></div>
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="fw-bold mb-0"></h5>
                                        <div class="col-md-6 text-end">
                                            <div class="small text-muted mb-1"><span>Event Status</span></div>
                                            <div class="fw-semibold"><span
                                                    class="badge fw-bold bg-light d-inline-flex gap-1"><i
                                                        class="fa fa-hourglass-half text-info"></i>&nbsp;Draft</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body pt-2">
                                        <form method="POST" name="create-event" id="create-event">

                                            <div class="mb-3"><span>Event Title</span><input
                                                    class="fw-bold form-control-sm form-control" type="text"
                                                    autofocus="" required="" name="event_title" id="event_title"><span
                                                    class="text-secondary text-x-small"
                                                    id="event-url">http://mywebsite.com/events/2026/04/23/navrathri-2026</span>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="small text-muted mb-1"><span>Event Start Date &amp;
                                                            Time</span></div>
                                                    <div class="fw-semibold">
                                                        <input class="fw-bold form-control-sm form-control"
                                                            type="datetime-local" name="event_start_date_time"
                                                            id="event_start_date_time" required="">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-muted mb-1"><span>Event End Date &amp;
                                                            Time</span></div>
                                                    <div class="fw-semibold"><input
                                                            class="fw-bold form-control-sm form-control"
                                                            type="datetime-local" name="event_end_date_time"
                                                            id="event_end_date_time" required="">
                                                    </div>
                                                </div>
                                                <div class="col-md-12 mt-2 mb-2">
                                                    <label class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="all_day_event" onchange="setAllDayEvent(this.checked)">
                                                        <span class="form-check-label">All Day Event</span>
                                                    </label>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="small text-muted mb-1"><span>Event Location</span></div>
                                                    <div class="fw-semibold"><select class="form-select-sm form-select"
                                                            name="event_location">
                                                            <optgroup label="This is a group">
                                                                <option value="12" selected="">This is item 1</option>
                                                                <option value="13">This is item 2</option>
                                                                <option value="14">This is item 3</option>
                                                            </optgroup>
                                                        </select></div>
                                                </div>
                                            </div>
                                            <div class="text-end my-3"><button class="btn btn-primary"
                                                    type="button">Create
                                                    Event</button></div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- Start: Footer Centered -->
                    <?php include '_include/inner-footer.php'; ?>
                    <!-- End: Footer Centered -->
                </main>
            </div>
        </div>
    </div>
    <?php include '_include/body_end_plugins.php'; ?>


</body>

</html>