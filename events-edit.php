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
    $event           = $events->getEventByHash($eventHash);
    $eventUrl        = $events->getEventUrl($eventHash);
    $locations       = $events->getEventLocations();
    $currentLocation = $event['event_location'];         // value stored in DB
    $isAllDay        = (int) $event['is_event_all_day']; // value from DB
    $eventCategory   = $event['event_category'];         // value stored in DB

    if (! $event) {
    header("Location: /events-manage.php?not_found=1");
    exit;
    }

    $startDT = $event['event_start_date'] . 'T' . ($event['event_start_time'] ?? '00:00');
    $endDT   = $event['event_end_date'] . 'T' . ($event['event_end_time'] ?? '00:00');
    $status  = $event['event_status'];
    $badge   = $events->getStatusBadge($status);

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

</head>


<body>
    <form id="eventEditForm" enctype="multipart/form-data">
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

                                <!-- ========================= -->
                                <!-- TOP CARD: TITLE + POSTER -->
                                <!-- ========================= -->
                                <div class="col-12 mb-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row">

                                                <!-- LEFT SIDE -->
                                                <div class="col">
                                                    <input class="fw-bold form-control-lg form-control" type="text"
                                                        name="event_title" id="event_title" autocomplete="off" required
                                                        value="<?php echo htmlspecialchars($event['event_title']); ?>">

                                                    <h3 class="fw-bold mb-1"></h3>

                                                    <p class="small text-muted mb-2" id="event-url">
                                                        <?php echo htmlspecialchars($eventUrl); ?>
                                                    </p>

                                                    <div class="d-flex flex-wrap gap-2 my-3">
                                                        <span
                                                            class="badge d-inline-flex gap-1 <?php echo $badge['class']; ?>">
                                                            <i class="fa <?php echo $badge['icon']; ?> me-1"></i>
                                                            <?php echo $badge['label']; ?>
                                                        </span>

                                                        <span class="badge bg-light">
                                                            <i class="fa fa-repeat me-1"></i>
                                                            Repeats every year on 3rd Monday of May
                                                        </span>

                                                        <span class="badge bg-light">Marketing Team</span>
                                                    </div>

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

                                                <!-- RIGHT SIDE: POSTER UPLOAD -->
                                                <div class="col-auto col-xxl-4 text-center">
                                                    <div class="mb-4 storage-dropzone">
                                                        <i class="fa fa-cloud-upload fa-3x text-muted mb-3"></i>

                                                        <h6 class="fw-bold mb-1">Click or drag event poster to upload
                                                        </h6>
                                                        <p class="small text-muted mb-0">PNG or JPG (max. 2 MB)</p>

                                                        <input class="d-none" type="file" id="fileInput-2"
                                                            accept="image/png, image/jpeg">
                                                        <input type="hidden" name="poster_media_id"
                                                            id="poster_media_id">
                                                    </div>
                                                </div>

                                                <!-- INLINE MESSAGES (correctly wrapped in col-12) -->
                                                <div class="col-12">
                                                    <div id="posterUploadError" class="w-100 alert-error d-none"></div>
                                                    <div id="posterUploadSuccess" class="w-100 alert-success d-none">
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <!-- ========================= -->
                                <!-- LEFT COLUMN (Event Fields) -->
                                <!-- ========================= -->
                                <div class="col-xl-8 mb-4">

                                    <!-- Event Title / Dates / Timezone / Location -->
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-end align-items-center">
                                            <h5 class="fw-bold mb-0"></h5>
                                            Status:&nbsp;
                                            <span class="badge d-inline-flex gap-1 <?php echo $badge['class']; ?>">
                                                <i class="fa <?php echo $badge['icon']; ?> me-1"></i>
                                                <?php echo $badge['label']; ?>
                                            </span>
                                        </div>

                                        <div class="card-body pt-2">
                                            <div class="mb-3">
                                                <span>Event Title</span>
                                                <input type="text" class="form-control fw-bold text-warning"
                                                    name="event_title" id="event_title_input"
                                                    value="<?php echo htmlspecialchars($event['event_title']); ?>">

                                                <span class="small text-secondary" id="event-url">
                                                    <?php echo htmlspecialchars($eventUrl); ?>
                                                </span>
                                            </div>

                                            <!-- Your date/time/location fields remain unchanged -->
                                            <!-- (Not repeating them here for brevity) -->
                                        </div>
                                    </div>

                                    <!-- Event Tags -->
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="fw-bold mb-0">Event Tags</h5>
                                            <button class="btn btn-warning text-white btn-sm" type="button"
                                                data-bs-toggle="modal" data-bs-target="#tagPickerModal">
                                                Add Event Tags
                                            </button>
                                        </div>

                                        <div class="card-body pt-2">
                                            <div class="d-flex flex-wrap gap-2">
                                                <div id="eventTagBadges" class="d-flex flex-wrap gap-2"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Event Details (Quill) -->
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="fw-bold mb-0">Event Details</h5>
                                        </div>

                                        <div class="card-body pt-0">
                                            <div class="list-group list-group-flush">
                                                <div id="editor" style="height: 300px;"></div>
                                                <input type="hidden" name="event_description" id="event_description">
                                            </div>
                                        </div>
                                    </div>

                                </div>


                                <!-- ========================= -->
                                <!-- RIGHT COLUMN (Poster Preview) -->
                                <!-- ========================= -->
                                <div class="col-xl-4 mb-4">
                                    <div class="card mb-4">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="fw-bold mb-0">Event Poster</h5>
                                        </div>

                                        <div class="card-body pt-2">
                                            <div id="posterPreview" class="mt-3 d-none">
                                                <img id="posterPreviewImg" src="" class="img-fluid rounded"
                                                    style="max-height: 240px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-12 d-flex justify-content-center">
                                    <button type="submit" class="btn btn-primary mt-3">Save Event</button>
                                </div>
                            </div>
                            <!-- Start: Footer Centered -->
                            <?php include '_include/inner-footer.php'; ?>
                            <!-- End: Footer Centered -->
                    </main>
                </div>
            </div>
        </div>
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

                        <div id="mediaGrid"></div>

                    </div>

                    <div class="modal-footer">
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

                        <input type="text" id="tagSearchInput" class="form-control"
                            placeholder="Search or create tags…">

                        <div id="tagSearchResults" class="list-group mt-3"></div>

                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <input type="hidden" id="hiddenTags" name="tags">

                    </div>

                </div>
            </div>
        </div>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script>
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

        // 1️⃣ Initialize Quill
        const quill = new Quill("#editor", {
            theme: "snow",
            modules: {
                toolbar: fullToolbar
            }
        });

        // 2️⃣ Override the image button to open your Media Library modal
        const toolbar = quill.getModule("toolbar");
        toolbar.addHandler("image", function() {
            openZentraMediaLibraryModal(); // <-- your modal function
        });

        // 3️⃣ Sync Quill content before form submit
        function syncQuillContent() {
            const html = quill.root.innerHTML;
            document.getElementById("event_description").value = html;
            return true;
        }

        function openZentraMediaLibraryModal() {
            const modal = new bootstrap.Modal(document.getElementById('zentraMediaModal'));
            modal.show();
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
        dropzone.addEventListener('click', () => fileInput.click());

        // File selected
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                uploadPoster(fileInput.files[0]);
            }
        });

        // DRAG OVER
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('drag-over');
        });

        // DRAG LEAVE
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('drag-over');
        });

        // DROP FILE
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('drag-over');

            if (e.dataTransfer.files.length > 0) {
                uploadPoster(e.dataTransfer.files[0]);
            }
        });

        // UPLOAD FUNCTION
        function uploadPoster(file) {
            if (!file.type.match(/image\/(png|jpeg)/)) {
                showPosterError(data.error || "Upload failed - Invalid file type. Only PNG or JPG allowed");
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                showPosterError(data.error || "Upload failed - File too large. Max size is 2 MB");
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            fetch('/api/v1/media/upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        posterMediaIdInput.value = data.media_id;
                        previewImg.src = data.url;
                        preview.classList.remove('d-none');
                        showPosterSuccess("Poster Image uploaded successfully");
                    } else {
                        showPosterError(data.error || "Upload failed");

                    }
                })
                .catch(err => {
                    console.error(err);
                    showPosterError(data.error || "Upload error occurred");
                });
        }
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

    <?php include '_include/body_end_plugins.php'; ?>
</body>

</html>