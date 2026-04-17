<?php
    secureSessionStart();

    // ==== CONFIG & DEPENDENCIES ====
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/init.php';
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/config/helpers.php';
    require_once __DIR__ . '/classes/User.php';

    enforceSessionSecurity();
    $ip = getClientIP();
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?php echo getenv('APP_NAME') ?> - Login</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css?h=283928673d7441cd64f1af3db9200eab">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Geist:400,700&amp;display=swap">
    <link rel="stylesheet" href="assets/css/styles.min.css?h=3a29c92ea4137926cb7ee989224f5bff">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
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

                            <div class="dropdown"><button class="btn dropdown-toggle border-0 p-2"
                                    data-bs-toggle="dropdown" aria-expanded="false" type="button"><img
                                        class="object-fit-cover border rounded-circle"
                                        src="assets/img/team/avatar2.jpg?h=7086b181e9fb853914a2cca97301c640" width="32"
                                        height="32"></button>
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
                                        href="logout.php"><svg class="bi bi-box-arrow-right me-2"
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
                            <h1 class="h2">Welcome <?php echo $_SESSION['user_email']; ?></h1>
                        </div>
                    </div>
                    <div>
                        <div class="row">
                            <div class="col-md-6 col-xl-3 mb-4">
                                <!-- Start: Stat Card -->
                                <div class="card">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="text-muted small"><span>Total Revenue</span></div>
                                                <div class="mb-0 h4"><span>$1,250.00</span></div>
                                            </div><span class="badge bg-success shadow-sm"><svg
                                                    class="icon icon-tabler icon-tabler-trending-up"
                                                    xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M3 17l6 -6l4 4l8 -8"></path>
                                                    <path d="M14 7l7 0l0 7"></path>
                                                </svg>&nbsp;+12.5% </span>
                                        </div>
                                        <div class="text-muted mt-2 small"><span>Trending up this month</span></div>
                                    </div>
                                </div><!-- End: Stat Card -->
                            </div>
                            <div class="col-md-6 col-xl-3 mb-4">
                                <div class="card">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="text-muted small"><span>New Customers</span></div>
                                                <div class="mb-0 h4"><span>1,234</span></div>
                                            </div><span class="badge bg-danger shadow-sm"> <svg
                                                    class="icon icon-tabler icon-tabler-trending-down"
                                                    xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M3 7l6 6l4 -4l8 8"></path>
                                                    <path d="M21 10l0 7l-7 0"></path>
                                                </svg>&nbsp;-20% </span>
                                        </div>
                                        <div class="text-muted mt-2 small"><span>Down 20% this period</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-3 mb-4">
                                <div class="card">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="text-muted small"><span>Active Accounts</span></div>
                                                <div class="mb-0 h4"><span>45,678</span></div>
                                            </div><span class="badge bg-success shadow-sm"><svg
                                                    class="icon icon-tabler icon-tabler-trending-up"
                                                    xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M3 17l6 -6l4 4l8 -8"></path>
                                                    <path d="M14 7l7 0l0 7"></path>
                                                </svg>&nbsp;+12.5% </span>
                                        </div>
                                        <div class="text-muted mt-2 small"><span>Strong user retention</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-xl-3 mb-4">
                                <div class="card">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="text-muted small"><span>Growth Rate</span></div>
                                                <div class="mb-0 h4"><span>4.5%</span></div>
                                            </div><span class="badge bg-success shadow-sm"><svg
                                                    class="icon icon-tabler icon-tabler-trending-up"
                                                    xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                                    fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                                    <path d="M3 17l6 -6l4 4l8 -8"></path>
                                                    <path d="M14 7l7 0l0 7"></path>
                                                </svg> +4.5% </span>
                                        </div>
                                        <div class="text-muted mt-2 small"><span>Steady performance increase</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- Start: Chart Card -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <h5 class="mb-0 card-title">Total Visitors</h5>
                                </div>
                                <div class="btn-group" role="group"><button class="btn btn-light btn-sm"
                                        type="button">30 Days</button><button class="btn btn-light btn-sm"
                                        type="button">60 Days</button><button class="btn btn-primary btn-sm"
                                        type="button">90 Days</button></div>
                            </div>
                            <div class="card-body">
                                <div><canvas
                                        data-bss-chart="{&quot;type&quot;:&quot;line&quot;,&quot;data&quot;:{&quot;labels&quot;:[&quot;January&quot;,&quot;February&quot;,&quot;March&quot;,&quot;April&quot;,&quot;May&quot;,&quot;June&quot;],&quot;datasets&quot;:[{&quot;label&quot;:&quot;Revenue&quot;,&quot;backgroundColor&quot;:&quot;rgba(85,153,255,0.3)&quot;,&quot;borderColor&quot;:&quot;rgb(85,153,255)&quot;,&quot;data&quot;:[&quot;4500&quot;,&quot;7600&quot;,&quot;6550&quot;,&quot;7800&quot;,&quot;6800&quot;,&quot;9000&quot;],&quot;fill&quot;:true,&quot;borderWidth&quot;:&quot;0&quot;}]},&quot;options&quot;:{&quot;legend&quot;:{&quot;display&quot;:false,&quot;labels&quot;:{&quot;fontStyle&quot;:&quot;normal&quot;}},&quot;title&quot;:{&quot;fontStyle&quot;:&quot;bold&quot;},&quot;aspectRatio&quot;:2,&quot;maintainAspectRatio&quot;:true,&quot;scales&quot;:{&quot;xAxes&quot;:[{&quot;gridLines&quot;:{&quot;color&quot;:&quot;rgba(152,152,152,0.15)&quot;,&quot;zeroLineColor&quot;:&quot;rgba(152,152,152,0.15)&quot;,&quot;drawBorder&quot;:false,&quot;drawTicks&quot;:false,&quot;drawOnChartArea&quot;:false},&quot;ticks&quot;:{&quot;fontColor&quot;:&quot;#999&quot;,&quot;fontStyle&quot;:&quot;normal&quot;,&quot;beginAtZero&quot;:false,&quot;padding&quot;:10}}],&quot;yAxes&quot;:[{&quot;gridLines&quot;:{&quot;color&quot;:&quot;rgba(152,152,152,0.15)&quot;,&quot;zeroLineColor&quot;:&quot;rgba(152,152,152,0.15)&quot;,&quot;drawBorder&quot;:false,&quot;drawTicks&quot;:false,&quot;drawOnChartArea&quot;:true},&quot;ticks&quot;:{&quot;fontColor&quot;:&quot;#999&quot;,&quot;fontStyle&quot;:&quot;normal&quot;,&quot;beginAtZero&quot;:false,&quot;padding&quot;:10}}]}}}"></canvas>
                                </div>
                            </div>
                        </div><!-- End: Chart Card -->
                        <div class="row gy-4 row-cols-1 row-cols-lg-2 mb-4">
                            <div class="col">
                                <!-- Start: Calendar Card -->
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center pb-3"><button
                                                class="btn" type="button"><svg class="bi bi-chevron-left"
                                                    xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                    fill="currentColor" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd"
                                                        d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0">
                                                    </path>
                                                </svg></button>
                                            <h5 class="mb-0">August 2025</h5><button class="btn" type="button"><svg
                                                    class="bi bi-chevron-right" xmlns="http://www.w3.org/2000/svg"
                                                    width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                    <path fill-rule="evenodd"
                                                        d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708">
                                                    </path>
                                                </svg></button>
                                        </div>
                                        <div class="lh-lg">
                                            <table class="w-100 text-center">
                                                <thead>
                                                    <tr class="text-muted small">
                                                        <th>Su</th>
                                                        <th>Mo</th>
                                                        <th>Tu</th>
                                                        <th>We</th>
                                                        <th>Th</th>
                                                        <th>Fr</th>
                                                        <th>Sa</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-body-tertiary">27</td>
                                                        <td class="text-body-tertiary">28</td>
                                                        <td class="text-body-tertiary">29</td>
                                                        <td class="text-body-tertiary">30</td>
                                                        <td class="text-body-tertiary">31</td>
                                                        <td>1</td>
                                                        <td>2</td>
                                                    </tr>
                                                    <tr>
                                                        <td>3</td>
                                                        <td>4</td>
                                                        <td>5</td>
                                                        <td>6</td>
                                                        <td>7</td>
                                                        <td>8</td>
                                                        <td>9</td>
                                                    </tr>
                                                    <tr>
                                                        <td>10</td>
                                                        <td>11</td>
                                                        <td>12</td>
                                                        <td><span
                                                                class="badge bg-primary position-absolute translate-middle">13</span>
                                                        </td>
                                                        <td>14</td>
                                                        <td>15</td>
                                                        <td>16</td>
                                                    </tr>
                                                    <tr>
                                                        <td>17</td>
                                                        <td>18</td>
                                                        <td>19</td>
                                                        <td>20</td>
                                                        <td>21</td>
                                                        <td>22</td>
                                                        <td>23</td>
                                                    </tr>
                                                    <tr>
                                                        <td>24</td>
                                                        <td>25</td>
                                                        <td>26</td>
                                                        <td>27</td>
                                                        <td>28</td>
                                                        <td>29</td>
                                                        <td>30</td>
                                                    </tr>
                                                    <tr>
                                                        <td>31</td>
                                                        <td class="text-body-tertiary">1</td>
                                                        <td class="text-body-tertiary">2</td>
                                                        <td class="text-body-tertiary">3</td>
                                                        <td class="text-body-tertiary">4</td>
                                                        <td class="text-body-tertiary">5</td>
                                                        <td class="text-body-tertiary">6</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div><!-- End: Calendar Card -->
                            </div>
                            <div class="col">
                                <!-- Start: Form Card -->
                                <div class="card">
                                    <div class="card-header pb-0 py-3">
                                        <h5 class="mb-1 card-title">Subscribe to our newsletter</h5>
                                        <p class="text-muted card-text small">Opt-in to receive updates and news about
                                            the sidebar.</p>
                                    </div>
                                    <div class="card-body">
                                        <form>
                                            <div class="d-flex flex-column gap-2"><input
                                                    class="form-control form-control" type="email"
                                                    placeholder="Email"><button class="btn btn-primary"
                                                    type="submit">Subscribe</button></div>
                                        </form>
                                    </div>
                                </div><!-- End: Form Card -->
                            </div>
                            <div class="col">
                                <!-- Start: Pie Chart Card -->
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center py-3">
                                        <h5 class="text-body m-0 fw-bold">Revenue Sources</h5>
                                        <div class="dropdown"><button class="btn btn-link" data-bs-toggle="dropdown"
                                                aria-expanded="false" type="button"><svg
                                                    class="bi bi-three-dots-vertical" xmlns="http://www.w3.org/2000/svg"
                                                    width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                    <path
                                                        d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0">
                                                    </path>
                                                </svg></button>
                                            <div class="dropdown-menu dropdown-menu-end shadow"><a class="dropdown-item"
                                                    href="#">7 Days</a><a class="dropdown-item" href="#">30 Days</a>
                                                <div class="dropdown-divider"></div><a class="dropdown-item"
                                                    href="#">Custom Period</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div><canvas
                                                data-bss-chart="{&quot;type&quot;:&quot;pie&quot;,&quot;data&quot;:{&quot;labels&quot;:[&quot;Direct&quot;,&quot;Social&quot;,&quot;Referral&quot;],&quot;datasets&quot;:[{&quot;label&quot;:&quot;&quot;,&quot;backgroundColor&quot;:[&quot;#4e73df&quot;,&quot;#1cc88a&quot;,&quot;#36b9cc&quot;],&quot;borderColor&quot;:[&quot;#ffffff&quot;,&quot;#ffffff&quot;,&quot;#ffffff&quot;],&quot;data&quot;:[&quot;50&quot;,&quot;30&quot;,&quot;15&quot;]}]},&quot;options&quot;:{&quot;legend&quot;:{&quot;display&quot;:true,&quot;labels&quot;:{&quot;fontStyle&quot;:&quot;normal&quot;},&quot;position&quot;:&quot;bottom&quot;},&quot;title&quot;:{&quot;fontStyle&quot;:&quot;normal&quot;},&quot;aspectRatio&quot;:2,&quot;maintainAspectRatio&quot;:false}}"></canvas>
                                        </div>
                                    </div>
                                </div><!-- End: Pie Chart Card -->
                            </div>
                            <div class="col">
                                <!-- Start: List Card -->
                                <div class="card">
                                    <div
                                        class="card-header d-flex justify-content-between align-items-center pb-0 py-3">
                                        <h5 class="m-0 fw-bold">Transactions</h5>
                                        <div class="dropdown"><button class="btn btn-link" data-bs-toggle="dropdown"
                                                aria-expanded="false" type="button"><svg
                                                    class="bi bi-three-dots-vertical" xmlns="http://www.w3.org/2000/svg"
                                                    width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                    <path
                                                        d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0">
                                                    </path>
                                                </svg></button>
                                            <div class="dropdown-menu dropdown-menu-end shadow"><a class="dropdown-item"
                                                    href="#">7 Days</a><a class="dropdown-item" href="#">30 Days</a>
                                                <div class="dropdown-divider"></div><a class="dropdown-item"
                                                    href="#">Custom Period</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <ul class="m-0 p-0">
                                            <li class="d-flex align-items-center mb-3">
                                                <div class="lh-1 bg-info-subtle rounded-circle p-2 me-3"><svg
                                                        class="bi bi-paypal fs-5" xmlns="http://www.w3.org/2000/svg"
                                                        width="1em" height="1em" fill="currentColor"
                                                        viewBox="0 0 16 16">
                                                        <path
                                                            d="M14.06 3.713c.12-1.071-.093-1.832-.702-2.526C12.628.356 11.312 0 9.626 0H4.734a.7.7 0 0 0-.691.59L2.005 13.509a.42.42 0 0 0 .415.486h2.756l-.202 1.28a.628.628 0 0 0 .62.726H8.14c.429 0 .793-.31.862-.731l.025-.13.48-3.043.03-.164.001-.007a.35.35 0 0 1 .348-.297h.38c1.266 0 2.425-.256 3.345-.91q.57-.403.993-1.005a4.94 4.94 0 0 0 .88-2.195c.242-1.246.13-2.356-.57-3.154a2.7 2.7 0 0 0-.76-.59l-.094-.061ZM6.543 8.82a.7.7 0 0 1 .321-.079H8.3c2.82 0 5.027-1.144 5.672-4.456l.003-.016q.326.186.548.438c.546.623.679 1.535.45 2.71-.272 1.397-.866 2.307-1.663 2.874-.802.57-1.842.815-3.043.815h-.38a.87.87 0 0 0-.863.734l-.03.164-.48 3.043-.024.13-.001.004a.35.35 0 0 1-.348.296H5.595a.106.106 0 0 1-.105-.123l.208-1.32z">
                                                        </path>
                                                    </svg></div>
                                                <div
                                                    class="d-flex w-100 justify-content-between align-items-center flex-wrap gap-2">
                                                    <div class="me-2"><small class="text-muted d-block">Paypal</small>
                                                        <p class="mb-0">Acme Widget 2000</p>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <p class="mb-0">82.6</p><span
                                                            class="text-body-secondary">USD</span>
                                                    </div>
                                                </div>
                                            </li>
                                            <li class="d-flex align-items-center mb-3">
                                                <div class="lh-1 bg-success-subtle rounded-circle p-2 me-3"><svg
                                                        class="bi bi-credit-card fs-5"
                                                        xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                        fill="currentColor" viewBox="0 0 16 16">
                                                        <path
                                                            d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z">
                                                        </path>
                                                        <path
                                                            d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z">
                                                        </path>
                                                    </svg></div>
                                                <div
                                                    class="d-flex w-100 justify-content-between align-items-center flex-wrap gap-2">
                                                    <div class="me-2"><small class="text-muted d-block">Credit
                                                            Card</small>
                                                        <p class="mb-0">Acme Pack Ultra</p>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <p class="mb-0">182.6</p><span
                                                            class="text-body-secondary">USD</span>
                                                    </div>
                                                </div>
                                            </li>
                                            <li class="d-flex align-items-center mb-3">
                                                <div class="lh-1 bg-danger-subtle rounded-circle p-2 me-3"><svg
                                                        class="bi bi-credit-card fs-5"
                                                        xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                        fill="currentColor" viewBox="0 0 16 16">
                                                        <path
                                                            d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z">
                                                        </path>
                                                        <path
                                                            d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z">
                                                        </path>
                                                    </svg></div>
                                                <div
                                                    class="d-flex w-100 justify-content-between align-items-center flex-wrap gap-2">
                                                    <div class="me-2"><small class="text-muted d-block">Credit
                                                            Card</small>
                                                        <p class="mb-0">Refund for Acme Pack Ultra</p>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <p class="text-danger mb-0">-182.6</p><span
                                                            class="text-danger">USD</span>
                                                    </div>
                                                </div>
                                            </li>
                                            <li class="d-flex align-items-center">
                                                <div class="lh-1 bg-secondary-subtle rounded-circle p-2 me-3"><svg
                                                        class="bi bi-bank fs-5" xmlns="http://www.w3.org/2000/svg"
                                                        width="1em" height="1em" fill="currentColor"
                                                        viewBox="0 0 16 16">
                                                        <path
                                                            d="m8 0 6.61 3h.89a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5H15v7a.5.5 0 0 1 .485.38l.5 2a.498.498 0 0 1-.485.62H.5a.498.498 0 0 1-.485-.62l.5-2A.5.5 0 0 1 1 13V6H.5a.5.5 0 0 1-.5-.5v-2A.5.5 0 0 1 .5 3h.89zM3.777 3h8.447L8 1zM2 6v7h1V6zm2 0v7h2.5V6zm3.5 0v7h1V6zm2 0v7H12V6zM13 6v7h1V6zm2-1V4H1v1zm-.39 9H1.39l-.25 1h13.72z">
                                                        </path>
                                                    </svg></div>
                                                <div
                                                    class="d-flex w-100 justify-content-between align-items-center flex-wrap gap-2">
                                                    <div class="me-2"><small class="text-muted d-block">Wire
                                                            Transfer</small>
                                                        <p class="mb-0">Acme Pack Ultra</p>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <p class="mb-0">182.6</p><span
                                                            class="text-body-secondary">USD</span>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div><!-- End: List Card -->
                            </div>
                            <div class="col">
                                <!-- Start: Gallery Card -->
                                <div class="card">
                                    <div class="card-header pb-0 py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">Gallery</h5>
                                            <div class="swiper-nav-standalone d-flex"
                                                data-bss-swiper-target="#swiper-tips">
                                                <div class="swiper-button-prev"><button
                                                        class="btn btn-light btn-sm me-2" type="button"><svg
                                                            class="bi bi-chevron-left"
                                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            fill="currentColor" viewBox="0 0 16 16">
                                                            <path fill-rule="evenodd"
                                                                d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0">
                                                            </path>
                                                        </svg></button></div>
                                                <div class="swiper-button-next"><button class="btn btn-light btn-sm"
                                                        type="button"><svg class="bi bi-chevron-right"
                                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            fill="currentColor" viewBox="0 0 16 16">
                                                            <path fill-rule="evenodd"
                                                                d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708">
                                                            </path>
                                                        </svg></button></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="swiper" id="swiper-tips"
                                            data-bss-swiper="{&quot;direction&quot;:&quot;horizontal&quot;,&quot;effect&quot;:&quot;slide&quot;,&quot;pagination&quot;:{&quot;el&quot;:&quot;.swiper-pagination&quot;,&quot;type&quot;:&quot;bullets&quot;}}">
                                            <div class="swiper-wrapper pb-2">
                                                <div class="swiper-slide"><img
                                                        class="img-fluid aspect-ratio-16x9 object-fit-cover rounded-4 w-100 mb-3"
                                                        alt="Slide Image" width="1080" height="720"
                                                        src="assets/img/photos/photo-2.jpg?h=5188cdbb00c2a6bcb3044fdb0e23672e">
                                                    <h4>Desert Sands</h4>
                                                    <p>Quam rutrum justo consectetur quam, sed congue cursus tincidunt
                                                        cursus ad. Ligula ante magna euismod, dui habitasse per etiam.
                                                        Vehicula dui fringilla egestas dolor.</p>
                                                </div>
                                                <div class="swiper-slide"><img
                                                        class="img-fluid aspect-ratio-16x9 object-fit-cover rounded-4 w-100 mb-3"
                                                        alt="Slide Image" width="1080" height="720"
                                                        src="assets/img/photos/photo-1.jpg?h=94902987b50503cd580b482c806f9735">
                                                    <h4>Beach Sunset</h4>
                                                    <p>Quam rutrum justo consectetur quam, sed congue cursus tincidunt
                                                        cursus ad. Ligula ante magna euismod, dui habitasse per etiam.
                                                        Vehicula dui fringilla egestas dolor.</p>
                                                </div>
                                            </div>
                                            <div class="swiper-pagination bottom-0"></div>
                                        </div>
                                    </div>
                                </div><!-- End: Gallery Card -->
                            </div>
                        </div><!-- Start: Tabs Card -->
                        <div class="card mb-4">
                            <div>
                                <ul class="nav nav-tabs card-header" role="tablist">
                                    <li class="nav-item" role="presentation"><a class="nav-link active" role="tab"
                                            data-bs-toggle="tab" href="#tab-1">Pages</a></li>
                                    <li class="nav-item" role="presentation"><a class="nav-link" role="tab"
                                            data-bs-toggle="tab" href="#tab-2">Need Review<span
                                                class="badge bg-secondary ms-1">3</span></a></li>
                                    <li class="nav-item" role="presentation"><a class="nav-link" role="tab"
                                            data-bs-toggle="tab" href="#tab-3">Deleted</a></li>
                                </ul>
                                <div class="tab-content pt-0 card-body">
                                    <div class="tab-pane active" role="tabpanel" id="tab-1"><button
                                            class="btn btn-primary btn-sm d-flex my-2 tab-card-btn" type="button"><svg
                                                class="bi bi-plus fs-5" xmlns="http://www.w3.org/2000/svg" width="1em"
                                                height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                <path
                                                    d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4">
                                                </path>
                                            </svg>Add New</button>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th width="40"><input type="checkbox" class="form-check-input">
                                                        </th>
                                                        <th>Page</th>
                                                        <th>Type</th>
                                                        <th>Status</th>
                                                        <th>Reviewer</th>
                                                        <th width="40"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr valign="middle">
                                                        <td><input type="checkbox" class="form-check-input"></td>
                                                        <td><button class="btn btn-link text-start p-0">Widget
                                                                Overview</button></td>
                                                        <td><span class="badge bg-light">Blog Post</span></td>
                                                        <td><span class="badge bg-light d-inline-flex gap-1"><svg
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
                                                                </svg>&nbsp;In Progress</span></td>
                                                        <td>Mike Johnson</td>
                                                        <td class="text-center">
                                                            <div class="dropstart"><a class="btn"
                                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                                    role="button"><svg class="bi bi-three-dots-vertical"
                                                                        xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                        height="1em" fill="currentColor"
                                                                        viewBox="0 0 16 16">
                                                                        <path
                                                                            d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0">
                                                                        </path>
                                                                    </svg></a>
                                                                <div class="dropdown-menu dropdown-menu-end"><a
                                                                        class="dropdown-item" href="#"><svg
                                                                            class="bi bi-person me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z">
                                                                            </path>
                                                                        </svg>View</a><a class="dropdown-item"
                                                                        href="#"><svg class="bi bi-pencil-square me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z">
                                                                            </path>
                                                                            <path fill-rule="evenodd"
                                                                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z">
                                                                            </path>
                                                                        </svg>Edit</a>
                                                                    <div class="dropdown-divider"></div><a
                                                                        class="dropdown-item link-danger" href="#"><svg
                                                                            class="bi bi-trash2 me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M14 3a.7.7 0 0 1-.037.225l-1.684 10.104A2 2 0 0 1 10.305 15H5.694a2 2 0 0 1-1.973-1.671L2.037 3.225A.7.7 0 0 1 2 3c0-1.105 2.686-2 6-2s6 .895 6 2M3.215 4.207l1.493 8.957a1 1 0 0 0 .986.836h4.612a1 1 0 0 0 .986-.836l1.493-8.957C11.69 4.689 9.954 5 8 5s-3.69-.311-4.785-.793">
                                                                            </path>
                                                                        </svg>Delete</a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr valign="middle">
                                                        <td><input type="checkbox" class="form-check-input"></td>
                                                        <td><button class="btn btn-link text-start p-0">Pricing</button>
                                                        </td>
                                                        <td><span class="badge bg-light">Content Update</span></td>
                                                        <td><span class="badge bg-light d-inline-flex gap-1"><svg
                                                                    class="bi bi-check-circle-fill text-success"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z">
                                                                    </path>
                                                                </svg>&nbsp;Done</span></td>
                                                        <td>Mike Johnson</td>
                                                        <td class="text-center">
                                                            <div class="dropstart"><a class="btn"
                                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                                    role="button"><svg class="bi bi-three-dots-vertical"
                                                                        xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                        height="1em" fill="currentColor"
                                                                        viewBox="0 0 16 16">
                                                                        <path
                                                                            d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0">
                                                                        </path>
                                                                    </svg></a>
                                                                <div class="dropdown-menu dropdown-menu-end"><a
                                                                        class="dropdown-item" href="#"><svg
                                                                            class="bi bi-person me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z">
                                                                            </path>
                                                                        </svg>View</a><a class="dropdown-item"
                                                                        href="#"><svg class="bi bi-pencil-square me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z">
                                                                            </path>
                                                                            <path fill-rule="evenodd"
                                                                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z">
                                                                            </path>
                                                                        </svg>Edit</a>
                                                                    <div class="dropdown-divider"></div><a
                                                                        class="dropdown-item link-danger" href="#"><svg
                                                                            class="bi bi-trash2 me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M14 3a.7.7 0 0 1-.037.225l-1.684 10.104A2 2 0 0 1 10.305 15H5.694a2 2 0 0 1-1.973-1.671L2.037 3.225A.7.7 0 0 1 2 3c0-1.105 2.686-2 6-2s6 .895 6 2M3.215 4.207l1.493 8.957a1 1 0 0 0 .986.836h4.612a1 1 0 0 0 .986-.836l1.493-8.957C11.69 4.689 9.954 5 8 5s-3.69-.311-4.785-.793">
                                                                            </path>
                                                                        </svg>Delete</a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr valign="middle">
                                                        <td><input type="checkbox" class="form-check-input"></td>
                                                        <td><button class="btn btn-link text-start p-0">Terms &amp;
                                                                Conditions</button></td>
                                                        <td><span class="badge bg-light">Content Update</span></td>
                                                        <td><span class="badge bg-light d-inline-flex gap-1"><svg
                                                                    class="bi bi-check-circle-fill text-success"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z">
                                                                    </path>
                                                                </svg>&nbsp;Done</span></td>
                                                        <td>Mike Johnson</td>
                                                        <td class="text-center">
                                                            <div class="dropstart"><a class="btn"
                                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                                    role="button"><svg class="bi bi-three-dots-vertical"
                                                                        xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                        height="1em" fill="currentColor"
                                                                        viewBox="0 0 16 16">
                                                                        <path
                                                                            d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0">
                                                                        </path>
                                                                    </svg></a>
                                                                <div class="dropdown-menu dropdown-menu-end"><a
                                                                        class="dropdown-item" href="#"><svg
                                                                            class="bi bi-person me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z">
                                                                            </path>
                                                                        </svg>View</a><a class="dropdown-item"
                                                                        href="#"><svg class="bi bi-pencil-square me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z">
                                                                            </path>
                                                                            <path fill-rule="evenodd"
                                                                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z">
                                                                            </path>
                                                                        </svg>Edit</a>
                                                                    <div class="dropdown-divider"></div><a
                                                                        class="dropdown-item link-danger" href="#"><svg
                                                                            class="bi bi-trash2 me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M14 3a.7.7 0 0 1-.037.225l-1.684 10.104A2 2 0 0 1 10.305 15H5.694a2 2 0 0 1-1.973-1.671L2.037 3.225A.7.7 0 0 1 2 3c0-1.105 2.686-2 6-2s6 .895 6 2M3.215 4.207l1.493 8.957a1 1 0 0 0 .986.836h4.612a1 1 0 0 0 .986-.836l1.493-8.957C11.69 4.689 9.954 5 8 5s-3.69-.311-4.785-.793">
                                                                            </path>
                                                                        </svg>Delete</a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr valign="middle">
                                                        <td><input type="checkbox" class="form-check-input"></td>
                                                        <td><button class="btn btn-link text-start p-0">Widget is
                                                                Coming</button></td>
                                                        <td><span class="badge bg-light">Blog Post</span></td>
                                                        <td><span class="badge bg-light d-inline-flex gap-1"><svg
                                                                    class="bi bi-x-circle-fill text-danger"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z">
                                                                    </path>
                                                                </svg>&nbsp;Rejected</span></td>
                                                        <td>Mike Johnson</td>
                                                        <td class="text-center">
                                                            <div class="dropstart"><a class="btn"
                                                                    data-bs-toggle="dropdown" aria-expanded="false"
                                                                    role="button"><svg class="bi bi-three-dots-vertical"
                                                                        xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                        height="1em" fill="currentColor"
                                                                        viewBox="0 0 16 16">
                                                                        <path
                                                                            d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0">
                                                                        </path>
                                                                    </svg></a>
                                                                <div class="dropdown-menu dropdown-menu-end"><a
                                                                        class="dropdown-item" href="#"><svg
                                                                            class="bi bi-person me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z">
                                                                            </path>
                                                                        </svg>View</a><a class="dropdown-item"
                                                                        href="#"><svg class="bi bi-pencil-square me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z">
                                                                            </path>
                                                                            <path fill-rule="evenodd"
                                                                                d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z">
                                                                            </path>
                                                                        </svg>Edit</a>
                                                                    <div class="dropdown-divider"></div><a
                                                                        class="dropdown-item link-danger" href="#"><svg
                                                                            class="bi bi-trash2 me-2"
                                                                            xmlns="http://www.w3.org/2000/svg"
                                                                            width="1em" height="1em" fill="currentColor"
                                                                            viewBox="0 0 16 16">
                                                                            <path
                                                                                d="M14 3a.7.7 0 0 1-.037.225l-1.684 10.104A2 2 0 0 1 10.305 15H5.694a2 2 0 0 1-1.973-1.671L2.037 3.225A.7.7 0 0 1 2 3c0-1.105 2.686-2 6-2s6 .895 6 2M3.215 4.207l1.493 8.957a1 1 0 0 0 .986.836h4.612a1 1 0 0 0 .986-.836l1.493-8.957C11.69 4.689 9.954 5 8 5s-3.69-.311-4.785-.793">
                                                                            </path>
                                                                        </svg>Delete</a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <nav aria-label="Table pagination">
                                            <div
                                                class="d-flex flex-column justify-content-between align-items-center gap-2 flex-lg-row pt-2 pt-lg-0">
                                                <div class="text-muted small"><span>Page 1 of 10</span></div>
                                                <div
                                                    class="d-flex justify-content-center align-items-center flex-wrap gap-3">
                                                    <div class="d-flex align-items-center gap-2"><label
                                                            class="form-label mb-0 small">Rows per page</label><select
                                                            class="form-select-sm w-auto form-select">
                                                            <option value="">10</option>
                                                            <option value="">25</option>
                                                            <option value="">50</option>
                                                        </select></div>
                                                    <nav>
                                                        <ul class="pagination pagination-sm mb-0">
                                                            <li class="page-item"><a class="page-link"
                                                                    aria-label="Previous" href="#"><span
                                                                        aria-hidden="true">«</span></a></li>
                                                            <li class="page-item"><a class="page-link" href="#">1</a>
                                                            </li>
                                                            <li class="page-item"><a class="page-link" href="#">2</a>
                                                            </li>
                                                            <li class="page-item"><a class="page-link" href="#">3</a>
                                                            </li>
                                                            <li class="page-item"><a class="page-link" aria-label="Next"
                                                                    href="#"><span aria-hidden="true">»</span></a></li>
                                                        </ul>
                                                    </nav>
                                                </div>
                                            </div>
                                        </nav>
                                    </div>
                                    <div class="tab-pane" role="tabpanel" id="tab-2">
                                        <p>Content for tab 2.</p>
                                    </div>
                                    <div class="tab-pane" role="tabpanel" id="tab-3">
                                        <p>Content for tab 3.</p>
                                    </div>
                                </div>
                            </div>
                        </div><!-- End: Tabs Card -->
                        <!-- Start: Table Card -->
                        <div class="card">
                            <div
                                class="card-header d-flex justify-content-between align-items-center flex-wrap flex-xl-nowrap pb-0 py-3">
                                <h5 class="fw-bold w-100 mb-3 mb-xl-0">Recent Customers</h5>
                                <form class="position-relative flex-grow-1 flex-shrink-0 flex-xl-grow-0 ms-auto"><input
                                        class="form-control pe-4" type="search" placeholder="Search"
                                        name="search"><button
                                        class="btn border-0 position-absolute top-50 end-0 translate-middle-y"
                                        type="submit"><svg class="bi bi-search" xmlns="http://www.w3.org/2000/svg"
                                            width="1em" height="1em" fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0">
                                            </path>
                                        </svg></button></form>
                                <div class="dropdown"><button class="btn btn-link" data-bs-toggle="dropdown"
                                        aria-expanded="false" type="button"><svg class="bi bi-three-dots-vertical"
                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                            fill="currentColor" viewBox="0 0 16 16">
                                            <path
                                                d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0">
                                            </path>
                                        </svg></button>
                                    <div class="dropdown-menu dropdown-menu-end shadow"><a class="dropdown-item"
                                            href="#">7 Days</a><a class="dropdown-item" href="#">30 Days</a>
                                        <div class="dropdown-divider"></div><a class="dropdown-item" href="#">Custom
                                            Period</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover" style="min-width: 550px;">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Status</th>
                                                <th>Lifetime Value</th>
                                                <th>Join Date</th>
                                                <th width="40"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr valign="middle">
                                                <td class="p-3"><a
                                                        class="text-decoration-none d-flex align-items-center gap-2"
                                                        href="#"><img
                                                            class="img-fluid aspect-ratio-1x1 object-fit-cover rounded-circle shadow-sm"
                                                            src="assets/img/team/avatar1.jpg?h=fc3130ca16c6d3ee2009fd4450b80205"
                                                            alt="Customer" width="40" height="40">
                                                        <div>
                                                            <p class="fw-bold mb-0">Joanna Prince</p><small
                                                                class="text-secondary d-block">Marketing Manager</small>
                                                        </div>
                                                    </a></td>
                                                <td><span class="badge bg-light d-inline-flex gap-1"><svg
                                                            class="bi bi-check-circle-fill text-success"
                                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            fill="currentColor" viewBox="0 0 16 16">
                                                            <path
                                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z">
                                                            </path>
                                                        </svg>&nbsp;Active</span></td>
                                                <td>$123.45</td>
                                                <td>21 Jul, 2025</td>
                                                <td class="text-center">
                                                    <div class="dropstart"><a class="btn" data-bs-toggle="dropdown"
                                                            aria-expanded="false" role="button"><svg
                                                                class="bi bi-three-dots-vertical"
                                                                xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                                <path
                                                                    d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0">
                                                                </path>
                                                            </svg></a>
                                                        <div class="dropdown-menu dropdown-menu-end"><a
                                                                class="dropdown-item" href="#"><svg
                                                                    class="bi bi-person me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z">
                                                                    </path>
                                                                </svg>View</a><a class="dropdown-item" href="#"><svg
                                                                    class="bi bi-pencil-square me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z">
                                                                    </path>
                                                                    <path fill-rule="evenodd"
                                                                        d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z">
                                                                    </path>
                                                                </svg>Edit</a>
                                                            <div class="dropdown-divider"></div><a
                                                                class="dropdown-item link-danger" href="#"><svg
                                                                    class="bi bi-trash2 me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M14 3a.7.7 0 0 1-.037.225l-1.684 10.104A2 2 0 0 1 10.305 15H5.694a2 2 0 0 1-1.973-1.671L2.037 3.225A.7.7 0 0 1 2 3c0-1.105 2.686-2 6-2s6 .895 6 2M3.215 4.207l1.493 8.957a1 1 0 0 0 .986.836h4.612a1 1 0 0 0 .986-.836l1.493-8.957C11.69 4.689 9.954 5 8 5s-3.69-.311-4.785-.793">
                                                                    </path>
                                                                </svg>Delete</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr valign="middle">
                                                <td class="p-3"><a
                                                        class="text-decoration-none d-flex align-items-center gap-2"
                                                        href="#"><img
                                                            class="img-fluid aspect-ratio-1x1 object-fit-cover rounded-circle shadow-sm"
                                                            src="assets/img/team/avatar2.jpg?h=7086b181e9fb853914a2cca97301c640"
                                                            alt="Customer" width="40" height="40">
                                                        <div>
                                                            <p class="fw-bold mb-0">Mike Johnson</p><small
                                                                class="text-secondary d-block">CTO, Corpy Corp</small>
                                                        </div>
                                                    </a></td>
                                                <td><span class="badge bg-light d-inline-flex gap-1"><svg
                                                            class="bi bi-pause-circle-fill text-warning"
                                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            fill="currentColor" viewBox="0 0 16 16">
                                                            <path
                                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M6.25 5C5.56 5 5 5.56 5 6.25v3.5a1.25 1.25 0 1 0 2.5 0v-3.5C7.5 5.56 6.94 5 6.25 5m3.5 0c-.69 0-1.25.56-1.25 1.25v3.5a1.25 1.25 0 1 0 2.5 0v-3.5C11 5.56 10.44 5 9.75 5">
                                                            </path>
                                                        </svg>&nbsp;Paused</span></td>
                                                <td>$9,123.45</td>
                                                <td>21 Jul, 2025</td>
                                                <td class="text-center">
                                                    <div class="dropstart"><a class="btn" data-bs-toggle="dropdown"
                                                            aria-expanded="false" role="button"><svg
                                                                class="bi bi-three-dots-vertical"
                                                                xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                                <path
                                                                    d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0">
                                                                </path>
                                                            </svg></a>
                                                        <div class="dropdown-menu dropdown-menu-end"><a
                                                                class="dropdown-item" href="#"><svg
                                                                    class="bi bi-person me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z">
                                                                    </path>
                                                                </svg>View</a><a class="dropdown-item" href="#"><svg
                                                                    class="bi bi-pencil-square me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z">
                                                                    </path>
                                                                    <path fill-rule="evenodd"
                                                                        d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z">
                                                                    </path>
                                                                </svg>Edit</a>
                                                            <div class="dropdown-divider"></div><a
                                                                class="dropdown-item link-danger" href="#"><svg
                                                                    class="bi bi-trash2 me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M14 3a.7.7 0 0 1-.037.225l-1.684 10.104A2 2 0 0 1 10.305 15H5.694a2 2 0 0 1-1.973-1.671L2.037 3.225A.7.7 0 0 1 2 3c0-1.105 2.686-2 6-2s6 .895 6 2M3.215 4.207l1.493 8.957a1 1 0 0 0 .986.836h4.612a1 1 0 0 0 .986-.836l1.493-8.957C11.69 4.689 9.954 5 8 5s-3.69-.311-4.785-.793">
                                                                    </path>
                                                                </svg>Delete</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr valign="middle">
                                                <td class="p-3"><a
                                                        class="text-decoration-none d-flex align-items-center gap-2"
                                                        href="#"><img
                                                            class="img-fluid aspect-ratio-1x1 object-fit-cover rounded-circle shadow-sm"
                                                            src="assets/img/team/avatar3.jpg?h=d00658bdbe17fa68ec776823ea82e9c1"
                                                            alt="Customer" width="40" height="40">
                                                        <div>
                                                            <p class="fw-bold mb-0">Joanna Prince</p><small
                                                                class="text-secondary d-block">Marketing Manager</small>
                                                        </div>
                                                    </a></td>
                                                <td><span class="badge bg-light d-inline-flex gap-1"><svg
                                                            class="bi bi-check-circle-fill text-success"
                                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            fill="currentColor" viewBox="0 0 16 16">
                                                            <path
                                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z">
                                                            </path>
                                                        </svg>&nbsp;Active</span></td>
                                                <td>$423.45</td>
                                                <td>21 Jul, 2025</td>
                                                <td class="text-center">
                                                    <div class="dropstart"><a class="btn" data-bs-toggle="dropdown"
                                                            aria-expanded="false" role="button"><svg
                                                                class="bi bi-three-dots-vertical"
                                                                xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                                <path
                                                                    d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0">
                                                                </path>
                                                            </svg></a>
                                                        <div class="dropdown-menu dropdown-menu-end"><a
                                                                class="dropdown-item" href="#"><svg
                                                                    class="bi bi-person me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z">
                                                                    </path>
                                                                </svg>View</a><a class="dropdown-item" href="#"><svg
                                                                    class="bi bi-pencil-square me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z">
                                                                    </path>
                                                                    <path fill-rule="evenodd"
                                                                        d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z">
                                                                    </path>
                                                                </svg>Edit</a>
                                                            <div class="dropdown-divider"></div><a
                                                                class="dropdown-item link-danger" href="#"><svg
                                                                    class="bi bi-trash2 me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M14 3a.7.7 0 0 1-.037.225l-1.684 10.104A2 2 0 0 1 10.305 15H5.694a2 2 0 0 1-1.973-1.671L2.037 3.225A.7.7 0 0 1 2 3c0-1.105 2.686-2 6-2s6 .895 6 2M3.215 4.207l1.493 8.957a1 1 0 0 0 .986.836h4.612a1 1 0 0 0 .986-.836l1.493-8.957C11.69 4.689 9.954 5 8 5s-3.69-.311-4.785-.793">
                                                                    </path>
                                                                </svg>Delete</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr valign="middle">
                                                <td class="p-3"><a
                                                        class="text-decoration-none d-flex align-items-center gap-2"
                                                        href="#"><img
                                                            class="img-fluid aspect-ratio-1x1 object-fit-cover rounded-circle shadow-sm"
                                                            src="assets/img/team/avatar4.jpg?h=13fcb1a3bcb58463519bc5974513259b"
                                                            alt="Customer" width="40" height="40">
                                                        <div>
                                                            <p class="fw-bold mb-0">Mike Johnson</p><small
                                                                class="text-secondary d-block">CTO, Corpy Corp</small>
                                                        </div>
                                                    </a></td>
                                                <td><span class="badge bg-light d-inline-flex gap-1"><svg
                                                            class="bi bi-x-circle-fill text-danger"
                                                            xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"
                                                            fill="currentColor" viewBox="0 0 16 16">
                                                            <path
                                                                d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293z">
                                                            </path>
                                                        </svg>&nbsp;Canceled</span></td>
                                                <td>$523.45</td>
                                                <td>21 Jul, 2025</td>
                                                <td class="text-center">
                                                    <div class="dropstart"><a class="btn" data-bs-toggle="dropdown"
                                                            aria-expanded="false" role="button"><svg
                                                                class="bi bi-three-dots-vertical"
                                                                xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                height="1em" fill="currentColor" viewBox="0 0 16 16">
                                                                <path
                                                                    d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0">
                                                                </path>
                                                            </svg></a>
                                                        <div class="dropdown-menu dropdown-menu-end"><a
                                                                class="dropdown-item" href="#"><svg
                                                                    class="bi bi-person me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z">
                                                                    </path>
                                                                </svg>View</a><a class="dropdown-item" href="#"><svg
                                                                    class="bi bi-pencil-square me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M15.502 1.94a.5.5 0 0 1 0 .706L14.459 3.69l-2-2L13.502.646a.5.5 0 0 1 .707 0l1.293 1.293zm-1.75 2.456-2-2L4.939 9.21a.5.5 0 0 0-.121.196l-.805 2.414a.25.25 0 0 0 .316.316l2.414-.805a.5.5 0 0 0 .196-.12l6.813-6.814z">
                                                                    </path>
                                                                    <path fill-rule="evenodd"
                                                                        d="M1 13.5A1.5 1.5 0 0 0 2.5 15h11a1.5 1.5 0 0 0 1.5-1.5v-6a.5.5 0 0 0-1 0v6a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-11a.5.5 0 0 1 .5-.5H9a.5.5 0 0 0 0-1H2.5A1.5 1.5 0 0 0 1 2.5z">
                                                                    </path>
                                                                </svg>Edit</a>
                                                            <div class="dropdown-divider"></div><a
                                                                class="dropdown-item link-danger" href="#"><svg
                                                                    class="bi bi-trash2 me-2"
                                                                    xmlns="http://www.w3.org/2000/svg" width="1em"
                                                                    height="1em" fill="currentColor"
                                                                    viewBox="0 0 16 16">
                                                                    <path
                                                                        d="M14 3a.7.7 0 0 1-.037.225l-1.684 10.104A2 2 0 0 1 10.305 15H5.694a2 2 0 0 1-1.973-1.671L2.037 3.225A.7.7 0 0 1 2 3c0-1.105 2.686-2 6-2s6 .895 6 2M3.215 4.207l1.493 8.957a1 1 0 0 0 .986.836h4.612a1 1 0 0 0 .986-.836l1.493-8.957C11.69 4.689 9.954 5 8 5s-3.69-.311-4.785-.793">
                                                                    </path>
                                                                </svg>Delete</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div><!-- End: Table Card -->
                    </div><!-- Start: Footer Centered -->
                    <?php include '_include/inner-footer.php'; ?>
                </main>
            </div>
        </div>
    </div>
    <?php include '_include/body_end_plugins.php'; ?>
</body>

</html>