<?php
// Auth guard - include at top of every protected page
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['last_regenerated']) || time() - $_SESSION['last_regenerated'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regenerated'] = time();
}
