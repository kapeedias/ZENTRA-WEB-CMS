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

    // ==== DB CONNECTION ====
    try {
    $pdo = Database::getInstance();
    } catch (Throwable $e) {
    $error[] = "Database connection failed: " . $e->getMessage();
    return;
    }

    // ==== GET CLIENT IP ====
    $ip = getClientIP();

    // ==== VALIDATE TENANT ====
    $tenantId = $_SESSION['tenant_id'] ?? null;

    if (! $tenantId) {

    // Log incident
    error_log("ERROR: Missing tenant_id in session for user_id=" . ($_SESSION['user_id'] ?? 'UNKNOWN'));

    // Destroy session safely
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

    header("Location: /login.php?session_error=1");
    exit;
    }
    $moduleManager = new ModuleManager($pdo); // ← REQUIRED
    $events        = new EventsModule($pdo, (int) $tenantId);
    $logger        = new ActivityLogger($pdo, (int) $tenantId);
    $timezones     = DateTimeZone::listIdentifiers();
    // ==== GET EVENT HASH FROM ROUTE ====
    $eventHash = $_GET['e'] ?? null;

    if (! $eventHash || ! preg_match('/^[a-f0-9]{12}$/i', $eventHash)) {
    header("Location: /events-manage.php?invalid_hash=1");
    exit;
    }

    // ==== LOAD EVENT ====
    $event = $events->getEventByHash($eventHash);

    if (! $event) {
    header("Location: /events-manage.php?not_found=1");
    exit;
    }

    $eventUrl        = $events->getEventUrl($eventHash);
    $locations       = $events->getEventLocations();
    $currentLocation = $event['event_location'];         // value stored in DB
    $isAllDay        = (int) $event['is_event_all_day']; // value from DB
    $eventCategory   = $event['event_category'];         // value stored in DB
    $poster_media_id = (int) ($event['poster_media_id'] ?? 0);
    $userId          = $_SESSION['user_id'] ?? null;
    $poster_url      = $event['poster_url'] ?: '/assets/img/1200x600.jpg';
    $poster_media_id = $event['poster_library_id'] ?: '';

    $startDT = $event['event_start_date'] . 'T' . ($event['event_start_time'] ?? '00:00');
    $endDT   = $event['event_end_date'] . 'T' . ($event['event_end_time'] ?? '00:00');
    $status  = $event['event_status'];
    $badge   = $events->getStatusBadge($status);

    // ⭐⭐⭐ SAVE HANDLER — RUNS BEFORE PAGE LOAD ⭐⭐⭐
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1️⃣ Collect POST data
    $data = [
        'event_title'       => $_POST['event_title'] ?? '',
        'event_description' => $_POST['event_description'] ?? '',
        'event_location'    => $_POST['event_location'] ?? '',
        'event_start_date'  => $_POST['event_start_date_time'] ?? '',
        'event_end_date'    => $_POST['event_end_date_time'] ?? '',
        'event_start_time'  => $_POST['event_start_date_time'] ?? '',
        'event_end_time'    => $_POST['event_end_date_time'] ?? '',
        'event_timezone'    => $_POST['event_timezone'] ?? 'UTC',
        'is_event_all_day'  => isset($_POST['is_event_all_day']) ? 1 : 0,
        'event_slug'        => $_POST['event_slug'] ?? null,
    ];

    // 2️⃣ Tags
    $tags = json_decode($_POST['hiddenTags'] ?? '[]', true);

    // 3️⃣ Poster
    $posterId = $_POST['poster_media_id'] ?? null;

    // 4️⃣ Determine CREATE vs UPDATE
    $eventHash = $_POST['event_idhash'] ?: null;

    // 5️⃣ Save event
    $savedHash = $events->saveEvent($data, $eventHash, $userId);

    // 6️⃣ Save tags
    if (method_exists($events, 'saveEventTags')) {
        $events->saveEventTags($savedHash, $tags);
        $msg[] = $eventHash ? "Event updated successfully." : "Event created successfully.";
    }

    // 8️⃣ Redirect back to edit page
    header("Location: /event/{$savedHash}/edit?saved=1");
    exit;
    }

    $pageTitle   = "Edit Event";
    $breadcrumbs = [
    ['label' => 'Home', 'url' => '/myaccount.php'],
    ['label' => 'Events', 'url' => '/events-manage.php'],
    ['label' => $event['event_title'], 'url' => "#"],
    ];

?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo getenv('APP_NAME') ?> - Edit Event</title>
    <?php include '_include/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />
    <style>
    /* Lower TinyMCE fullscreen overlay so modals can sit above it */
    .tox-fullscreen {
        z-index: 1040 !important;
    }

    /* Raise Zentra modal and backdrop above TinyMCE fullscreen */
    #zentraMediaModal {
        z-index: 2000 !important;
    }

    /* Keep backdrop below modal but above page */
    .modal {
        z-index: 2000 !important;
    }

    .modal-backdrop {
        z-index: 1990 !important;
    }
    </style>

    <script>
    // Allow Bootstrap modal to receive focus even when TinyMCE is fullscreen
    document.addEventListener('focusin', function(e) {
        const modal = document.getElementById('zentraMediaModal');
        if (modal && modal.contains(e.target)) {
            e.stopPropagation();
        }
    });
    </script>

</head>

<body>
    <form id="eventEditForm" enctype="multipart/form-data" method="POST">
        <div class="container-fluid">
            <div class="row min-vh-100">
                <?php include '_include/nav_side.php'; ?>
                <div class="col-md-9 col-xl-10 bg-body-tertiary px-0">
                    <div class="d-md-none p-2 sticky-top">
                        <?php include '_include/nav_top_branding.php'; ?>
                    </div>
                    <main class="px-3 px-md-4">
                        <!-- Start: top-nav-and-details -->
                        <?php include '_include/nav_top.php'; ?>
                        <!-- End: top-nav-and-details -->
                        <div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="card"></div>
                                    <?php if (! empty($errors) || ! empty($msg)): ?>
                                    <div class="w-100 <?php echo ! empty($errors) ? 'alert-error' : 'alert-success' ?>">
                                        <?php
                                            $list = ! empty($errors) ? $errors : $msg;
                                            foreach ($list as $item):
                                        ?>
                                        <span><?php echo htmlspecialchars($item) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                </div>
                                <div class="col-xl-8 mb-4">
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-end align-items-center">
                                            <h5 class="fw-bold mb-0"></h5>
                                            Status:&nbsp;<span
                                                class="badge d-inline-flex gap-1 <?php echo $badge['class']; ?>">
                                                <i class="fa <?php echo $badge['icon']; ?> me-1"></i>
                                                <?php echo $badge['label']; ?>
                                            </span>
                                        </div>

                                        <div class="card-body pt-2">
                                            <div class="mb-3"><span>Event Title</span>
                                                <input type="hidden" id="event_id" name="event_id"
                                                    value="<?php echo intval($event['event_id']); ?>">

                                                <input type="hidden" id="event_idhash" name="event_idhash"
                                                    value="<?php echo htmlspecialchars($eventHash) ?>">

                                                <input type="text" class="form-control fw-bold text-warning"
                                                    autofocus="" name="event_title" id="event_title"
                                                    value="<?php echo htmlspecialchars($event['event_title']); ?>"><span
                                                    class="small text-secondary"
                                                    id="event-url"><?php echo htmlspecialchars($eventUrl); ?></span>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <div class="small text-muted mb-1"><span>Event Start Date &amp;
                                                            Time</span></div>
                                                    <div class="fw-semibold">
                                                        <input class="fw-bold form-control-sm form-control text-warning"
                                                            type="datetime-local" name="event_start_date_time"
                                                            id="event_start_date_time" required=""
                                                            value="<?php echo $event['event_start_date']; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="small text-muted mb-1"><span>Event End Date &amp;
                                                            Time</span></div>
                                                    <div class="fw-semibold"><input
                                                            class="fw-bold form-control-sm form-control text-warning"
                                                            type="datetime-local" name="event_end_date_time"
                                                            id="event_end_date_time" required=""
                                                            value="<?php echo $event['event_end_date']; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="small text-muted mb-1"><span
                                                            class="small text-muted mb-1">Is this an all day
                                                            event?</span></div>
                                                    <label class="form-check">
                                                        <input class="form-check-input text-warning" type="checkbox"
                                                            id="all_day_event" onchange="setAllDayEvent(this.checked)"
                                                            <?php echo $isAllDay ? 'checked' : '' ?>>
                                                        <span class="form-check-label">Yes</span>
                                                    </label>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="small text-muted mb-1"><span>Event Timezone</span><span
                                                            class="text-danger">*</span></div>
                                                    <div class="fw-semibold">
                                                        <select class="form-select-sm form-select text-warning"
                                                            name="event_timezone" id="event_timezone" required="yes">
                                                            <?php
                                                                foreach ($timezones as $tz) {
                                                                    $selected = ($tz === 'America/Vancouver') ? 'selected' : '';
                                                                    echo "<option value=\"$tz\" $selected>$tz</option>";
                                                                }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="small text-muted mb-1"><span>Event Location</span><span
                                                            class="text-danger">*</span></div>
                                                    <div class="fw-semibold"><select
                                                            class="form-select-sm form-select text-warning"
                                                            name="event_location" required="yes">
                                                            <option value="">-- Select Location --</option>
                                                            <?php foreach ($locations as $loc): ?>
                                                            <option value="<?php echo $loc['location_id'] ?>"
                                                                <?php echo($loc['location_id'] == $currentLocation) ? 'selected' : '' ?>>
                                                                <?php echo htmlspecialchars($loc['location_name']) ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select></div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="small text-muted mb-1"><span>Event Category</span><span
                                                            class="text-danger">*</span></div>
                                                    <div class="fw-semibold"><select
                                                            class="form-select-sm form-select text-warning"
                                                            name="event_category" required="yes">
                                                            <option value="">-- Select Event Category --</option>
                                                            <option value="Event"
                                                                <?php echo($eventCategory === 'Event') ? 'selected' : '' ?>>
                                                                Event
                                                            </option>

                                                            <option value="Festival"
                                                                <?php echo($eventCategory === 'Festival') ? 'selected' : '' ?>>
                                                                Festival
                                                            </option>
                                                        </select></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="fw-bold mb-0">Event Tags</h5>
                                            <button class="btn btn-warning text-white btn-sm" type="button"
                                                data-bs-toggle="modal" data-bs-target="#tagPickerModal"> Add Event Tags
                                            </button>
                                        </div>

                                        <div class="card-body pt-2">

                                            <div class="d-flex flex-wrap gap-2">
                                                <div id="eventTagBadges" class="d-flex flex-wrap gap-2"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="fw-bold mb-0">Event Details</h5>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="list-group list-group-flush">

                                                <textarea id="event_description" name="event_description"
                                                    class="form-control" rows="10">
                                                    <?php echo trim(htmlspecialchars($event['event_description'])) ?>
                                                </textarea>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4 mb-4">
                                    <!-- Start: event-poster -->
                                    <div class="card mb-4">
                                        <div
                                            class="card-header d-flex justify-content-between align-items-center border-bottom">
                                            <h5 class="fw-bold mb-0">Event
                                                Poster</h5>
                                            <button class="btn btn-warning text-white btn-sm" type="button"
                                                onclick="openZentraMediaLibraryModal('eventPoster')"> Set Event Poster
                                            </button>
                                        </div>
                                        <div class="card-body mt-2">

                                            <div class="card mb-3">
                                                <div id="posterPreview">
                                                    <img id="posterPreviewImg"
                                                        src="<?php echo htmlspecialchars($poster_url) ?>"
                                                        alt="Poster Preview" class="img-fluid rounded border">
                                                </div>
                                                <input type="hidden" id="poster_media_id" name="poster_media_id"
                                                    value="<?php echo $poster_media_id ?>">
                                            </div>

                                            <div class="small text-muted mb-2"><span>Share This Event</span></div>
                                            <div class="d-flex gap-2"><a class="btn btn-outline-primary btn-sm"
                                                    role="button" href="#"><i class="fa fa-linkedin-square"></i></a><a
                                                    class="btn btn-outline-primary btn-sm" role="button" href="#"><i
                                                        class="fa fa-instagram"></i></a><a
                                                    class="btn btn-outline-primary btn-sm" role="button" href="#"><svg
                                                        class="bi bi-whatsapp" xmlns="http://www.w3.org/2000/svg"
                                                        width="1em" height="1em" fill="currentColor"
                                                        viewBox="0 0 16 16">
                                                        <path
                                                            d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232">
                                                        </path>
                                                    </svg></a><a class="btn btn-outline-primary btn-sm" role="button"
                                                    href="#"><i class="fa fa-facebook-square"></i></a></div>
                                        </div>

                                    </div><!-- End: event-poster -->

                                    <!-- Start: event-poster -->
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="fw-bold mb-0">Event
                                                Poster</h5>
                                        </div>
                                        <div class="card-body pt-2">


                                            <hr>
                                            <div class="small text-muted mb-2"><span>Become a Sponsor or Donor</span>
                                            </div>
                                            <button class="btn btn-primary btn-sm" type="button"> Donate Now
                                            </button>
                                        </div>
                                    </div><!-- End: event-poster -->
                                    <!-- Start: location-details -->
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="fw-bold mb-0">Contact
                                                Info</h5><button class="btn btn-primary btn-sm" type="button"> Edit
                                            </button>
                                        </div>
                                        <div class="card-body pt-2">
                                            <div class="mb-3">
                                                <div class="small text-muted mb-1"><span>Email</span></div>
                                                <div class="d-flex align-items-center"><a class="text-decoration-none"
                                                        href="mailto:sarah.johnson@company.com">sarah.johnson@company.com</a>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="small text-muted mb-1"><span>Phone</span></div>
                                                <div class="d-flex align-items-center"><span>+1
                                                        (555)
                                                        123-4567</span></div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="small text-muted mb-1"><span>Location</span></div>
                                                <div class="d-flex align-items-center"><span>San
                                                        Francisco, CA
                                                        94102</span></div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="small text-muted mb-1"><span>Time
                                                        Zone</span></div>
                                                <div class="d-flex align-items-center"><span>Pacific
                                                        Time (PT)</span></div>
                                            </div>
                                            <hr>
                                            <div class="small text-muted mb-2"><span>Social
                                                    Links</span></div>
                                            <div class="d-flex gap-2"><a class="btn btn-outline-primary btn-sm"
                                                    role="button" href="#"><i class="fa fa-linkedin-square"></i></a><a
                                                    class="btn btn-outline-primary btn-sm" role="button" href="#"><i
                                                        class="fa fa-instagram"></i></a><a
                                                    class="btn btn-outline-primary btn-sm" role="button" href="#"><svg
                                                        class="bi bi-whatsapp" xmlns="http://www.w3.org/2000/svg"
                                                        width="1em" height="1em" fill="currentColor"
                                                        viewBox="0 0 16 16">
                                                        <path
                                                            d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232">
                                                        </path>
                                                    </svg></a><a class="btn btn-outline-primary btn-sm" role="button"
                                                    href="#"><i class="fa fa-facebook-square"></i></a></div>
                                        </div>
                                    </div><!-- End: location-details -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h5 class="fw-bold mb-0">Sponsors</h5>
                                        </div>
                                        <div class="card-body pt-2">
                                            <div class="list-group list-group-flush">
                                                <div class="px-0 py-2 list-group-item">
                                                    <div class="d-flex align-items-center"><img
                                                            class="object-fit-cover rounded-circle me-2"
                                                            src="/assets/img/team/avatar1.jpg?h=fc3130ca16c6d3ee2009fd4450b80205"
                                                            width="36" height="36" alt="Team member">
                                                        <div class="flex-grow-1">
                                                            <div class="small fw-semibold"><span>Mike
                                                                    Chen</span></div><small class="text-muted">Lead
                                                                Developer</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="px-0 py-2 list-group-item">
                                                    <div class="d-flex align-items-center"><img
                                                            class="object-fit-cover rounded-circle me-2"
                                                            src="/assets/img/team/avatar3.jpg?h=d00658bdbe17fa68ec776823ea82e9c1"
                                                            width="36" height="36" alt="Team member">
                                                        <div class="flex-grow-1">
                                                            <div class="small fw-semibold"><span>Emma
                                                                    Wilson</span></div><small class="text-muted">UI/UX
                                                                Designer</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="px-0 py-2 list-group-item">
                                                    <div class="d-flex align-items-center"><img
                                                            class="object-fit-cover rounded-circle me-2"
                                                            src="/assets/img/team/avatar4.jpg?h=13fcb1a3bcb58463519bc5974513259b"
                                                            width="36" height="36" alt="Team member">
                                                        <div class="flex-grow-1">
                                                            <div class="small fw-semibold"><span>David
                                                                    Lee</span></div><small class="text-muted">Backend
                                                                Developer</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="px-0 py-2 list-group-item">
                                                    <div class="d-flex align-items-center"><img
                                                            class="object-fit-cover rounded-circle me-2"
                                                            src="/assets/img/team/avatar5.jpg?h=3c112678b7e2b1881f0b09da11f0e1e7"
                                                            width="36" height="36" alt="Team member">
                                                        <div class="flex-grow-1">
                                                            <div class="small fw-semibold"><span>Lisa
                                                                    Martinez</span></div><small class="text-muted">QA
                                                                Tester</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3"><a class="btn btn-outline-primary btn-sm w-100"
                                                    role="button" href="#">View
                                                    All Team Members</a></div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="fw-bold mb-0">Recent
                                                Activity</h5>
                                        </div>
                                        <div class="card-body pt-2">
                                            <div>
                                                <div class="d-flex mb-3">
                                                    <div class="flex-shrink-0">
                                                        <div
                                                            class="bg-success rounded-circle d-flex justify-content-center align-items-center size-30">
                                                            <svg class="bi bi-check-lg fs-5 text-white"
                                                                xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                                <path
                                                                    d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="small fw-semibold"><span>Completed
                                                                task</span></div>
                                                        <div class="small text-muted"><span>Homepage
                                                                mockup
                                                                approved</span></div>
                                                        <div class="small text-muted"><span>2
                                                                hours
                                                                ago</span></div>
                                                    </div>
                                                </div>
                                                <div class="d-flex mb-3">
                                                    <div class="flex-shrink-0">
                                                        <div
                                                            class="bg-primary rounded-circle d-flex justify-content-center align-items-center size-30">
                                                            <svg class="icon icon-tabler icon-tabler-pencil fs-5 text-white"
                                                                xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                height="1em" viewBox="0 0 24 24" stroke-width="2"
                                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                                stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none">
                                                                </path>
                                                                <path
                                                                    d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4">
                                                                </path>
                                                                <path d="M13.5 6.5l4 4"></path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="small fw-semibold"><span>Updated
                                                                project</span></div>
                                                        <div class="small text-muted"><span>Website
                                                                Redesign
                                                                progress</span></div>
                                                        <div class="small text-muted"><span>5
                                                                hours
                                                                ago</span></div>
                                                    </div>
                                                </div>
                                                <div class="d-flex mb-3">
                                                    <div class="flex-shrink-0">
                                                        <div
                                                            class="bg-warning rounded-circle d-flex justify-content-center align-items-center size-30">
                                                            <svg class="icon icon-tabler icon-tabler-brand-zoom fs-5 text-white"
                                                                xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                height="1em" viewBox="0 0 24 24" stroke-width="2"
                                                                stroke="currentColor" fill="none" stroke-linecap="round"
                                                                stroke-linejoin="round">
                                                                <path stroke="none" d="M0 0h24v24H0z" fill="none">
                                                                </path>
                                                                <path d="M17.011 9.385v5.128l3.989 3.487v-12z"></path>
                                                                <path
                                                                    d="M3.887 6h10.08c1.468 0 3.033 1.203 3.033 2.803v8.196a.991 .991 0 0 1 -.975 1h-10.373c-1.667 0 -2.652 -1.5 -2.652 -3l.01 -8a.882 .882 0 0 1 .208 -.71a.841 .841 0 0 1 .67 -.287z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="small fw-semibold"><span>Meeting
                                                                scheduled</span></div>
                                                        <div class="small text-muted"><span>Design
                                                                review on Feb
                                                                10</span></div>
                                                        <div class="small text-muted"><span>Yesterday</span></div>
                                                    </div>
                                                </div>
                                                <div class="d-flex">
                                                    <div class="flex-shrink-0">
                                                        <div
                                                            class="bg-info rounded-circle d-flex justify-content-center align-items-center size-30">
                                                            <svg class="bi bi-person-fill-check fs-5 text-white"
                                                                xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                                <path
                                                                    d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7m1.679-4.493-1.335 2.226a.75.75 0 0 1-1.174.144l-.774-.773a.5.5 0 0 1 .708-.708l.547.548 1.17-1.951a.5.5 0 1 1 .858.514M11 5a3 3 0 1 1-6 0 3 3 0 0 1 6 0">
                                                                </path>
                                                                <path
                                                                    d="M2 13c0 1 1 1 1 1h5.256A4.5 4.5 0 0 1 8 12.5a4.5 4.5 0 0 1 1.544-3.393Q8.844 9.002 8 9c-5 0-6 3-6 4">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="small fw-semibold"><span>Joined
                                                                team</span></div>
                                                        <div class="small text-muted"><span>Brand
                                                                Identity
                                                                project</span></div>
                                                        <div class="small text-muted"><span>2
                                                                days
                                                                ago</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="text-muted small"><span>Event
                                                            Start Date &amp;
                                                            Time</span></div>
                                                    <div class="mb-0 h4" data-coreui-timepicker="true"
                                                        data-coreui-toggle="date-range-picker"><input
                                                            class="w-75 form-control" type="datetime-local"></div>
                                                </div>
                                            </div>
                                            <div class="text-muted mt-2 small"><span>Event
                                                    Local Time</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="text-muted small"><span>Completed
                                                            Tasks</span></div>
                                                    <div class="mb-0 h4"><span>248</span></div>
                                                </div><span class="badge bg-danger shadow-sm">
                                                    <svg class="icon icon-tabler icon-tabler-trending-down"
                                                        xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M3 7l6 6l4 -4l8 8"></path>
                                                        <path d="M21 10l0 7l-7 0"></path>
                                                    </svg>&nbsp;-15</span>
                                            </div>
                                            <div class="text-muted mt-2 small"><span>Last
                                                    30 days</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="text-muted small"><span>Team
                                                            Members</span></div>
                                                    <div class="mb-0 h4"><span>24</span></div>
                                                </div><span class="badge bg-success shadow-sm"><svg
                                                        class="icon icon-tabler icon-tabler-trending-up"
                                                        xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M3 17l6 -6l4 4l8 -8"></path>
                                                        <path d="M14 7l7 0l0 7"></path>
                                                    </svg>&nbsp;+3 </span>
                                            </div>
                                            <div class="text-muted mt-2 small"><span>Across
                                                    5 teams</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-xl-3 mb-4">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="text-muted small"><span>Hours
                                                            Logged</span></div>
                                                    <div class="mb-0 h4"><span>156</span></div>
                                                </div><span class="badge bg-success shadow-sm"><svg
                                                        class="icon icon-tabler icon-tabler-trending-up"
                                                        xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                        fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                        <path d="M3 17l6 -6l4 4l8 -8"></path>
                                                        <path d="M14 7l7 0l0 7"></path>
                                                    </svg>&nbsp;+30</span>
                                            </div>
                                            <div class="text-muted mt-2 small"><span>This
                                                    month</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 d-flex justify-content-center">
                                <button type="submit" id="saveEventBtn" class="btn btn-secondary mt-3">Save
                                    Event</button>
                            </div>
                        </div>
                        <!-- Start: Footer Centered -->
                        <?php include '_include/inner-footer.php'; ?>
                        <!-- End: Footer Centered -->
                    </main>
                </div>
            </div>
        </div>
    </form>
    <script>
    let selectionMode = null;

    function openZentraMediaLibraryModal(mode) {
        selectionMode = mode; // <-- THIS is the missing line

        loadMediaLibrary();

        const modalEl = document.getElementById('zentraMediaModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    document.addEventListener('hidden.bs.modal', function() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 1) {
            backdrops[0].remove();
        }
    });
    </script>

    <div class="modal fade" id="zentraMediaModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Media Library</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- Filter row starts here -->
                    <div class="d-flex align-items-center gap-2 mb-3">

                        <input type="text" id="mediaSearch" class="form-control" placeholder="Search files..."
                            style="max-width: 250px;">

                        <button class="btn btn-sm btn-outline-secondary" data-filter="all">All</button>
                        <button class="btn btn-sm btn-outline-secondary" data-filter="images">Images</button>
                        <button class="btn btn-sm btn-outline-secondary" data-filter="documents">Documents</button>
                        <button class="btn btn-sm btn-outline-secondary" data-filter="videos">Videos</button>

                        <button id="uploadBtn" class="btn btn-sm btn-light border ms-auto">
                            <i class="fa fa-upload"></i> Upload
                        </button>

                    </div>
                    <!-- Filter row ends here -->
                    <div id="mediaGrid" class="row g-2" style="max-height:60vh; overflow-y:auto;"></div>
                    <!--
                        <div id="mediaGrid" class="media-grid"></div>
                                                            -->
                </div>

                <div class="modal-footer">
                    <span id="selectedCount">0 items selected</span>
                    <button class="btn btn-primary" id="insertSelectedMedia">Insert</button>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="tagPickerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Select Tags</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="text" id="tagSearchInput" class="form-control" placeholder="Search or create tags…">

                    <div id="tagSearchResults" class="list-group mt-3"></div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <input type="hidden" id="hiddenTags" name="tags">

                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script src="/assets/tinymce/tinymce.min.js"></script>

    <script>
    let quill; // global
    document.addEventListener("DOMContentLoaded", function() {

        const fullToolbar = [
            [{
                header: [1, 2, 3, 4, 5, 6, false]
            }],
            ["bold", "italic", "underline", "strike"],
            [{
                color: []
            }, {
                background: []
            }],
            [{
                script: "sub"
            }, {
                script: "super"
            }],
            [{
                list: "ordered"
            }, {
                list: "bullet"
            }],
            [{
                indent: "-1"
            }, {
                indent: "+1"
            }],
            [{
                align: []
            }],
            ["blockquote", "code-block"],
            ["link", "image", "video"],
            ["clean"]
        ];

        // ✅ Initialize Quill ONCE
        quill = new Quill("#editor", {
            theme: "snow",
            modules: {
                toolbar: fullToolbar
            }
        });

        // ✅ Override image button to open media library
        const toolbar = quill.getModule("toolbar");
        toolbar.addHandler("image", function() {
            openZentraMediaLibraryModal('editor');
        });

        // Optional: sync content before submit
        window.syncQuillContent = function() {
            const html = quill.root.innerHTML;
            document.getElementById("event_description").value = html;
            return true;
        };
    });
    </script>

    <script>
    tinymce.init({
        selector: '#event_description',
        license_key: 'gpl',
        height: 500,

        // ⭐ Load all free plugins
        plugins: `
        advlist anchor autolink autosave charmap code codesample directionality
        emoticons fullscreen help image insertdatetime link lists media nonbreaking
        pagebreak preview quickbars searchreplace table visualblocks visualchars
        wordcount
    `,

        // ⭐ Show ALL toolbar tools (no hiding, no overflow)
        toolbar_mode: 'wrap', // prevents collapsing
        toolbar: `
        undo redo |
        blocks fontfamily fontsize |
        bold italic underline strikethrough |
        forecolor backcolor |
        alignleft aligncenter alignright alignjustify |
        lineheight |
        bullist numlist outdent indent |
        myimage media table |
        link anchor |
        emoticons charmap |
        pagebreak insertdatetime |
        codesample code |
        visualblocks visualchars |
        fullscreen preview |
        removeformat
    `,

        // ⭐ Show ALL menu items
        menubar: 'file edit view insert format tools table help',

        branding: false,

        setup: function(editor) {

            // Custom toolbar image button
            editor.ui.registry.addButton('myimage', {
                icon: 'image',
                tooltip: 'Insert image',
                onAction: function() {
                    openZentraMediaLibraryModal('editor');
                }
            });

            // Replace Insert → Image… menu item
            editor.ui.registry.addMenuItem('image', {
                text: 'Image…',
                icon: 'image',
                onAction: function() {
                    openZentraMediaLibraryModal('editor');
                }
            });

            // Clicking an image inside the editor opens modal
            editor.on('click', function(e) {
                if (e.target.nodeName === 'IMG') {
                    openZentraMediaLibraryModal('editor');
                }
            });
        }

    });
    </script>

    <script>
    function insertImageIntoEditor(url) {
        const filename = url.split('/').pop();
        tinymce.activeEditor.insertContent(`<img src="${url}" alt="${filename}" style="max-width:100%; height:auto;">`);
    }
    </script>



    <script>
    document.addEventListener("DOMContentLoaded", function() {

        const saveBtn = document.getElementById("saveEventBtn");
        const form = document.getElementById("eventForm");

        if (saveBtn && form) {
            saveBtn.addEventListener("click", function() {

                // ⭐ Sync Quill content
                const html = quill.root.innerHTML;
                document.getElementById("event_description").value = html;

                // ⭐ Sync tags (if you use tags)
                if (window.selectedTags) {
                    document.getElementById("hiddenTags").value =
                        JSON.stringify(window.selectedTags);
                }

                // ⭐ Submit form
                form.submit();
            });
        }
    });
    </script>
    <script>
    let selectedTags = [];
    const badgeContainer = document.getElementById('eventTagBadges');
    const tagSearchInput = document.getElementById('tagSearchInput');
    const tagSearchResults = document.getElementById('tagSearchResults');
    // --- SEARCH TAGS ---
    tagSearchInput.addEventListener('input', function() {
        const q = this.value.trim();
        if (!q) {
            tagSearchResults.innerHTML = '';
            return;
        }

        fetch(`/api/v1/tags/search.php?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(tags => renderTagSearch(tags, q));
    });

    function renderTagSearch(tags, query) {
        tagSearchResults.innerHTML = '';

        if (tags.length === 0) {
            tagSearchResults.innerHTML = `
                <button class="list-group-item list-group-item-action"
                        onclick="addTag('${query}', true)">
                    Create tag: <strong>${query}</strong>
                </button>`;
            return;
        }

        tags.forEach(tag => {
            tagSearchResults.innerHTML += `
                <button class="list-group-item list-group-item-action"
                        onclick="addTag('${tag.tag_name}', false, ${tag.tag_id})">
                    ${tag.tag_name}
                </button>`;
        });
    }
    // --- ADD TAG ---
    function addTag(name, isNew, tagId = null) {
        const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-');

        if (selectedTags.some(t => t.slug === slug)) return;

        // If it's a new tag, create it in DB first
        if (isNew) {
            fetch('/api/v1/tags/create.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        name,
                        slug
                    })
                })
                .then(r => r.json())
                .then(data => {
                    selectedTags.push({
                        name,
                        slug,
                        tagId: data.tag_id,
                        isNew: false
                    });
                    renderBadges();
                });
        } else {
            // Existing tag
            selectedTags.push({
                name,
                slug,
                tagId,
                isNew: false
            });
            renderBadges();
        }
    }
    // --- RENDER BADGES ---
    function renderBadges() {
        badgeContainer.innerHTML = '';

        selectedTags.forEach((tag, index) => {
            badgeContainer.innerHTML += `
            <span class="badge bg-light d-inline-flex gap-1 align-items-center">
                ${tag.name}
                <span class="remove-tag text-muted" onclick="removeTag(${index})">&times;</span>
            </span>`;

        });

        document.getElementById('hiddenTags').value = JSON.stringify(selectedTags);
    }
    // --- REMOVE TAG ---
    function removeTag(index) {
        selectedTags.splice(index, 1);
        renderBadges();
    }
    // --- LOAD TAGS FOR EDIT MODE ---
    function loadEventTags(eventId) {
        fetch(`/api/v1/tags/event-tags.php?event_id=${eventId}`)
            .then(r => r.json())
            .then(tags => {
                selectedTags = tags.map(t => ({
                    name: t.tag_name,
                    slug: t.tag_slug,
                    tagId: t.tag_id,
                    isNew: false
                }));
                renderBadges();
            });
    }
    // Auto-run on page load (only if editing)
    <?php if (! empty($event_id)): ?>
    document.addEventListener("DOMContentLoaded", function() {
        loadEventTags(<?php echo $event_id ?>);
    });
    <?php endif; ?>
    </script>

    <script>
    (() => {
        // Prevent double initialization
        if (window.__posterUploaderInitialized) return;
        window.__posterUploaderInitialized = true;

        const dropzone = document.querySelector('.storage-dropzone');
        const fileInput = document.getElementById('fileInput-2');
        const preview = document.getElementById('posterPreview');
        const previewImg = document.getElementById('posterPreviewImg');
        const posterMediaIdInput = document.getElementById('poster_media_id');

        if (!dropzone || !fileInput) return;

        // CLICK → open file dialog
        //dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('click', () => {
            openZentraMediaLibraryModal("eventPoster"); // ✅ unified modal
        });

        window.applySelectedMedia = function(selected) {
            if (window.mediaLibraryMode === "eventPoster") {
                posterMediaIdInput.value = selected.id;
                previewImg.src = selected.url;
                preview.classList.remove('d-none');
                showPosterSuccess("Poster selected successfully");
            } else if (window.mediaLibraryMode === "editorInsert") {
                quill.insertEmbed(quill.getSelection().index, 'image', selected.url);
            }
            const modal = bootstrap.Modal.getInstance(document.getElementById('zentraMediaModal'));
            modal.hide();
        };
    })();
    </script>

    <script>
    function showPosterError(msg) {
        const box = document.getElementById('posterUploadError');
        box.innerHTML = `<span>${msg}</span>`;
        box.classList.remove('d-none');

        setTimeout(() => {
            box.classList.add('d-none');
        }, 5000);
    }

    function showPosterSuccess(msg) {
        const box = document.getElementById('posterUploadSuccess');
        box.innerHTML = `<span>${msg}</span>`;
        box.classList.remove('d-none');

        setTimeout(() => {
            box.classList.add('d-none');
        }, 5000);
    }
    </script>
    <script>
    function loadMediaLibrary(page = 1, search = "", filter = "all") {
        const grid = document.getElementById("mediaGrid");
        if (!grid) {
            console.error("mediaGrid not found");
            return;
        }

        grid.innerHTML = `<div class="text-center p-4">Loading...</div>`;

        const params = new URLSearchParams({
            page: page,
            q: search,
            filter: filter
        });

        fetch(`/api/v1/media/list.php?${params.toString()}`)
            .then(r => r.json())
            .then(files => {
                grid.innerHTML = "";

                if (!files.length) {
                    grid.innerHTML = `<div class="text-center text-muted p-4">No media found</div>`;
                    return;
                }

                files.forEach(file => {
                    grid.innerHTML += `
                    <div class="media-item" data-id="${file.id}" data-url="${file.url}" data-tags="${file.tags || ''}">
                    <img src="${file.url}" alt="${file.name}">
                    <div class="media-overlay">
                        <button class="tag-btn" title="Edit Tags"><i class="fa fa-tags"></i></button>
                        <button class="link-btn" title="Copy Link"><i class="fa fa-link"></i></button>
                    </div>
                    <input type="checkbox" class="select-checkbox">
                    </div>
                    `;
                });
            })
            .catch(err => {
                console.error("loadMediaLibrary error:", err);
                grid.innerHTML = `<div class="text-danger p-4">Error loading media</div>`;
            });
    }
    </script>
    <script>
    document.addEventListener('click', function(e) {
        const item = e.target.closest('.media-item');
        if (!item) return;

        if (e.target.closest('.tag-btn')) {
            console.log('Tag clicked for ID:', item.dataset.id);
            return;
        }

        if (e.target.closest('.link-btn')) {
            navigator.clipboard.writeText(item.dataset.url);
            console.log('Link copied:', item.dataset.url);
            return;
        }

        if (!e.target.closest('.media-overlay')) {
            const checkbox = item.querySelector('.select-checkbox');
            checkbox.checked = !checkbox.checked;
            item.classList.toggle('selected', checkbox.checked);
        }
    });

    function updateSelectedCount() {
        const count = document.querySelectorAll('.media-item.selected').length;
        const el = document.getElementById('selectedCount');
        if (el) el.textContent = `${count} item${count !== 1 ? 's' : ''} selected`;
    }

    document.addEventListener('click', function(e) {
        const img = e.target.closest('.media-item img');
        if (!img) return;

        const item = img.closest('.media-item');
        const checkbox = item.querySelector('.select-checkbox');
        if (!checkbox) return;

        // --- POSTER MODE: allow only one selection ---
        if (selectionMode === 'eventPoster') {
            // Unselect all other items
            document.querySelectorAll('.media-item.selected').forEach(i => {
                if (i !== item) {
                    i.classList.remove('selected');
                    i.querySelector('.select-checkbox').checked = false;
                }
            });
        }

        // Toggle this checkbox
        checkbox.click();
    });




    document.getElementById('insertSelectedMedia').addEventListener('click', function(e) {
        if (e && e.target) e.target.blur();

        // --- EDITOR MODE (TinyMCE) ---
        if (selectionMode === 'editor') {
            const selectedItems = document.querySelectorAll('.media-item.selected');
            if (!selectedItems.length) return;

            selectedItems.forEach(item => {
                const url = item.dataset.url;
                tinymce.activeEditor.insertContent(`<img src="${url}" alt="">`);
            });

            bootstrap.Modal.getInstance(document.getElementById('zentraMediaModal')).hide();
            return;
        }

        // --- POSTER MODE ---
        if (selectionMode === 'eventPoster') {
            const selected = document.querySelector('.media-item.selected');
            if (!selected) return;

            const url = selected.dataset.url;
            const id = selected.dataset.id;

            // Update preview
            document.getElementById('posterPreviewImg').src = url;
            document.getElementById('posterPreview').classList.remove('d-none');

            // Update hidden input
            document.getElementById('poster_media_id').value = id;

            // Update event poster immediately
            const eventId = document.getElementById('event_id').value;

            fetch('/ajax/update_event_poster.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        event_id: eventId,
                        library_id: id
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        alert("Failed to update event poster");
                    }
                });

            bootstrap.Modal.getInstance(document.getElementById('zentraMediaModal')).hide();
            return;
        }
    });


    document.getElementById('zentraMediaModal')
        .addEventListener('hide.bs.modal', function() {
            // Remove focus from ANY element inside the modal before hiding
            if (document.activeElement && this.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });
    </script>


    <?php include '_include/body_end_plugins.php'; ?>
</body>

</html>