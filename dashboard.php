<?php
require_once 'auth_check.php';
require_once 'config.php';

$db = getDB();

// Fetch dashboard stats
$members_count = $db->query("SELECT COUNT(*) FROM members")->fetchColumn();
$products_count = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$clients_count  = $db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$sales_count    = $db->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$total_revenue  = $db->query("SELECT COALESCE(SUM(total), 0) FROM sales")->fetchColumn();
$total_stock    = $db->query("SELECT COALESCE(SUM(quantity), 0) FROM products")->fetchColumn();

// Monthly sales (last 6 months)
$monthly = $db->query("
    SELECT DATE_FORMAT(sale_date,'%b') as month, SUM(total) as revenue
    FROM sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(sale_date,'%Y-%m')
    ORDER BY sale_date ASC
    LIMIT 6
")->fetchAll();

// Recent sales
$recent_sales = $db->query("
    SELECT s.id, s.total, s.sale_date, c.name as client_name, p.type as product_type
    FROM sales s
    JOIN clients c ON s.client_id = c.id
    JOIN products p ON s.product_id = p.id
    ORDER BY s.created_at DESC LIMIT 6
")->fetchAll();

// Top members by product quantity
$top_members = $db->query("
    SELECT m.name, COALESCE(SUM(p.quantity), 0) as total_qty
    FROM members m
    LEFT JOIN products p ON m.id = p.member_id
    GROUP BY m.id, m.name
    ORDER BY total_qty DESC
    LIMIT 5
")->fetchAll();

$max_qty = max(array_column($top_members, 'total_qty') ?: [1]);

include 'includes/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card glass-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?= number_format($members_count) ?></div>
        <div class="stat-label">Total Members</div>
        <div class="stat-change"><i class="fas fa-arrow-trend-up"></i> Active farmers</div>
    </div>
    <div class="stat-card glass-card">
        <div class="stat-icon purple"><i class="fas fa-wheat-awn"></i></div>
        <div class="stat-value"><?= number_format($total_stock, 0) ?> <small style="font-size:1rem">kg</small></div>
        <div class="stat-label">Total Stock</div>
        <div class="stat-change"><i class="fas fa-box"></i> <?= $products_count ?> product entries</div>
    </div>
    <div class="stat-card glass-card">
        <div class="stat-icon orange"><i class="fas fa-cart-shopping"></i></div>
        <div class="stat-value"><?= number_format($sales_count) ?></div>
        <div class="stat-label">Total Sales</div>
        <div class="stat-change"><i class="fas fa-handshake"></i> <?= $clients_count ?> clients</div>
    </div>
    <div class="stat-card glass-card">
        <div class="stat-icon red"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-value" style="font-size:1.3rem;"><?= formatCurrency($total_revenue) ?></div>
        <div class="stat-label">Total Revenue</div>
        <div class="stat-change"><i class="fas fa-chart-line"></i> All time</div>
    </div>
</div>

<!-- Dashboard Grid -->
<div class="dashboard-grid">
    <!-- Monthly Revenue Chart -->
    <div class="chart-card glass-card">
        <h3><i class="fas fa-chart-bar"></i> Monthly Revenue (Last 6 Months)</h3>
        <?php if (!empty($monthly)):
            $max_rev = max(array_column($monthly, 'revenue') ?: [1]);
        ?>
        <div class="chart-bars">
            <?php foreach ($monthly as $m):
                $height = $max_rev > 0 ? round(($m['revenue'] / $max_rev) * 130) : 5;
            ?>
            <div class="bar-item">
                <span style="font-size:0.7rem;color:var(--primary-accent);"><?= formatCurrency($m['revenue']) ?></span>
                <div class="bar" style="height:<?= max(5, $height) ?>px;"></div>
                <span class="bar-label"><?= $m['month'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">
            <i class="fas fa-chart-bar" style="font-size:2.5rem;opacity:0.3;display:block;margin-bottom:12px;"></i>
            No sales data yet
        </div>
        <?php endif; ?>
    </div>

    <!-- Top Members -->
    <div class="chart-card glass-card">
        <h3><i class="fas fa-trophy"></i> Top Members by Stock</h3>
        <?php if (!empty($top_members)): ?>
        <?php foreach ($top_members as $i => $m):
            $pct = $max_qty > 0 ? round(($m['total_qty'] / $max_qty) * 100) : 0;
        ?>
        <div style="margin-bottom:16px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:0.85rem;">
                <span style="color:var(--text-primary);font-weight:500;"><?= clean($m['name']) ?></span>
                <span style="color:var(--primary-accent);"><?= number_format($m['total_qty'],0) ?> kg</span>
            </div>
            <div class="stock-bar-wrap">
                <div class="stock-bar-fill" style="width:<?= $pct ?>%"></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div style="text-align:center;padding:30px;color:var(--text-muted);">No members yet</div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Sales -->
<div style="margin-top:24px;">
    <div class="section-header">
        <div class="section-title">
            <i class="fas fa-clock-rotate-left"></i>
            <h2>Recent Sales</h2>
        </div>
        <a href="sales.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-right"></i> View All</a>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Product Type</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_sales)): ?>
                <?php foreach ($recent_sales as $i => $s): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= clean($s['client_name']) ?></strong></td>
                    <td><span class="badge badge-primary"><?= clean($s['product_type']) ?></span></td>
                    <td style="color:var(--primary-accent);font-weight:600;"><?= formatCurrency($s['total']) ?></td>
                    <td><?= formatDate($s['sale_date']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted);">No sales recorded yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
