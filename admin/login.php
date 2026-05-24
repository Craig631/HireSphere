<?php
session_start();

include '../config/database.php';

if ($_POST) {
    $username = $_POST['user'];
    $password = $_POST['pass'];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'];

        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid login credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - HireSphere</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('https://images.unsplash.com/photo-1492144534655-ae79c964c9d7'); background-size: cover; background-position: center; min-height: 100vh; margin: 0; }
        .login-card { background: var(--surface); padding: 40px; border-radius: var(--radius); border: 1px solid var(--border); width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .login-field { margin-bottom: 20px; }
        .login-field label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-secondary); }
        .login-input { width: 100%; padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border); font-family: inherit; outline: none; transition: border-color 0.2s; }
        .login-input:focus { border-color: var(--accent); }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="page-title" style="margin-bottom: 8px; text-align: center;">Admin Login</h2>
        <p class="page-subtitle" style="margin-bottom: 24px; text-align: center;">Access the HireSphere control panel</p>
        
        <?php if(isset($error)): ?>
            <div class="badge badge-failed" style="width: 100%; margin-bottom: 20px; justify-content: center; padding: 10px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="login-field">
                <label>Username</label>
                <input name="user" class="login-input" placeholder="Enter username" required autofocus>
            </div>
            <div class="login-field">
                <label>Password</label>
                <input name="pass" type="password" class="login-input" placeholder="Enter password" required>
            </div>
            <button class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">Login to Dashboard</button>
        </form>
    </div>
</body>
</html>