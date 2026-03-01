<?php
// ============================================
// UMUHUZA COOPERATIVE - Database Configuration
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'umuhuza_cooperative');
define('APP_NAME', 'UMUHUZA Cooperative');
define('APP_VERSION', '1.0.0');

// PDO Connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;background:#1a1a2e;color:#ef4444;padding:40px;min-height:100vh;">
                <h2>&#9888; Database Connection Failed</h2>
                <p style="color:#fca5a5;margin-top:10px;">' . htmlspecialchars($e->getMessage()) . '</p>
                <p style="color:#9ca3af;margin-top:20px;">Please check your database settings in <code>config.php</code></p>
            </div>');
        }
    }
    return $pdo;
}

// Sanitize helper
function clean($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// Format currency (RWF)
function formatCurrency($amount) {
    return 'RWF ' . number_format($amount, 0, '.', ',');
}

// Format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit();
}

// Flash message
function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
