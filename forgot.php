<?php
// ==== CONFIG & DEPENDENCIES ====
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Mailer.php';

// ==== SECURE SESSION START ====
secureSessionStart();

// ==== INITIALIZE ====
$errors = $_SESSION['forgot_errors'] ?? [];
unset($_SESSION['forgot_errors']);
$success = $_SESSION['forgot_success'] ?? null;
unset($_SESSION['forgot_success']);

try {
    $pdo = Database::getInstance();
    $userObj = new User($pdo);
    $mailer = new Mailer();
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

// ==== RATE LIMITING CONFIG ====
if (!isset($_SESSION['forgot_attempts'])) {
    $_SESSION['forgot_attempts'] = [];
}

// Remove expired attempts
foreach ($_SESSION['forgot_attempts'] as $time => $recordedIp) {
    if (time() - $time > $lockoutTime) {
        unset($_SESSION['forgot_attempts'][$time]);
    }
}

// Count attempts from this IP
$attempts = array_filter($_SESSION['forgot_attempts'], fn($v) => $v === $ip);

// ==== FORM SUBMISSION HANDLER ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = [];
    if (count($attempts) >= $maxAttempts) {
        $errors[] = 'Too many password reset requests from your IP. Please wait and try again later.';
    }
    // Trim email before sanitization
    $_POST['useremail'] = trim($_POST['useremail'] ?? '');

    // Sanitize and validate input
    $allowedFields = ['useremail' => 'email'];
    try {
        $input = sanitizeInput($_POST, $allowedFields);
        $email = $input['useremail'];
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }

    // reCAPTCHA verification
    $recaptchaSecret = GOOGLE_RECAPTCHA_SECRET_KEY;
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    if (empty($recaptchaResponse)) {
        $errors[] = 'Please complete the reCAPTCHA.';
    } else {
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?" . http_build_query([
            'secret' => $recaptchaSecret,
            'response' => $recaptchaResponse,
            'remoteip' => $ip
        ]));
        $captchaResult = json_decode($verify);
        if (!$captchaResult->success) {
            $errors[] = 'reCAPTCHA verification failed.';
        }
    }

    if (empty($errors)) {
        try {
            // Check if user exists and is approved & not banned
            $stmt = $pdo->prepare("SELECT id, first_name, user_email, approved, banned FROM zentra_users WHERE user_email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // To prevent user enumeration, do NOT reveal this directly
                $errors[] = "We have successfully got your password reset request. If our system finds an account with the email provided, a reset link will be sent to the same email address shortly.";
            } elseif ((int)$user['banned'] === 1) {
                $errors[] = 'This account has been banned.';
            } elseif ((int)$user['approved'] !== 1) {
                $errors[] = 'This account has not been approved yet.';
            } else {
                // Generate token and expiry (1 hour validity)
                $token = bin2hex(random_bytes(16));
                $expires = date('Y-m-d H:i:s', time() + 3600);

                // Store token and expiry in your password resets table
                $insert = $pdo->prepare("INSERT INTO zentra_password_resets (user_id, reset_token, expires_at) VALUES (:uid, :token, :expires)");
                $insert->execute([
                    'uid' => $user['id'],
                    'token' => $token,
                    'expires' => $expires
                ]);

                // Send reset email
                $mailSent = $mailer->sendResetPasswordEmail($user['user_email'], $user['first_name'], $token);

                if ($mailSent) {
                    $success[] = "A password reset link has been sent to your email.";
                    $identifier = "Password reset requested for user {$user['user_email']}";
                    $userObj->logActivity($user['id'], $identifier, 'Password Reset Requested');
                } else {
                    $errors[] = "Failed to send reset email. Please try again later.";
                }
            }
        } catch (PDOException $e) {
            error_log("FORGOT PASSWORD ERROR: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again later.';
        }
    }

    // Log attempts and errors
    if (!empty($errors)) {
        $_SESSION['forgot_attempts'][time()] = $ip;
        $identifier = "Failed password reset request for email: " . ($email ?? '[unknown]');
        $userId = $user['id'] ?? null;
        $userObj->logActivity(
            $userId,
            $identifier,
            'Password Reset Error',
            ['context_error' => implode(" | ", $errors)]
        );
    }

    // Save errors or success message for display and redirect to self
    $_SESSION['forgot_errors'] = $errors;
    $_SESSION['forgot_success'] = $success ?? null;
    header("Location: forgot.php");
    exit;
}
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= APP_NAME ?> - Forgot</title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css?h=283928673d7441cd64f1af3db9200eab">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Geist:400,700&amp;display=swap">
    <link rel="stylesheet" href="assets/css/styles.min.css?h=6fca2a621bf969aa555e4e55be38144a">
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
                        <!-- Start: alert -->
                        <?php if (!empty($success)): ?>
                            <div class="w-100 alert-success">
                                <?= implode('<br>', $success) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="w-100 alert-error">
                                <?= implode('<br>', $errors) ?>
                            </div>
                        <?php endif; ?>

                        <!-- End: alert -->
                        <div class="card w-100 max-w-400">
                            <div class="card-body">
                                <h1 class="fs-5 mb-1">Password playing hide and seek?</h1>
                                <p class="text-muted mb-4 small">It happens! Drop your email below and we'll send you a
                                    shiny new password.</p>
                                <form>
                                    <div class="mb-3"><label class="form-label" for="email">Email</label><input
                                            class="form-control" type="email" id="useremail"
                                            placeholder="yourname@work-email.com" required="" name="useremail"
                                            autofocus="" autocomplete="off" inputmode="email"></div>
                                    <!-- Start: recaptcha -->
                                    <div class="g-recaptcha" data-sitekey="<?= GOOGLE_RECAPTCHA_SITE_KEY; ?>"></div>
                                    <script src="https://www.google.com/recaptcha/api.js" async defer>
                                    </script>
                                    <!-- End: recaptcha -->
                                    <div class="d-grid gap-2 my-3"><button class="btn btn-primary" type="submit">Send
                                            new
                                            password</button></div>
                                    <div class="text-center mt-4 small"><span> Back to&nbsp;</span><a
                                            href="login.php">Login</a></div>
                                </form>
                            </div>
                        </div>
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