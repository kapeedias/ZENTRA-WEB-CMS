<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Dashboard - Brand</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css?h=283928673d7441cd64f1af3db9200eab">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Geist:400,700&amp;display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.min.css?h=9b0897f2ce3ff19c63159749511b5e0d">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.1/dist/bootstrap-table.min.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <?php include('_include/nav_side.php'); ?>
            <div class="col-md-9 col-xl-10 bg-body-tertiary px-0">
                <div class="d-md-none p-2 sticky-top">
                    <?php include('_include/nav_top_branding.php'); ?>
                </div>
                <main class="px-3 px-md-4">
                    <!-- Start: top-nav-and-details -->
                    <div
                        class="d-flex flex-column justify-content-between flex-xl-row-reverse align-items-xl-start pt-3 mb-3 border-bottom">
                        <div class="d-flex align-items-center mb-3 mb-xl-0">
                            <form class="position-relative flex-grow-1 me-1"><input class="form-control pe-4"
                                    type="search" placeholder="Search" name="search"><button
                                    class="btn border-0 position-absolute top-50 end-0 translate-middle-y"
                                    type="submit"><svg class="bi bi-search" xmlns="http://www.w3.org/2000/svg"
                                        width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                        <path
                                            d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0">
                                        </path>
                                    </svg></button></form>
                            <div class="theme-switcher dropdown"><button class="btn btn-link dropdown-toggle px-2"
                                    data-bs-toggle="dropdown" aria-expanded="false" type="button"><svg
                                        class="bi bi-sun-fill mb-1" xmlns="http://www.w3.org/2000/svg" width="1em"
                                        height="1em" fill="currentColor" viewBox="0 0 16 16">
                                        <path
                                            d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708">
                                        </path>
                                    </svg></button>
                                <div class="dropdown-menu dropdown-menu-end"><a
                                        class="dropdown-item d-flex align-items-center" href="#"
                                        data-bs-theme-value="light"><svg class="bi bi-sun-fill me-2 opacity-50"
                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8M8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0m0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13m8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5M3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8m10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0m-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0m9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707M4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708">
                                            </path>
                                        </svg>Light</a><a class="dropdown-item d-flex align-items-center" href="#"
                                        data-bs-theme-value="dark"><svg class="bi bi-moon-stars-fill me-2 opacity-50"
                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M6 .278a.77.77 0 0 1 .08.858 7.2 7.2 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277q.792-.001 1.533-.16a.79.79 0 0 1 .81.316.73.73 0 0 1-.031.893A8.35 8.35 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.75.75 0 0 1 6 .278">
                                            </path>
                                            <path
                                                d="M10.794 3.148a.217.217 0 0 1 .412 0l.387 1.162c.173.518.579.924 1.097 1.097l1.162.387a.217.217 0 0 1 0 .412l-1.162.387a1.73 1.73 0 0 0-1.097 1.097l-.387 1.162a.217.217 0 0 1-.412 0l-.387-1.162A1.73 1.73 0 0 0 9.31 6.593l-1.162-.387a.217.217 0 0 1 0-.412l1.162-.387a1.73 1.73 0 0 0 1.097-1.097zM13.863.099a.145.145 0 0 1 .274 0l.258.774c.115.346.386.617.732.732l.774.258a.145.145 0 0 1 0 .274l-.774.258a1.16 1.16 0 0 0-.732.732l-.258.774a.145.145 0 0 1-.274 0l-.258-.774a1.16 1.16 0 0 0-.732-.732l-.774-.258a.145.145 0 0 1 0-.274l.774-.258c.346-.115.617-.386.732-.732z">
                                            </path>
                                        </svg>Dark</a><a class="dropdown-item d-flex align-items-center" href="#"
                                        data-bs-theme-value="auto"><svg class="bi bi-circle-half me-2 opacity-50"
                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 0 8 1zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16"></path>
                                        </svg>Auto</a></div>
                            </div>
                            <div class="dropdown"><button class="btn dropdown-toggle border-0 p-2"
                                    data-bs-toggle="dropdown" aria-expanded="false" type="button"><img
                                        class="object-fit-cover border rounded-circle"
                                        src="../assets/img/team/avatar2.jpg?h=7086b181e9fb853914a2cca97301c640"
                                        width="32" height="32"></button>
                                <div class="dropdown-menu dropdown-menu-end shadow"><a class="dropdown-item"
                                        href="#"><svg class="bi bi-person me-2" xmlns="http://www.w3.org/2000/svg"
                                            width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z">
                                            </path>
                                        </svg>&nbsp;Profile</a><a class="dropdown-item" href="#"><svg
                                            class="bi bi-gear me-2" xmlns="http://www.w3.org/2000/svg" width="1em"
                                            height="1em" fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0">
                                            </path>
                                            <path
                                                d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z">
                                            </path>
                                        </svg>&nbsp;Settings</a><a class="dropdown-item" href="#"><svg
                                            class="bi bi-list-nested me-2" xmlns="http://www.w3.org/2000/svg"
                                            width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M4.5 11.5A.5.5 0 0 1 5 11h10a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5m-2-4A.5.5 0 0 1 3 7h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m-2-4A.5.5 0 0 1 1 3h10a.5.5 0 0 1 0 1H1a.5.5 0 0 1-.5-.5">
                                            </path>
                                        </svg>&nbsp;Activity log</a>
                                    <div class="dropdown-divider"></div><a class="dropdown-item link-danger"
                                        href="#"><svg class="bi bi-box-arrow-right me-2"
                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z">
                                            </path>
                                            <path fill-rule="evenodd"
                                                d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z">
                                            </path>
                                        </svg>&nbsp;Logout</a>
                                </div>
                            </div>
                        </div>
                        <div>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#"><span>Home</span></a></li>
                                <li class="breadcrumb-item"><a href="#"><span>Examples</span></a></li>
                                <li class="breadcrumb-item active"><span>Documents</span></li>
                            </ol>
                            <h1 class="h2">Documents</h1>
                        </div>
                    </div><!-- End: top-nav-and-details -->
                    <!-- Start: main content -->
                    <div>
                        <p>Paragraph</p>
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
                        <p class="text-muted mb-2">© 2026 Brand. All rights reserved.</p>
                        <div class="fs-4 d-flex justify-content-center align-items-center gap-2 mb-2"><a
                                class="link-body-emphasis" href="#"><svg
                                    class="icon icon-tabler icon-tabler-brand-instagram text-muted"
                                    xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path
                                        d="M4 4m0 4a4 4 0 0 1 4 -4h8a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-8a4 4 0 0 1 -4 -4z">
                                    </path>
                                    <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                    <path d="M16.5 7.5l0 .01"></path>
                                </svg></a><a class="link-body-emphasis" href="#"><svg
                                    class="icon icon-tabler icon-tabler-brand-x text-muted"
                                    xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path d="M4 4l11.733 16h4.267l-11.733 -16z"></path>
                                    <path d="M4 20l6.768 -6.768m2.46 -2.46l6.772 -6.772"></path>
                                </svg></a><a class="link-body-emphasis" href="#"><svg
                                    class="icon icon-tabler icon-tabler-brand-tiktok text-muted"
                                    xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                    <path
                                        d="M21 7.917v4.034a9.948 9.948 0 0 1 -5 -1.951v4.5a6.5 6.5 0 1 1 -8 -6.326v4.326a2.5 2.5 0 1 0 4 2v-11.5h4.083a6.005 6.005 0 0 0 4.917 4.917z">
                                    </path>
                                </svg></a></div>
                    </footer><!-- End: Footer Centered -->
                </main>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/4.0.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.27.1/dist/bootstrap-table.min.js"></script>
    <script src="../assets/js/script.min.js?h=76fb943b07981bddcd684084e3798cff"></script>
</body>

</html>