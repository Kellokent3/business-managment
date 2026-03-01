<?php
// Determine current page for active nav state
$current_page = basename($_SERVER['PHP_SELF'], '.php');

$nav_items = [
    ['href' => 'dashboard.php', 'icon' => 'fa-gauge-high',      'label' => 'Dashboard',    'page' => 'dashboard'],
    ['href' => 'members.php',   'icon' => 'fa-users',           'label' => 'Members',       'page' => 'members'],
    ['href' => 'products.php',  'icon' => 'fa-wheat-awn',       'label' => 'Products/Stock','page' => 'products'],
    ['href' => 'clients.php',   'icon' => 'fa-handshake',       'label' => 'Clients',       'page' => 'clients'],
    ['href' => 'sales.php',     'icon' => 'fa-cart-shopping',   'label' => 'Sales',         'page' => 'sales'],
    ['href' => 'reports.php',   'icon' => 'fa-chart-bar',       'label' => 'Reports',       'page' => 'reports'],
];

// Page titles
$page_titles = [
    'dashboard' => ['title' => 'Dashboard',      'subtitle' => 'Overview of cooperative activities'],
    'members'   => ['title' => 'Members',         'subtitle' => 'Manage cooperative members'],
    'products'  => ['title' => 'Products & Stock','subtitle' => 'Manage maize inventory'],
    'clients'   => ['title' => 'Clients',         'subtitle' => 'Manage buyers and clients'],
    'sales'     => ['title' => 'Sales',           'subtitle' => 'Record and manage sales transactions'],
    'reports'   => ['title' => 'Reports',         'subtitle' => 'Generate sales and stock reports'],
];

$pt = $page_titles[$current_page] ?? ['title' => 'UMUHUZA', 'subtitle' => 'Cooperative Management'];
$username = clean($_SESSION['admin_username'] ?? 'Admin');
$initials = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($pt['title']) ?> - UMUHUZA Cooperative</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-wrapper">

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon"><i class="fas fa-wheat-awn" style="color:#fff"></i></div>
            <div class="sidebar-logo">
                UMUHUZA<span>Cooperative Management</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main Menu</div>
                <?php foreach ($nav_items as $item): ?>
                <a href="<?= $item['href'] ?>" class="nav-item <?= $current_page === $item['page'] ? 'active' : '' ?>">
                    <i class="fas <?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar"><?= $initials ?></div>
                <div class="user-info">
                    <div class="user-name"><?= $username ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
            <a href="logout.php" class="btn-logout" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fas fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </aside>

    <!-- ===== HEADER ===== -->
    <header class="app-header">
        <div style="display:flex;align-items:center;gap:14px;">
            <button onclick="document.getElementById('sidebar').classList.toggle('active')"
                style="display:none;background:transparent;border:none;color:var(--text-primary);font-size:1.2rem;cursor:pointer;" id="menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header-title">
                <h2><?= clean($pt['title']) ?></h2>
                <p><?= clean($pt['subtitle']) ?></p>
            </div>
        </div>
        <div class="header-right">
            <div class="header-stat">
                <i class="fas fa-calendar"></i>
                <span><?= date('d M Y') ?></span>
            </div>
            <a href="logout.php" class="btn btn-sm btn-secondary" onclick="return confirm('Logout?')">
                <i class="fas fa-right-from-bracket"></i>
            </a>
        </div>
    </header>

    <!-- ===== MAIN CONTENT STARTS ===== -->
    <main class="main-content">

    <?php
    // Display flash messages
    $flash = getFlash();
    if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" id="flash-alert">
        <i class="fas <?= $flash['type'] === 'success' ? 'fa-circle-check' : ($flash['type'] === 'danger' ? 'fa-circle-xmark' : 'fa-circle-info') ?>"></i>
        <span><?= clean($flash['message']) ?></span>
    </div>
    <script>setTimeout(() => { const a = document.getElementById('flash-alert'); if(a) a.style.opacity='0'; }, 4000);</script>
    <?php endif; ?>
