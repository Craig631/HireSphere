<?php
session_start();

include 'config/database.php';
require_once __DIR__ . '/config/google.php';

$google_login_url = $client->createAuthUrl();

if(isset($_POST['login'])){

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepared statement to prevent SQL Injection
    $stmt = mysqli_prepare($conn, "SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1");
    
    if (!$stmt) {
        die("Internal server error. Please try again later.");
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) == 1){
        $user = mysqli_fetch_assoc($result);

        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // redirect based on role
            if($user['role'] === 'host'){
                header("Location: host-dashboard.php");
            } else {
                header("Location: index.php");
            }

            exit;

        } else {
            $error = "Invalid password";
        }

    } else {
        $error = "User not found";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="auth-page-wrapper">

    <div class="container py-5">

        <div class="row justify-content-center">

            <div class="col-lg-5">

                <div class="auth-card">

                    <h2 class="auth-title">Welcome Back</h2>
                    <p class="auth-subtitle">Login to access HireSphere</p>

                    <?php if(isset($error)){ ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php } ?>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control auth-input" placeholder="Enter your email" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control auth-input" placeholder="Enter your password" required>
                        </div>

                        <button name="login" class="btn btn-primary w-100 auth-btn mb-3">
                            Login
                        </button>

                    </form>

                    <div class="text-center mb-4">
                        <p class="text-muted mb-2">Don't have an account?</p>
                        <a href="register.php" class="btn btn-outline-secondary w-100 auth-btn">
                            Register New Account
                        </a>
                    </div>

                    <div class="text-center mb-3">

                        <p class="text-muted">Or continue with</p>

                        <a href="<?php echo $google_login_url; ?>" class="btn btn-google auth-social-btn w-100 mb-2">
                            <svg class="google-icon" viewBox="0 0 533.5 544.3" aria-hidden="true" focusable="false">
                                <path fill="#4285f4" d="M533.5 278.4c0-18.8-1.5-37-4.4-54.7H272.1v103.5h146.9c-6.4 34.6-25.8 63.9-55 83.6v69.4h88.8c52.1-48 82-118.5 82-201.8z"/>
                                <path fill="#34a853" d="M272.1 544.3c74.4 0 136.8-24.7 182.3-67.1l-88.8-69.4c-24.7 16.6-56.4 26.4-93.5 26.4-71.9 0-132.8-48.6-154.6-113.9H28.9v71.5c45.3 89.3 138.5 152.5 243.2 152.5z"/>
                                <path fill="#fbbc04" d="M117.5 324.8c-10.1-30.6-10.1-63.5 0-94.1V159.2H28.9C-3.4 213.6-3.4 330.7 28.9 385.1l88.6-60.3z"/>
                                <path fill="#ea4335" d="M272.1 107.8c39.8 0 75.6 13.7 103.7 40.5l77.9-77.9C407.8 24.7 345.4 0 272.1 0 167.4 0 74.2 63.2 28.9 152.5l88.6 71.5c21.8-65.3 82.7-113.9 154.6-113.9z"/>
                            </svg>
                            Continue with Google
                        </a>

                        <button class="btn btn-facebook auth-social-btn w-100 mb-2 js-social-login" data-provider="Facebook">
                            <i class="bi bi-facebook"></i>
                            Continue with Facebook
                        </button>

                        <button class="btn btn-microsoft auth-social-btn w-100 mb-2 js-social-login" data-provider="Microsoft">
                            <i class="bi bi-microsoft me-2"></i>
                            Continue with Microsoft
                        </button>

                        <button class="btn btn-apple auth-social-btn w-100 mb-2 js-social-login" data-provider="Apple">
                            <i class="bi bi-apple me-2"></i>
                            Continue with Apple
                        </button>

                        <button class="btn btn-phone auth-social-btn w-100 mb-2 js-social-login" data-provider="Phone">
                            <i class="bi bi-telephone-fill me-2"></i>
                            Continue with Phone
                        </button>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include 'includes/footer.php'; ?>
