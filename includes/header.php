<?php
if(session_status() === PHP_SESSION_NONE) session_start();

require_once 'config/google.php';
$google_login_url = $client->createAuthUrl();

// Check the existing login session without changing the user's account role.
$navLoggedIn = isset($_SESSION['user_id']);
$navIsHost = $navLoggedIn && ($_SESSION['role'] ?? '') === 'host';

// Identify host pages so the correct navigation option is highlighted.
$navCurrentPage = basename($_SERVER['SCRIPT_NAME']);
$navHostPages = [
    'host-dashboard.php',
    'host-bookings.php',
    'host-calendar.php',
    'add-vehicle.php',
    'edit-vehicle.php',
    'subscriptions.php'
];
$navHostActive = in_array($navCurrentPage, $navHostPages, true);

// Escape account details before displaying them inside HTML.
$navName = htmlspecialchars(
    (string) ($_SESSION['name'] ?? 'Member'),
    ENT_QUOTES,
    'UTF-8'
);
$navRole = $navIsHost ? 'Host' : 'Customer';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HireSphere</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
      rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
<!-- Load appearance overrides after the original site styles. -->
<link rel="stylesheet" href="assets/css/appearance.css">

<!-- Apply the saved theme before the page is painted to reduce flashing. -->
<script>
    (() => {
        let theme = 'light';

        // Storage can be unavailable in some browser privacy configurations.
        try {
            const savedTheme = localStorage.getItem('hiresphere-theme');

            if (savedTheme === 'light' || savedTheme === 'dark') {
                theme = savedTheme;
            }
        } catch (error) {
            // Continue with light mode when storage cannot be read.
        }

        document.documentElement.setAttribute('data-bs-theme', theme);
    })();
</script>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">

    <div class="container">

        <!-- BRAND -->
        <a class="navbar-brand d-flex align-items-center gap-3" href="index.php">

    <span class="home-icon">

        🏠

    </span>

    <span class="brand-text">

        HireSphere

    </span>

</a>

        <!-- MOBILE TOGGLER -->
        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav">

            <span class="navbar-toggler-icon"></span>

        </button>

        <!-- NAVIGATION -->
        <div class="collapse navbar-collapse" id="navbarNav">

            <!-- CENTER SEARCH -->
            <div class="mx-auto nav-search d-none d-lg-flex">

                <div class="search-pill shadow-sm">

                    <span>Nairobi</span>

                    <div class="divider"></div>

                    <span>Any Week</span>

                    <div class="divider"></div>

                    <span class="text-muted">
                        Add Guests
                    </span>

                    <button class="search-btn">
                        🔍
                    </button>

                </div>

            </div>

            <!-- RIGHT SIDE: page switcher and account dropdown. -->
            <ul class="navbar-nav align-items-lg-center gap-2">

                <?php if ($navIsHost): ?>
                    <li class="nav-item">
                        <!-- Links change the current page; they do not change account permissions. -->
                        <div class="hs-mode-switch" role="group" aria-label="Choose workspace">

                            <a href="index.php"
                               class="hs-mode-link <?= !$navHostActive ? 'active' : '' ?>"
                               <?= !$navHostActive ? 'aria-current="page"' : '' ?>>
                                <i class="bi bi-house-door" aria-hidden="true"></i>
                                Guest
                            </a>

                            <a href="host-dashboard.php"
                               class="hs-mode-link <?= $navHostActive ? 'active' : '' ?>"
                               <?= $navHostActive ? 'aria-current="page"' : '' ?>>
                                <i class="bi bi-grid" aria-hidden="true"></i>
                                Host
                            </a>

                        </div>
                    </li>
                <?php endif; ?>

                <?php if ($navLoggedIn): ?>
                    <li class="nav-item dropdown">

                        <!-- Bootstrap opens this menu using the bundle already in your footer. -->
                        <button class="nav-link dropdown-toggle hs-profile-button"
                                type="button"
                                id="hsProfileMenu"
                                data-bs-toggle="dropdown"
                                aria-expanded="false">

                            <i class="bi bi-person-circle fs-4" aria-hidden="true"></i>
                            <span><?= $navName ?></span>

                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow"
                            aria-labelledby="hsProfileMenu">

                            <!-- Settings opens a modal instead of changing the page layout. -->
                            <li>
                                <button class="dropdown-item"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#hsSettingsModal">
                                    <i class="bi bi-gear me-2" aria-hidden="true"></i>
                                    Settings
                                </button>
                            </li>

                            <!-- JavaScript updates this button's icon and label. -->
                            <li>
                                <button class="dropdown-item"
                                        type="button"
                                        data-hs-theme-toggle
                                        aria-pressed="false">
                                    <i class="bi bi-moon-stars me-2"
                                       data-hs-theme-icon
                                       aria-hidden="true"></i>
                                    <span data-hs-theme-label>Switch to dark mode</span>
                                </button>
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <!-- Keep using your existing logout handler. -->
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>
                                    Logout
                                </a>
                            </li>

                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Visitors retain normal registration and login links. -->
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Become a Host</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                <?php endif; ?>

            </ul>

        </div>

    </div>

</nav>

<?php if ($navLoggedIn): ?>
<!-- Account settings: displays current session details without updating the database. -->
<div class="modal fade"
     id="hsSettingsModal"
     tabindex="-1"
     aria-labelledby="hsSettingsTitle"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="hsSettingsTitle">
                    Account settings
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>

            <div class="modal-body">

                <!-- These fields are read-only; profile editing needs a separate handler. -->
                <dl class="row mb-4">
                    <dt class="col-4">Name</dt>
                    <dd class="col-8"><?= $navName ?></dd>

                    <dt class="col-4">Account</dt>
                    <dd class="col-8"><?= $navRole ?></dd>
                </dl>

                <h6>Appearance</h6>
                <p class="text-muted small">
                    Your appearance preference is saved in this browser.
                </p>

                <!-- Reuses the same theme action as the profile dropdown. -->
                <button class="btn btn-outline-primary"
                        type="button"
                        data-hs-theme-toggle
                        aria-pressed="false">
                    <i class="bi bi-moon-stars me-2"
                       data-hs-theme-icon
                       aria-hidden="true"></i>
                    <span data-hs-theme-label>Switch to dark mode</span>
                </button>

            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- =========================
     LOGIN MODAL
========================= -->

<div class="modal fade"
     id="loginModal"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content auth-modal">

            <div class="modal-body p-4 p-lg-5">

                <!-- CLOSE -->
                <div class="text-end mb-3">

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"></button>

                </div>

                <!-- TITLE -->
                <h2 class="fw-bold mb-2">
                    Welcome Back
                </h2>

                <p class="text-muted mb-4">
                    Login to continue booking premium rides.
                </p>

                <!-- FORM -->
                <form>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Email Address
                        </label>

                        <input type="email"
                               class="form-control auth-input"
                               placeholder="Enter your email">

                    </div>

                    <div class="mb-4">

                        <label class="form-label fw-semibold">
                            Password
                        </label>

                        <div class="position-relative">

                            <input type="password"
                                   class="form-control auth-input password-field"
                                   placeholder="Enter your password">

                            <button type="button"
                                    class="password-toggle">

                                <i class="bi bi-eye"></i>

                            </button>

                        </div>

                    </div>

                    <button type="button" class="btn btn-primary w-100 auth-btn">

                        Login

                    </button>

                </form>

                <!-- DIVIDER -->
                <div class="auth-divider my-4">
                    <span>OR</span>
                </div>

                <!-- SOCIALS -->
                <div class="d-grid gap-3">

                    <a href="<?php echo $google_login_url; ?>" class="btn btn-google auth-social-btn">

                        <svg class="google-icon" viewBox="0 0 533.5 544.3" aria-hidden="true" focusable="false">
                            <path fill="#4285f4" d="M533.5 278.4c0-18.8-1.5-37-4.4-54.7H272.1v103.5h146.9c-6.4 34.6-25.8 63.9-55 83.6v69.4h88.8c52.1-48 82-118.5 82-201.8z"/>
                            <path fill="#34a853" d="M272.1 544.3c74.4 0 136.8-24.7 182.3-67.1l-88.8-69.4c-24.7 16.6-56.4 26.4-93.5 26.4-71.9 0-132.8-48.6-154.6-113.9H28.9v71.5c45.3 89.3 138.5 152.5 243.2 152.5z"/>
                            <path fill="#fbbc04" d="M117.5 324.8c-10.1-30.6-10.1-63.5 0-94.1V159.2H28.9C-3.4 213.6-3.4 330.7 28.9 385.1l88.6-60.3z"/>
                            <path fill="#ea4335" d="M272.1 107.8c39.8 0 75.6 13.7 103.7 40.5l77.9-77.9C407.8 24.7 345.4 0 272.1 0 167.4 0 74.2 63.2 28.9 152.5l88.6 71.5c21.8-65.3 82.7-113.9 154.6-113.9z"/>
                        </svg>
                        Continue with Google

                    </a>

                    <button class="btn btn-facebook auth-social-btn js-social-login" data-provider="Facebook">

                        <i class="bi bi-facebook"></i>
                        Continue with Facebook

                    </button>

                    <button class="btn btn-microsoft auth-social-btn js-social-login" data-provider="Microsoft">
                        <i class="bi bi-microsoft me-2"></i>
                        Continue with Microsoft
                    </button>

                    <button class="btn btn-apple auth-social-btn js-social-login" data-provider="Apple">
                        <i class="bi bi-apple me-2"></i>
                        Continue with Apple
                    </button>

                    <button class="btn btn-phone auth-social-btn js-social-login" data-provider="Phone">
                        <i class="bi bi-telephone-fill me-2"></i>
                        Continue with Phone
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>


<!-- =========================
     REGISTER MODAL
========================= -->

<div class="modal fade"
     id="registerModal"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content auth-modal">

            <div class="modal-body p-4 p-lg-5">

                <!-- CLOSE -->
                <div class="text-end mb-3">

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"></button>

                </div>

                <!-- TITLE -->
                <h2 class="fw-bold mb-2">
                    Create Account
                </h2>

                <p class="text-muted mb-4">
                    Join HireSphere and start booking premium vehicles.
                </p>

                <!-- FORM -->
                <form>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Full Name
                        </label>

                        <input type="text"
                               class="form-control auth-input"
                               placeholder="Enter full name">

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Email Address
                        </label>

                        <input type="email"
                               class="form-control auth-input"
                               placeholder="Enter email">

                    </div>

                    <div class="mb-4">

                        <label class="form-label fw-semibold">
                            Password
                        </label>

                        <div class="position-relative">

                            <input type="password"
                                   class="form-control auth-input password-field"
                                   placeholder="Create password">

                            <button type="button"
                                    class="password-toggle">

                                <i class="bi bi-eye"></i>

                            </button>

                        </div>

                    </div>

                    <button type="button" class="btn btn-primary w-100 auth-btn">

                        Create Account

                    </button>

                </form>

                <!-- HOST CTA -->
                <div class="host-cta mt-4">

                    <h6 class="fw-bold mb-2">
                        Want to earn with your car?
                    </h6>

                    <p class="text-muted small mb-3">
                        Become a host and list your vehicle on HireSphere.
                    </p>

                    <button class="btn btn-outline-primary w-100">

                        Become a Host

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>
