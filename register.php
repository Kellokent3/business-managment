<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'All fields are required.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();

        // Check duplicates
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists. Please choose another.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed]);
            $success = 'Account created successfully! You can now <a href="login.php">log in</a>.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UMUHUZA Cooperative</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-box glass-card" style="max-width:480px;">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-wheat-awn"></i>
                <span>UMUHUZA</span>
            </div>
            <h2>Create Admin Account</h2>
            <p>Register to manage the cooperative system</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-circle-xmark"></i><span><?= clean($error) ?></span></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-circle-check"></i><span><?= $success ?></span></div>
        <?php endif; ?>

        <form method="POST" class="auth-form" autocomplete="off">
            <div class="form-group">
                <label>Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Choose a username" value="<?= clean($_POST['username'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Enter your email" value="<?= clean($_POST['email'] ?? '') ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="pwd" placeholder="Min. 6 characters" required>
                    <button type="button" onclick="togglePwd('pwd','e1')" style="position:absolute;right:14px;background:none;border:none;color:var(--text-muted);cursor:pointer;"><i class="fas fa-eye" id="e1"></i></button>
                </div>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" id="pwd2" placeholder="Repeat your password" required>
                    <button type="button" onclick="togglePwd('pwd2','e2')" style="position:absolute;right:14px;background:none;border:none;color:var(--text-muted);cursor:pointer;"><i class="fas fa-eye" id="e2"></i></button>
                </div>
            </div>

            <!-- Password strength -->
            <div id="pwd-strength" style="margin-top:-12px;margin-bottom:16px;font-size:0.78rem;color:var(--text-muted);"></div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Sign In</a></p>
        </div>
    </div>
</div>
<script>
function togglePwd(id, eyeId) {
    const p = document.getElementById(id);
    const e = document.getElementById(eyeId);
    p.type = p.type === 'password' ? 'text' : 'password';
    e.className = p.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
document.getElementById('pwd').addEventListener('input', function() {
    const v = this.value, el = document.getElementById('pwd-strength');
    if (!v) { el.textContent = ''; return; }
    let strength = 0;
    if (v.length >= 6) strength++;
    if (v.length >= 10) strength++;
    if (/[A-Z]/.test(v)) strength++;
    if (/[0-9]/.test(v)) strength++;
    if (/[^A-Za-z0-9]/.test(v)) strength++;
    const labels = ['', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
    const colors = ['', '#ef4444', '#f59e0b', '#f59e0b', '#10b981', '#00d4aa'];
    el.textContent = 'Strength: ' + (labels[strength] || 'Weak');
    el.style.color = colors[strength] || '#ef4444';
});
</script>
</body>
</html>
