<?php
session_start();
require_once 'config.php';

// Already logged in?
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password    = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']       = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_email']    = $user['email'];
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['last_regenerated'] = time();
            setFlash('success', 'Welcome back, ' . $user['username'] . '! You are now logged in.');
            redirect('dashboard.php');
        } else {
            $error = 'Invalid username/email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UMUHUZA Cooperative</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-box glass-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-wheat-awn"></i>
                <span>UMUHUZA</span>
            </div>
            <h2>Welcome Back</h2>
            <p>Sign in to manage the cooperative</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-circle-xmark"></i>
            <span><?= clean($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" class="auth-form" autocomplete="off">
            <div class="form-group">
                <label>Username or Email</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="login_input" placeholder="Enter username or email"
                           value="<?= clean($_POST['login_input'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    <button type="button" onclick="togglePwd()" style="position:absolute;right:14px;background:none;border:none;color:var(--text-muted);cursor:pointer;"><i class="fas fa-eye" id="eye-icon"></i></button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
                <i class="fas fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php">Create Admin Account</a></p>
        </div>

        <div style="margin-top:20px;padding:16px;background:rgba(0,0,0,0.2);border-radius:var(--radius-md);border:1px solid var(--glass-border);">
            <p style="font-size:0.75rem;color:var(--text-muted);margin-bottom:8px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;"><i class="fas fa-info-circle"></i> Quick Setup</p>
            <p style="font-size:0.8rem;color:var(--text-muted);">First time? <a href="register.php" style="color:var(--primary-accent);">Register an admin account</a> to get started.</p>
        </div>
    </div>
</div>
<script>
function togglePwd() {
    const p = document.getElementById('password');
    const e = document.getElementById('eye-icon');
    p.type = p.type === 'password' ? 'text' : 'password';
    e.className = p.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
</script>
</body>
</html>
