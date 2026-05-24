<?php
if(session_status() === PHP_SESSION_NONE) session_start();

require_once 'config/google.php';
$google_login_url = $client->createAuthUrl();

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

            <!-- RIGHT SIDE -->
            <ul class="navbar-nav align-items-lg-center">

                <li class="nav-item">
                    <a class="nav-link" href="add-vehicle.php">
                        Become a Host
                    </a>
                </li>

                <?php if(isset($_SESSION['name'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Welcome, <?php echo $_SESSION['name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'host'): ?>
                                <li><a class="dropdown-item" href="host-dashboard.php">Host Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="add-vehicle.php">
                            Become a Host
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php" data-bs-toggle="modal" data-bs-target="#loginModal">
                            Login
                        </a>
                    </li>
                <?php endif; ?>

            </ul>

        </div>

    </div>

</nav>

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

</nav>