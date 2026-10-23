<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

include 'config/database.php';

/* Load the shared email service used for account notifications. */
require_once __DIR__ . '/includes/mailer.php';
/* Load the shared branded email layout. */
require_once __DIR__ . '/includes/email-template.php';

if(isset($_POST['register'])){

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    try {
        // Use prepared statements for a more robust and secure registration
        $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement");
        }

        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $password, $role);
        $exec_result = mysqli_stmt_execute($stmt);

        if ($exec_result) {
            mysqli_stmt_close($stmt);

            /* Describe what the newly registered account can do. */
            $accountMessage = $role === 'host'
                ? 'List your vehicles, manage rental requests, and rent vehicles from other hosts.'
                : 'Explore vehicles, choose your rental dates, and book your next drive.';

            /* Build the welcome message using the shared HireSphere design. */
            $body = hsEmailTemplate(
                $name,
                'Your next journey starts here',
                'Welcome to HireSphere! Your account has been created successfully.',
                [
                    'Name' => $name,
                    'Account type' => ucfirst($role),
                    'Email address' => $email
                ],
                $accountMessage . ' Sign in to get started.'
            );

            /* Deliver to the new user's registration email. */
            $emailSent = sendEmail(
                $email,
                'Welcome aboard — HireSphere',
                $body
            );

            /* Registration remains successful even if email delivery fails. */
            if (!$emailSent) {
                error_log('HireSphere: welcome email delivery failed.');
            }

            header('Location: login.php');
            exit;
        } else {
            $error = "Registration failed. Please check your details.";
        }
    } catch (mysqli_sql_exception $e) {
        // Handle common errors like duplicate email without crashing (HTTP 500)
        $error = "Registration failed";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="auth-page-wrapper">

    <div class="container py-5">

        <div class="row justify-content-center">

            <div class="col-lg-5">

                <div class="auth-card">

                    <h2 class="auth-title">Create Account</h2>
                    <p class="auth-subtitle">Join HireSphere as a host or customer</p>

                    <?php if(isset($error)){ ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php } ?>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" name="name" class="form-control auth-input" placeholder="e.g. John Doe" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control auth-input" placeholder="name@example.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control auth-input" placeholder="Create a strong password" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Register As</label>
                            <select name="role" class="form-select auth-input">
                                <option value="customer">Customer</option>
                                <option value="host">Host</option>
                            </select>
                        </div>

                        <button name="register" class="btn btn-primary w-100 auth-btn mb-3">
                            Create Account
                        </button>

                    </form>

                    <p class="text-center text-muted mb-0">
                        Already have an account? <a href="login.php" class="text-primary text-decoration-none fw-semibold">Login</a>
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

<?php include 'includes/footer.php'; ?>