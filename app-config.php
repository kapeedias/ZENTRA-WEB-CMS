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

?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo getenv('APP_NAME') ?> - App Configuration</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css?h=283928673d7441cd64f1af3db9200eab">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Geist:400,700&amp;display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css">
    <link rel="stylesheet" href="assets/css/styles.min.css?h=8185e874b995279ecb2a9d7bb55b09b0">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
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
                        <p>Paragraph</p><!-- Start: Table Card -->
                        <div class="card">
                            <div
                                class="card-header d-flex justify-content-between align-items-center flex-wrap flex-xl-nowrap pb-0 py-3">
                                <h5 class="fw-bold w-100 mb-3 mb-xl-0">App Configurations</h5>
                                <form class="position-relative flex-grow-1 flex-shrink-0 flex-xl-grow-0 ms-auto"><input
                                        class="form-control pe-4" type="search" placeholder="Search"
                                        name="searchAppConfig"><button
                                        class="btn border-0 position-absolute top-50 end-0 translate-middle-y"
                                        type="submit"><i class="fa fa-search"></i></button></form>
                                <div class="dropdown"><button class="btn btn-link" data-bs-toggle="dropdown"
                                        aria-expanded="false" type="button"><i class="fa fa-ellipsis-v"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end shadow"><a class="dropdown-item"
                                            href="#">7
                                            Days</a><a class="dropdown-item" href="#">30 Days</a>
                                        <div class="dropdown-divider"></div><a class="dropdown-item" href="#">Custom
                                            Period</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" id="AppConfigData">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Status</th>
                                                <th>Lifetime Value</th>
                                                <th>Join Date</th>
                                                <th width="40px"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr valign="middle">
                                                <td><a class="text-decoration-none d-flex align-items-center gap-2"
                                                        href="#"><img
                                                            class="img-fluid aspect-ratio-1x1 object-fit-cover rounded-circle shadow-sm"
                                                            src="assets/img/team/avatar1.jpg?h=fc3130ca16c6d3ee2009fd4450b80205"
                                                            alt="Customer" width="40" height="40">
                                                        <div>
                                                            <p class="fw-bold mb-0">Joanna
                                                                Prince</p><small
                                                                class="text-secondary d-block">Marketing
                                                                Manager</small>
                                                        </div>
                                                    </a></td>
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
                                            <tr valign="middle">
                                                <td><a class="text-decoration-none d-flex align-items-center gap-2"
                                                        href="#"><img
                                                            class="img-fluid aspect-ratio-1x1 object-fit-cover rounded-circle shadow-sm"
                                                            src="assets/img/team/avatar2.jpg?h=7086b181e9fb853914a2cca97301c640"
                                                            alt="Customer" width="40" height="40">
                                                        <div>
                                                            <p class="fw-bold mb-0">Mike
                                                                Johnson</p><small class="text-secondary d-block">CTO,
                                                                Corpy
                                                                Corp</small>
                                                        </div>
                                                    </a></td>
                                                <td><span class="badge bg-light d-inline-flex gap-1"><i
                                                            class="material-icons text-warning">pause_circle_filled</i>&nbsp;Paused</span>
                                                </td>
                                                <td>$9,123.45</td>
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
                                            <tr valign="middle">
                                                <td><a class="text-decoration-none d-flex align-items-center gap-2"
                                                        href="#"><img
                                                            class="img-fluid aspect-ratio-1x1 object-fit-cover rounded-circle shadow-sm"
                                                            src="assets/img/team/avatar3.jpg?h=d00658bdbe17fa68ec776823ea82e9c1"
                                                            alt="Customer" width="40" height="40">
                                                        <div>
                                                            <p class="fw-bold mb-0">Joanna
                                                                Prince</p><small
                                                                class="text-secondary d-block">Marketing
                                                                Manager</small>
                                                        </div>
                                                    </a></td>
                                                <td><span class="badge bg-light d-inline-flex gap-1"><i
                                                            class="material-icons text-success">check_circle</i>&nbsp;Active</span>
                                                </td>
                                                <td>$423.45</td>
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
                                            <tr valign="middle">
                                                <td><a class="text-decoration-none d-flex align-items-center gap-2"
                                                        href="#"><img
                                                            class="img-fluid aspect-ratio-1x1 object-fit-cover rounded-circle shadow-sm"
                                                            src="assets/img/team/avatar4.jpg?h=13fcb1a3bcb58463519bc5974513259b"
                                                            alt="Customer" width="40" height="40">
                                                        <div>
                                                            <p class="fw-bold mb-0">Mike
                                                                Johnson</p><small class="text-secondary d-block">CTO,
                                                                Corpy
                                                                Corp</small>
                                                        </div>
                                                    </a></td>
                                                <td><span class="badge bg-light d-inline-flex gap-1"><i
                                                            class="material-icons text-danger">cancel</i>&nbsp;Canceled</span>
                                                </td>
                                                <td>$523.45</td>
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
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div><!-- End: Table Card -->
                    </div><!-- End: main content -->
                    <!-- Start: Footer Centered -->
                    <footer class="text-center py-5"><a
                            class="text-decoration-none link-body-emphasis d-inline-flex align-items-center"
                            href="#"><span class="fs-4 fw-bold brand-primary">ZENTRA</span><span
                                class="fs-4 brand-secondary">CMS</span></a>
                        <div class="d-flex justify-content-center align-items-center flex-wrap mb-2"><a
                                class="link-body-emphasis mx-2" href="#">Privacy Policy</a><a
                                class="link-body-emphasis mx-2" href="#">Terms of Service</a><a
                                class="link-body-emphasis mx-2" href="#">Cookie Policy</a></div>
                        <p class="text-muted mb-2">© 2026 Brand. All rights
                            reserved.</p>
                    </footer><!-- End: Footer Centered -->
                </main>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/4.0.0/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.min.js?h=76fb943b07981bddcd684084e3798cff"></script>
    <script>
    const table = $('#AppConfigData').DataTable({
        pageLength: 5,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: true,
        autoWidth: false, // IMPORTANT
        scrollX: false, // IMPORTANT (prevents forced horizontal scroll)
        dom: '<"row align-items-center mb-2"<"col-md-6"l>>' +
            'rt' +
            '<"row align-items-center mt-2"<"col-md-6"i><"col-md-6 d-flex justify-content-end"p>>'
    });

    let timer;
    $('input[name="searchAppConfig"]').on('input', function() {
        clearTimeout(timer);
        const value = this.value;

        timer = setTimeout(() => {
            table.search(value).draw();
        }, 150);
    });
    </script>
</body>

</html>