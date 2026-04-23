<?php

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // ==== CONFIG & DEPENDENCIES ====
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/config/init.php';
    require_once __DIR__ . '/config/helpers.php';
    require_once __DIR__ . '/classes/User.php';
    require_once __DIR__ . '/classes/ActivityLogger.php';
    require_once __DIR__ . 'classes/MenuManager.php';
    require_once __DIR__ . '_include/nav_renderer.php';

    // ==== SECURE SESSION START ====
    secureSessionStart();

    // ==== REQUEST CONTEXT (IP, AGENT, GEO, DEVICE) ====
    $ip      = cleanIP(getClientIP());
    $agent   = getUserAgent();
    $browser = getBrowserName($agent);
    $device  = getDeviceType($agent);
    $geo     = getGeoLocation($ip);

    $_SESSION['geo'] = [
    'city'    => $geo['city'],
    'region'  => $geo['region'],
    'country' => $geo['country'],
    'postal'  => $geo['postal'],
    'raw'     => $geo['raw'],
    ];

    $_SESSION['user_timezone'] = $geo['timezone'];

    // ==== ERROR DISPLAY HANDLER ====
    $errors = $_SESSION['login_errors'] ?? [];
    unset($_SESSION['login_errors']);

    try {
    $pdo     = Database::getInstance();
    $userObj = new User($pdo);
    } catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
    }
    $moduleManager = new ModuleManager($pdo); // ← REQUIRED
                                          // ==== RATE LIMITING CONFIG ====
    if (! isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
    }

    // Remove expired attempts
    foreach ($_SESSION['login_attempts'] as $time => $recordedIp) {
    if (time() - $time > $lockoutTime) {
        unset($_SESSION['login_attempts'][$time]);
    }
    }

    // Count login attempts from this IP
    $attempts = array_filter($_SESSION['login_attempts'], fn($v) => $v === $ip);

    // ==== LOGIN HANDLER ====
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (count($attempts) >= $maxAttempts) {
        $errors[] = 'Too many login attempts. Please wait before trying again.';
    }

    if (empty($errors)) {
        try {
            // === INPUT SANITIZATION ===
            $allowedFields = [
                'useremail'    => 'email',
                'userpassword' => 'password',
            ];
            $input    = sanitizeInput($_POST, $allowedFields);
            $email    = $input['useremail'];
            $password = $input['userpassword'];
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        // === reCAPTCHA VERIFICATION ===
        $recaptchaSecret   = GOOGLE_RECAPTCHA_SECRET_KEY;
        $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

        if (empty($recaptchaResponse)) {
            $errors[] = 'Please complete the reCAPTCHA.';
        } else {
            $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" .
                urlencode($recaptchaSecret) .
                "&response=" . urlencode($recaptchaResponse) .
                "&remoteip=" . urlencode($ip));
            $captchaResult = json_decode($verify);
            if (! $captchaResult->success) {
                $errors[] = 'reCAPTCHA verification failed.';
            }
        }

        // === DATABASE AUTH ===
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT id, first_name, pwd, approved, banned FROM zentra_users WHERE user_email = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (! $user || ! password_verify($password, $user['pwd'])) {
                    $errors[] = 'Invalid email or password.';
                } elseif ((int) $user['banned'] === 1) {
                    $errors[] = 'Your account has been banned.';
                } elseif ((int) $user['approved'] !== 1) {
                    $errors[] = 'Your account has not been approved yet.';
                } else {
                    // ==== SUCCESSFUL LOGIN ====
                    foreach ($_SESSION['login_attempts'] as $time => $attemptIp) {
                        if ($attemptIp === $ip) {
                            unset($_SESSION['login_attempts'][$time]);
                        }
                    }

                    session_regenerate_id(true); // Prevent session fixation

                    $userId                    = (int) $user['id'];
                    $_SESSION['user_id']       = $user['id'];
                    $_SESSION['user_name']     = $user['first_name'];
                    $_SESSION['user_email']    = $email;
                    $_SESSION['login_time']    = time();
                    $_SESSION['last_activity'] = time();
                    $_SESSION['user_ip']       = $ip;
                    $_SESSION['user_agent']    = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

                    // ==== ACTIVITY LOG ====
                    $identifier = "User {$user['first_name']} ({$email}) logged in";

                    $userObj->logActivity(
                        $userId,
                        $identifier,
                        'Login',
                        [
                            'user_name'     => $user['first_name'], // REQUIRED for ActivityLogger
                            'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                            'ip'            => $ip,
                            'browser'       => $browser,
                            'device'        => $device,
                            'city'          => $geo['city'] ?? null,
                            'region'        => $geo['region'] ?? null,
                            'country'       => $geo['country'] ?? null,
                            'geo_raw'       => $geo['raw'] ?? null,
                        ]
                    );

                    header("Location: myaccount.php");
                    exit;
                }
            } catch (PDOException $e) {
                error_log("LOGIN ERROR: " . $e->getMessage());
                $errors[] = 'Login failed. Please try again later.';
            }
        }
    }

    // === LOG FAILED ATTEMPTS + REDIRECT ===
    if (! empty($errors)) {
        $_SESSION['login_attempts'][time()] = $ip;

        $errorText  = implode(" | ", $errors);
        $safeEmail  = $email ?? 'unknown';
        $identifier = "Failed login attempt for email: {$safeEmail}";
        $userId     = $user['id'] ?? null;

        $userObj->logActivity(
            $userId,
            $identifier,
            'Login Error',
            [
                'user_name'     => $safeEmail, // shows email for failed attempts
                'user_timezone' => $_SESSION['user_timezone'] ?? 'UTC',
                'field_changed' => 'LOGIN_ATTEMPT',
                'old_value'     => 'UNAUTHENTICATED',
                'new_value'     => 'ERROR',
                'context_error' => $errorText,
                'ip'            => $ip,
                'browser'       => $browser,
                'device'        => $device,
                'city'          => $geo['city'] ?? null,
                'region'        => $geo['region'] ?? null,
                'country'       => $geo['country'] ?? null,
                'geo_raw'       => $geo['raw'] ?? null,
            ]
        );

        // Save errors for display
        $_SESSION['login_errors'] = $errors;
        header("Location: login.php");
        exit;
    }
    }
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
</head>

<body>
    <div class="container-fluid min-vh-100">
        <div class="row min-vh-100">
            <div class="col-lg-6 d-flex flex-column gap-4 p-4">
                <div class="d-flex justify-content-center gap-2 justify-content-md-start"><a
                        class="text-decoration-none link-body-emphasis d-inline-flex align-items-center" href="#"><span
                            class="fs-4 fw-bold brand-primary">ZENTRA</span><span
                            class="fs-4 brand-secondary">CMS</span></a></div>
                <div class="d-flex flex-fill justify-content-center align-items-center">
                    <div class="w-100 max-w-320">
                        <?php if (! empty($errors)): ?>
                        <div class="w-100 alert-error">
                            <?php foreach ($errors as $err): ?>
                            <span><?php echo htmlspecialchars($err) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <form class="d-flex flex-column gap-3" method="POST" action="">
                            <div class="text-center">
                                <h1 class="fs-4 fw-bold">Hey there <svg class="bi bi-heart-fill text-danger"
                                        xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="currentColor"
                                        viewBox="0 0 16 16">
                                        <path fill-rule="evenodd"
                                            d="M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314">
                                        </path>
                                    </svg></h1>
                                <p class="text-muted small">Log in with your email and let's get going.</p>
                            </div>
                            <div class="mb-3"><label class="form-label small fw-medium" for="email">Email</label><input
                                    class="form-control" type="email" placeholder="yourname@work-email.com"
                                    autocomplete="off" required name="useremail"></div>
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2"><label
                                        class="form-label mb-0 small fw-medium" for="password">Password</label><a
                                        class="ms-auto small" href="forgot.php">Forgot your password?</a></div><input
                                    class="form-control" type="password" id="userpassword" placeholder="**********"
                                    autocomplete="off" required name="userpassword">
                            </div>
                            <div class="g-recaptcha" data-sitekey="<?php echo GOOGLE_RECAPTCHA_SITE_KEY; ?>"></div>
                            <script src="https://www.google.com/recaptcha/api.js" async defer>
                            </script>
                            <button class="btn btn-primary w-100" type="submit">Login</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block p-0"><img class="img-fluid object-fit-cover w-100 h-100" width="6016"
                    height="4016" src="assets/img/photos/cms-bg.jpg?h=dc8949afa519d8742dc2bc733c6edeea" alt="Image">
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/4.0.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.min.js?h=76fb943b07981bddcd684084e3798cff"></script>
</body>

</html>