<?php
    declare (strict_types = 1);

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
    require_once __DIR__ . '/classes/EventsModule.php';
    require_once __DIR__ . '/classes/ActivityLogger.php';

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

    $moduleManager = new ModuleManager($pdo);

    // ==== LOAD LOGGER + EVENTS MODULE ====
    $logger   = new ActivityLogger($pdo, (int) ($_SESSION['tenant_id'] ?? 0));
    $tenantId = $_SESSION['tenant_id'] ?? null;

    if (! $tenantId) {

    // 1. User-friendly error
    $error[] = "Your session is missing tenant information. Please log in again.";

    // 2. Log to PHP error log
    error_log("ERROR: Missing tenant_id in session for user_id={$_SESSION['user_id']}");

    // 3. Log to user activity audit
    $logger->log(
        $_SESSION['user_id'] ?? null,
        "Missing tenant_id in session",
        "System Error",
        [
            'ip'        => getClientIP(),
            'browser'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'tenant_id' => null,
            'context'   => 'events-add.php missing tenant_id',
        ]
    );

    // 4. Destroy session safely
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();

    // 5. Redirect user to login
    header("Location: /login.php?session_error=1");
    exit;
    }

    $events    = new EventsModule($pdo, $tenantId);
    $locations = $events->getEventLocations();

    // ==== HANDLE FORM SUBMISSION ====
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title    = trim($_POST['event_title'] ?? '');
    $startDT  = trim($_POST['event_start_date_time'] ?? '');
    $endDT    = trim($_POST['event_end_date_time'] ?? '');
    $location = trim($_POST['event_location'] ?? '');
    $eventURL = trim($_POST['event_url'] ?? '');
    $timezone = trim($_POST['event_timezone'] ?? 'America/Vancouver');
    $isAllDay = isset($_POST['all_day_event']) ? 1 : 0;

    if ($title === '' || $startDT === '' || $endDT === '') {
        $error[] = "Please fill in all required fields.";
    } else {

        // Extract slug from generated URL
        $slug = basename($eventURL);

        // Split datetime-local
        list($startDate, $startTime) = explode('T', $startDT);
        list($endDate, $endTime)     = explode('T', $endDT);

        // Normalize time format
        $startTime .= ':00';
        $endTime   .= ':00';

        // All-day override
        if ($isAllDay) {
            $startTime = "00:00:00";
            $endTime   = "23:59:00";
        }

        // Build local datetime strings
        $localStart = $startDate . ' ' . $startTime;
        $localEnd   = $endDate . ' ' . $endTime;

        // Convert to UTC using timezone.php
        $startUTC = toUTC($localStart, $timezone);
        $endUTC   = toUTC($localEnd, $timezone);

        // Load user
        $user   = User::loadFromSession();
        $userId = $user->id;

        // Build data array for EventsModule
        $data = [
            'event_slug'        => $slug,
            'event_title'       => $title,
            'event_description' => '',
            'event_location'    => $location,
            // Local datetime components
            'event_start_date'  => $localStart,
            'event_start_time'  => $startTime,
            'event_end_date'    => $localEnd,
            'event_end_time'    => $endTime,
            // UTC datetime
            'start_date_utc'    => $startUTC,
            'end_date_utc'      => $endUTC,
            'event_timezone'    => $timezone,
            'is_event_all_day'  => $isAllDay,
        ];

        // Create event — returns event_hash
        $eventHash = $events->saveEvent($data, null, $userId);

        // Redirect to clean URL using hash
        header("Location: /event/{$eventHash}/edit");
        exit;
    }
    }

    $pageTitle   = "Add Event";
    $breadcrumbs = [
    ['label' => 'Home', 'url' => '/myaccount.php'],
    ['label' => 'Events', 'url' => '/events-manage.php'],
    ['label' => 'Add Event', 'url' => "#"],
    ];

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

                                <!-- Start: alert -->
                                <?php if (! empty($success)): ?>
                                <div class="w-100 alert-success shadow alert-dismissible" role="alert">
                                    <?php echo implode('<br>', $success) ?>
                                </div>
                                <?php endif; ?>

                                <?php if (! empty($errors)): ?>
                                <div class="w-100 alert-error shadow alert-dismissible" role="alert">
                                    <?php echo implode('<br>', $errors) ?>
                                </div>
                                <?php endif; ?>
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
                                            <input type="hidden" name="event_url" id="event_url_hidden">

                                            <div class="mb-3"><span>Event Title</span> <span
                                                    class="text-danger">*</span><input
                                                    class="fw-bold form-control-sm form-control" type="text"
                                                    autofocus="" required="" name="event_title" id="event_title"><span
                                                    class="text-secondary text-x-small"
                                                    id="event-url">http://mywebsite.com/events/yyyy/mm/dd/event-title</span>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="small text-muted mb-1"><span>Event Start Date &amp;
                                                            Time</span><span class="text-danger">*</span></div>
                                                    <div class="fw-semibold">
                                                        <input class="fw-bold form-control-sm form-control"
                                                            type="datetime-local" name="event_start_date_time"
                                                            id="event_start_date_time" required="">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-muted mb-1"><span>Event End Date &amp;
                                                            Time</span><span class="text-danger">*</span></div>
                                                    <div class="fw-semibold"><input
                                                            class="fw-bold form-control-sm form-control"
                                                            type="datetime-local" name="event_end_date_time"
                                                            id="event_end_date_time" required="">
                                                    </div>
                                                </div>
                                                <div class="col-md-12 mt-2 mb-2 pt-3">
                                                    <label class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="all_day_event" name="all_day_event"
                                                            onchange="setAllDayEvent(this.checked)">
                                                        <span class="form-check-label">All Day Event</span>
                                                    </label>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-muted mb-1"><span>Event Timezone</span><span
                                                            class="text-danger">*</span></div>
                                                    <div class="fw-semibold">
                                                        <select class="form-select-sm form-select" name="event_timezone"
                                                            id="event_timezone" required="yes">
                                                            <?php
                                                                $timezones = DateTimeZone::listIdentifiers();
                                                                foreach ($timezones as $tz) {
                                                                    $selected = ($tz === 'America/Vancouver') ? 'selected' : '';
                                                                    echo "<option value=\"$tz\" $selected>$tz</option>";
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="small text-muted mb-1"><span>Event Location</span><span
                                                            class="text-danger">*</span></div>
                                                    <div class="fw-semibold"><select class="form-select-sm form-select"
                                                            name="event_location" required="yes">
                                                            <option value="">-- Select Location --</option>
                                                            <?php foreach ($locations as $loc): ?>
                                                            <option value="<?php echo $loc['location_id'] ?>">
                                                                <?php echo htmlspecialchars($loc['location_name']) ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select></div>
                                                </div>
                                            </div>
                                            <div class="text-end my-3"><button class="btn btn-primary"
                                                    type="submit">Create
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