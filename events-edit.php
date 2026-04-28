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
    require_once __DIR__ . '/classes/EventsModule.php';
    require_once __DIR__ . '/classes/ActivityLogger.php';

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

    $moduleManager = new ModuleManager($pdo);

    // ==== LOAD LOGGER + EVENTS MODULE ====
    $logger = new ActivityLogger($pdo);
    $events = new EventsModule($pdo, 1); // object_id = 1 (or dynamic)
    $events->setLogger($logger);

    // ==== HANDLE FORM SUBMISSION ====
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title    = trim($_POST['event_title'] ?? '');
    $startDT  = trim($_POST['event_start_date_time'] ?? '');
    $endDT    = trim($_POST['event_end_date_time'] ?? '');
    $location = trim($_POST['event_location'] ?? '');
    $eventURL = trim($_POST['event_url'] ?? '');

    if ($title === '' || $startDT === '' || $endDT === '') {
        $error[] = "Please fill in all required fields.";
    } else {

        // Extract slug from generated URL
        $slug = basename($eventURL);

        // Split datetime-local
        list($startDate, $startTime) = explode('T', $startDT);
        list($endDate, $endTime)     = explode('T', $endDT);

        // Load user
        $user   = User::loadFromSession();
        $userId = $user->id;

        // Build data array for EventsModule
        $data = [
            'event_slug'        => $slug,
            'title'             => $title,
            'event_description' => '',
            'event_location'    => $location,
            'event_start_date'  => $startDate,
            'event_end_date'    => $endDate,
            'event_start_time'  => $startTime,
            'event_end_time'    => $endTime,
            'event_timezone'    => 'UTC',
            'is_event_all_day'  => isset($_POST['all_day_event']) ? 1 : 0,
        ];

        // Logging context
        $context = [
            'ip' => $ip,
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        // Create event (this logs automatically)
        $eventId = $events->create($data, $userId, $context);

        // Redirect to event list or detail page
        header("Location: events-edit.php?created=1&id=" . $eventId);
        exit;
    }
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
                    <!-- Start: top-nav-and-details -->
                    <?php include '_include/nav_top.php'; ?>
                    <!-- End: top-nav-and-details -->
                    <div>
                        <div class="row">
                            <div class="col-xl-8 mb-4">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="fw-bold mb-0"></h5><span
                                            class="badge bg-light d-inline-flex gap-1"><svg
                                                class="bi bi-check-circle-fill text-success"
                                                xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                fill="currentColor" viewBox="0 0 16 16">
                                                <path
                                                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z">
                                                </path>
                                            </svg>&nbsp;Done</span>
                                    </div>
                                    <div class="card-body pt-2">
                                        <div class="mb-3"><span>Event Title</span><input type="text"
                                                class="form-control" autofocus=""><span class="small text-secondary"
                                                id="event-url">http://mywebsite.com/events/2026/04/23/navrathri-2026</span>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="small text-muted mb-1"><span>Event Start Date &amp;
                                                        Time</span></div>
                                                <div class="fw-semibold"><span>Marketing</span></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="small text-muted mb-1"><span>Event End Date &amp;
                                                        Time</span></div>
                                                <div class="fw-semibold"><span>EMP-2021-0342</span></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="small text-muted mb-1"><span>Join Date</span></div>
                                                <div class="fw-semibold"><span>January 15, 2023</span></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="small text-muted mb-1"><span>Reports To</span></div>
                                                <div class="fw-semibold"><span>Michael Chen (VP Marketing)</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="fw-bold mb-0">Skills &amp; Expertise</h5><button
                                            class="btn btn-primary btn-sm" type="button"> Add </button>
                                    </div>
                                    <div class="card-body pt-2">
                                        <div class="d-flex flex-wrap gap-2"><span
                                                class="badge bg-light d-inline-flex gap-1">&nbsp;Project
                                                Management</span><span
                                                class="badge bg-light d-inline-flex gap-1">Agile/Scrum</span><span
                                                class="badge bg-light d-inline-flex gap-1">Leadership</span><span
                                                class="badge bg-light d-inline-flex gap-1">Strategic
                                                Planning</span><span
                                                class="badge bg-light d-inline-flex gap-1">Stakeholder
                                                Management</span><span
                                                class="badge bg-light d-inline-flex gap-1">Jira</span><span
                                                class="badge bg-light d-inline-flex gap-1">Microsoft Project</span><span
                                                class="badge bg-light d-inline-flex gap-1">Risk Management</span><span
                                                class="badge bg-light d-inline-flex gap-1">Budget Planning</span><span
                                                class="badge bg-light d-inline-flex gap-1">Team Building</span></div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="fw-bold mb-0">Recent Projects</h5><a class="btn btn-primary btn-sm"
                                            role="button" href="#">View All</a>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="list-group list-group-flush">
                                            <div class="px-0 py-3 list-group-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <h6 class="me-2 mb-0">Website Redesign</h6><span
                                                                class="badge bg-light d-inline-flex gap-1"><svg
                                                                    class="bi bi-check-circle-fill text-success"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z">
                                                                    </path>
                                                                </svg>&nbsp;Done</span>
                                                        </div>
                                                        <p class="small text-muted mb-2">Complete redesign of company
                                                            website with modern UI/UX</p>
                                                        <div class="progress mb-2">
                                                            <div class="progress-bar bg-info" aria-valuenow="68"
                                                                aria-valuemin="0" aria-valuemax="100"
                                                                style="width: 68%;"><span
                                                                    class="visually-hidden">68%</span></div>
                                                        </div>
                                                        <div class="small text-muted d-flex gap-3"><span> Due: Mar 15,
                                                                2026</span><span> 8 members</span><span> 68%
                                                                complete</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="px-0 py-3 list-group-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <h6 class="me-2 mb-0">Brand Identity Refresh</h6><span
                                                                class="badge bg-light d-inline-flex gap-1"><svg
                                                                    class="icon icon-tabler icon-tabler-loader"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" viewBox="0 0 24 24" stroke-width="2"
                                                                    stroke="currentColor" fill="none"
                                                                    stroke-linecap="round" stroke-linejoin="round">
                                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none">
                                                                    </path>
                                                                    <path d="M12 6l0 -3"></path>
                                                                    <path d="M16.25 7.75l2.15 -2.15"></path>
                                                                    <path d="M18 12l3 0"></path>
                                                                    <path d="M16.25 16.25l2.15 2.15"></path>
                                                                    <path d="M12 18l0 3"></path>
                                                                    <path d="M7.75 16.25l-2.15 2.15"></path>
                                                                    <path d="M6 12l-3 0"></path>
                                                                    <path d="M7.75 7.75l-2.15 -2.15"></path>
                                                                </svg>&nbsp;In Progress</span>
                                                        </div>
                                                        <p class="small text-muted mb-2">Update brand guidelines and
                                                            create new marketing materials</p>
                                                        <div class="progress mb-2">
                                                            <div class="progress-bar bg-info" aria-valuenow="85"
                                                                aria-valuemin="0" aria-valuemax="100"
                                                                style="width: 85%;"><span
                                                                    class="visually-hidden">85%</span></div>
                                                        </div>
                                                        <div class="small text-muted d-flex gap-3"><span> Due: Feb 28,
                                                                2026</span><span> 5 members</span><span> 85%
                                                                complete</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="px-0 py-3 list-group-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center mb-2">
                                                            <h6 class="me-2 mb-0">CRM Integration</h6><span
                                                                class="badge bg-light d-inline-flex gap-1"><svg
                                                                    class="bi bi-check-circle-fill text-success"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z">
                                                                    </path>
                                                                </svg>&nbsp;Done</span>
                                                        </div>
                                                        <p class="small text-muted mb-2">Integrate new CRM system with
                                                            existing tools</p>
                                                        <div class="progress mb-2">
                                                            <div class="progress-bar bg-info" aria-valuenow="100"
                                                                aria-valuemin="0" aria-valuemax="100"
                                                                style="width: 100%;"><span
                                                                    class="visually-hidden">100%</span></div>
                                                        </div>
                                                        <div class="small text-muted d-flex gap-3"><span> Completed: Jan
                                                                10, 2026</span><span> 6 members</span></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 mb-4">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="fw-bold mb-0">Contact Info</h5><button class="btn btn-primary btn-sm"
                                            type="button"> Edit </button>
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
                                            <div class="d-flex align-items-center"><span>+1 (555) 123-4567</span></div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small text-muted mb-1"><span>Location</span></div>
                                            <div class="d-flex align-items-center"><span>San Francisco, CA 94102</span>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small text-muted mb-1"><span>Time Zone</span></div>
                                            <div class="d-flex align-items-center"><span>Pacific Time (PT)</span></div>
                                        </div>
                                        <hr>
                                        <div class="small text-muted mb-2"><span>Social Links</span></div>
                                        <div class="d-flex gap-2"><a class="btn btn-outline-primary btn-sm"
                                                role="button" href="#"><svg class="bi bi-linkedin"
                                                    xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    fill="currentColor" viewBox="0 0 16 16">
                                                    <path
                                                        d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z">
                                                    </path>
                                                </svg></a><a class="btn btn-outline-primary btn-sm" role="button"
                                                href="#"><svg class="bi bi-instagram" xmlns="http://www.w3.org/2000/svg"
                                                    width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                    <path
                                                        d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334">
                                                    </path>
                                                </svg></a><a class="btn btn-outline-primary btn-sm" role="button"
                                                href="#"><svg class="bi bi-dribbble" xmlns="http://www.w3.org/2000/svg"
                                                    width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd"
                                                        d="M8 0C3.584 0 0 3.584 0 8s3.584 8 8 8c4.408 0 8-3.584 8-8s-3.592-8-8-8m5.284 3.688a6.8 6.8 0 0 1 1.545 4.251c-.226-.043-2.482-.503-4.755-.217-.052-.112-.096-.234-.148-.355-.139-.33-.295-.668-.451-.99 2.516-1.023 3.662-2.498 3.81-2.69zM8 1.18c1.735 0 3.323.65 4.53 1.718-.122.174-1.155 1.553-3.584 2.464-1.12-2.056-2.36-3.74-2.551-4A7 7 0 0 1 8 1.18m-2.907.642A43 43 0 0 1 7.627 5.77c-3.193.85-6.013.833-6.317.833a6.87 6.87 0 0 1 3.783-4.78zM1.163 8.01V7.8c.295.01 3.61.053 7.02-.971.199.381.381.772.555 1.162l-.27.078c-3.522 1.137-5.396 4.243-5.553 4.504a6.82 6.82 0 0 1-1.752-4.564zM8 14.837a6.8 6.8 0 0 1-4.19-1.44c.12-.252 1.509-2.924 5.361-4.269.018-.009.026-.009.044-.017a28.3 28.3 0 0 1 1.457 5.18A6.7 6.7 0 0 1 8 14.837m3.81-1.171c-.07-.417-.435-2.412-1.328-4.868 2.143-.338 4.017.217 4.251.295a6.77 6.77 0 0 1-2.924 4.573z">
                                                    </path>
                                                </svg></a></div>
                                    </div>
                                </div>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="fw-bold mb-0">Team</h5>
                                    </div>
                                    <div class="card-body pt-2">
                                        <div class="list-group list-group-flush">
                                            <div class="px-0 py-2 list-group-item">
                                                <div class="d-flex align-items-center"><img
                                                        class="object-fit-cover rounded-circle me-2"
                                                        src="assets/img/team/avatar1.jpg?h=fc3130ca16c6d3ee2009fd4450b80205"
                                                        width="36" height="36" alt="Team member">
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-semibold"><span>Mike Chen</span></div>
                                                        <small class="text-muted">Lead Developer</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="px-0 py-2 list-group-item">
                                                <div class="d-flex align-items-center"><img
                                                        class="object-fit-cover rounded-circle me-2"
                                                        src="assets/img/team/avatar3.jpg?h=d00658bdbe17fa68ec776823ea82e9c1"
                                                        width="36" height="36" alt="Team member">
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-semibold"><span>Emma Wilson</span></div>
                                                        <small class="text-muted">UI/UX Designer</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="px-0 py-2 list-group-item">
                                                <div class="d-flex align-items-center"><img
                                                        class="object-fit-cover rounded-circle me-2"
                                                        src="assets/img/team/avatar4.jpg?h=13fcb1a3bcb58463519bc5974513259b"
                                                        width="36" height="36" alt="Team member">
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-semibold"><span>David Lee</span></div>
                                                        <small class="text-muted">Backend Developer</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="px-0 py-2 list-group-item">
                                                <div class="d-flex align-items-center"><img
                                                        class="object-fit-cover rounded-circle me-2"
                                                        src="assets/img/team/avatar5.jpg?h=3c112678b7e2b1881f0b09da11f0e1e7"
                                                        width="36" height="36" alt="Team member">
                                                    <div class="flex-grow-1">
                                                        <div class="small fw-semibold"><span>Lisa Martinez</span></div>
                                                        <small class="text-muted">QA Tester</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3"><a class="btn btn-outline-primary btn-sm w-100" role="button"
                                                href="#">View All Team Members</a></div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="fw-bold mb-0">Recent Activity</h5>
                                    </div>
                                    <div class="card-body pt-2">
                                        <div>
                                            <div class="d-flex mb-3">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="bg-success rounded-circle d-flex justify-content-center align-items-center size-30">
                                                        <svg class="bi bi-check-lg fs-5 text-white"
                                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            fill="currentColor" viewBox="0 0 16 16">
                                                            <path
                                                                d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z">
                                                            </path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <div class="small fw-semibold"><span>Completed task</span></div>
                                                    <div class="small text-muted"><span>Homepage mockup approved</span>
                                                    </div>
                                                    <div class="small text-muted"><span>2 hours ago</span></div>
                                                </div>
                                            </div>
                                            <div class="d-flex mb-3">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="bg-primary rounded-circle d-flex justify-content-center align-items-center size-30">
                                                        <svg class="icon icon-tabler icon-tabler-pencil fs-5 text-white"
                                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path
                                                                d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4">
                                                            </path>
                                                            <path d="M13.5 6.5l4 4"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <div class="small fw-semibold"><span>Updated project</span></div>
                                                    <div class="small text-muted"><span>Website Redesign progress</span>
                                                    </div>
                                                    <div class="small text-muted"><span>5 hours ago</span></div>
                                                </div>
                                            </div>
                                            <div class="d-flex mb-3">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="bg-warning rounded-circle d-flex justify-content-center align-items-center size-30">
                                                        <svg class="icon icon-tabler icon-tabler-brand-zoom fs-5 text-white"
                                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                            fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                            <path d="M17.011 9.385v5.128l3.989 3.487v-12z"></path>
                                                            <path
                                                                d="M3.887 6h10.08c1.468 0 3.033 1.203 3.033 2.803v8.196a.991 .991 0 0 1 -.975 1h-10.373c-1.667 0 -2.652 -1.5 -2.652 -3l.01 -8a.882 .882 0 0 1 .208 -.71a.841 .841 0 0 1 .67 -.287z">
                                                            </path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <div class="small fw-semibold"><span>Meeting scheduled</span></div>
                                                    <div class="small text-muted"><span>Design review on Feb 10</span>
                                                    </div>
                                                    <div class="small text-muted"><span>Yesterday</span></div>
                                                </div>
                                            </div>
                                            <div class="d-flex">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="bg-info rounded-circle d-flex justify-content-center align-items-center size-30">
                                                        <svg class="bi bi-person-fill-check fs-5 text-white"
                                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            fill="currentColor" viewBox="0 0 16 16">
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
                                                    <div class="small fw-semibold"><span>Joined team</span></div>
                                                    <div class="small text-muted"><span>Brand Identity project</span>
                                                    </div>
                                                    <div class="small text-muted"><span>2 days ago</span></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mb-4">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col"><input class="fw-bold form-control-lg form-control"
                                                    type="text" name="event-title" autocomplete="off" required="">
                                                <h3 class="fw-bold mb-1"></h3>
                                                <p class="small text-muted mb-2" id="event-url-1">
                                                    https://website.com/events/2026/04/23/shiva-ratri</p>
                                                <div class="d-flex flex-wrap gap-2 my-3" mt-3=""><span
                                                        class="badge bg-success">Active</span><span
                                                        class="badge bg-light"> <i
                                                            class="fa fa-repeat me-1"></i>&nbsp;Repeats every year on
                                                        3rd Monday of May</span><span class="badge bg-light"> Marketing
                                                        Team </span></div>
                                                <div
                                                    class="small text-muted d-flex flex-column gap-2 flex-xl-row mb-3 mb-xl-0">
                                                    <ul class="list-inline">
                                                        <li class="list-inline-item">Item 1</li>
                                                        <li class="list-inline-item">Item 2</li>
                                                        <li class="list-inline-item">Item 3</li>
                                                        <li class="list-inline-item">Item 4</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="col-auto col-xxl-4 text-center">
                                                <div class="mb-4 storage-dropzone"><svg
                                                        class="bi bi-cloud-arrow-up fs-1 mb-2"
                                                        xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                        fill="currentColor" viewBox="0 0 16 16">
                                                        <path fill-rule="evenodd"
                                                            d="M7.646 5.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708z">
                                                        </path>
                                                        <path
                                                            d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z">
                                                        </path>
                                                    </svg>
                                                    <h6 class="fw-bold mb-1">Click or drag event poster to upload</h6>
                                                    <p class="small text-muted mb-0">PNG or JPG (max. 2 MB)</p><input
                                                        class="d-none" type="file" id="fileInput-2">
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
                                                <div class="text-muted small"><span>Event Start Date &amp; Time</span>
                                                </div>
                                                <div class="mb-0 h4" data-coreui-timepicker="true"
                                                    data-coreui-toggle="date-range-picker"><input
                                                        class="w-75 form-control" type="datetime-local"></div>
                                            </div>
                                        </div>
                                        <div class="text-muted mt-2 small"><span>Event Local Time</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-3 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="text-muted small"><span>Completed Tasks</span></div>
                                                <div class="mb-0 h4"><span>248</span></div>
                                            </div><span class="badge bg-danger shadow-sm"> <svg
                                                    class="icon icon-tabler icon-tabler-trending-down"
                                                    xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M3 7l6 6l4 -4l8 8"></path>
                                                    <path d="M21 10l0 7l-7 0"></path>
                                                </svg>&nbsp;-15</span>
                                        </div>
                                        <div class="text-muted mt-2 small"><span>Last 30 days</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-3 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="text-muted small"><span>Team Members</span></div>
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
                                        <div class="text-muted mt-2 small"><span>Across 5 teams</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-3 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="text-muted small"><span>Hours Logged</span></div>
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
                                        <div class="text-muted mt-2 small"><span>This month</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- Start: Footer Centered -->
                    <footer class="text-center py-5"><a
                            class="text-decoration-none link-body-emphasis d-inline-flex align-items-center"
                            href="#"><span class="fs-4 fw-bold brand-primary">ZENTRA</span><span
                                class="fs-4 brand-secondary">CMS</span></a>
                        <div class="d-flex justify-content-center align-items-center flex-wrap mb-2"><a
                                class="link-body-emphasis mx-2" href="#">Privacy Policy</a><a
                                class="link-body-emphasis mx-2" href="#">Terms of Service</a><a
                                class="link-body-emphasis mx-2" href="#">Cookie Policy</a></div>
                        <p class="text-muted mb-2">© 2026 Brand. All rights reserved.</p>
                    </footer><!-- End: Footer Centered -->
                </main>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.min.js?h=da74781f0d8a702dd153810a21ac1707"></script>
</body>

</html>