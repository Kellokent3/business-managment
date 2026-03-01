<?php
require_once 'auth_check.php';
require_once 'config.php';

$db = getDB();

$report_type = $_GET['report'] ?? 'sales';
$date_from   = $_GET['date_from'] ?? date('Y-m-01');
$date_to     = $_GET['date_to']   ?? date('Y-m-d');
$member_id   = (int)($_GET['member_id'] ?? 0);

$members = $db->query("SELECT id, name FROM members ORDER BY name")->fetchAll();

// ===== SALES REPORT =====
$sales_where = "WHERE s.sale_date BETWEEN ? AND ?";
$sales_params = [$date_from, $date_to];
if ($member_id) {
    $sales_where .= " AND p.member_id = ?";
    $sales_params[] = $member_id;
}

$sales_data = $db->prepare("
    SELECT s.*, c.name as client_name, c.location as client_location,
           p.type as product_type, p.price as unit_price,
           m.name as member_name, m.village
    FROM sales s
    JOIN clients c ON s.client_id = c.id
    JOIN products p ON s.product_id = p.id
    JOIN members m ON p.member_id = m.id
    $sales_where
    ORDER BY s.sale_date DESC
");
$sales_data->execute($sales_params);
$sales_data = $sales_data->fetchAll();

$sales_summary = $db->prepare("
    SELECT COUNT(*) as count, COALESCE(SUM(s.total),0) as revenue, COALESCE(SUM(s.quantity),0) as qty
    FROM sales s JOIN products p ON s.product_id=p.id $sales_where
");
$sales_summary->execute($sales_params);
$sales_summary = $sales_summary->fetch();

// ===== STOCK REPORT =====
$stock_data = $db->query("
    SELECT p.*, m.name as member_name, m.village,
           (p.quantity * p.price) as value
    FROM products p
    JOIN members m ON p.member_id = m.id
    ORDER BY p.quantity DESC
")->fetchAll();

$stock_totals = $db->query("
    SELECT COUNT(*) as count,
           COALESCE(SUM(quantity),0) as total_qty,
           COALESCE(SUM(quantity*price),0) as total_value
    FROM products
")->fetch();

// ===== MEMBER CONTRIBUTIONS =====
$contrib_data = $db->prepare("
    SELECT m.id, m.name, m.phone, m.village, m.join_date,
           COUNT(p.id) as product_count,
           COALESCE(SUM(p.quantity),0) as total_qty,
           COALESCE(SUM(p.quantity * p.price),0) as total_value,
           (SELECT COALESCE(SUM(s.total),0) FROM sales s JOIN products pp ON s.product_id=pp.id WHERE pp.member_id=m.id AND s.sale_date BETWEEN ? AND ?) as sales_total
    FROM members m
    LEFT JOIN products p ON m.id = p.member_id
    GROUP BY m.id
    ORDER BY total_qty DESC
");
$contrib_data->execute([$date_from, $date_to]);
$contrib_data = $contrib_data->fetchAll();

include 'includes/header.php';
?>

<!-- Report Type Tabs -->
<div class="section-header">
    <div class="section-title">
        <i class="fas fa-chart-bar"></i>
        <h2>Reports & Analytics</h2>
    </div>
    <div class="section-actions no-print">
        <button onclick="window.print()" class="btn btn-secondary btn-sm">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<!-- Tab Buttons -->
<div style="display:flex;gap:8px;margin-bottom:24px;" class="no-print">
    <a href="?report=sales&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>"
       class="btn btn-sm <?= $report_type==='sales' ? 'btn-primary' : 'btn-secondary' ?>">
        <i class="fas fa-cart-shopping"></i> Sales Report
    </a>
    <a href="?report=stock&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>"
       class="btn btn-sm <?= $report_type==='stock' ? 'btn-primary' : 'btn-secondary' ?>">
        <i class="fas fa-boxes-stacked"></i> Stock Report
    </a>
    <a href="?report=members&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>"
       class="btn btn-sm <?= $report_type==='members' ? 'btn-primary' : 'btn-secondary' ?>">
        <i class="fas fa-users"></i> Member Contributions
    </a>
</div>

<!-- Filters -->
<div class="report-filter-card glass-card no-print">
    <form method="GET">
        <input type="hidden" name="report" value="<?= $report_type ?>">
        <div class="filter-row">
            <div class="filter-group">
                <label><i class="fas fa-calendar-start"></i> From Date</label>
                <input type="date" name="date_from" value="<?= $date_from ?>">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar-end"></i> To Date</label>
                <input type="date" name="date_to" value="<?= $date_to ?>">
            </div>
            <?php if ($report_type === 'sales' || $report_type === 'members'): ?>
            <div class="filter-group">
                <label><i class="fas fa-user"></i> Filter by Member</label>
                <select name="member_id">
                    <option value="0">All Members</option>
                    <?php foreach ($members as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $member_id == $m['id'] ? 'selected' : '' ?>>
                        <?= clean($m['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="filter-group" style="flex:0;">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary" style="width:100%;white-space:nowrap;">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Report Header (for print) -->
<div style="display:none;" class="print-only" id="print-header">
    <div style="text-align:center;margin-bottom:20px;border-bottom:2px solid #000;padding-bottom:16px;">
        <h1 style="color:#000;font-size:1.5rem;">UMUHUZA COOPERATIVE</h1>
        <p style="color:#333;">Eastern Province, Rwanda</p>
        <h2 style="margin-top:8px;color:#000;">
            <?= $report_type === 'sales' ? 'Sales Report' : ($report_type === 'stock' ? 'Stock/Inventory Report' : 'Member Contributions Report') ?>
        </h2>
        <p style="color:#555;">Period: <?= date('d M Y', strtotime($date_from)) ?> — <?= date('d M Y', strtotime($date_to)) ?></p>
        <p style="color:#777;font-size:0.85em;">Generated on: <?= date('d M Y H:i') ?> by <?= clean($_SESSION['admin_username']) ?></p>
    </div>
</div>
<style>
@media print {
    .print-only { display: block !important; }
    body { background: white !important; color: black !important; }
    .glass-card { background: white !important; border: 1px solid #ddd !important; box-shadow: none !important; }
    .data-table th, .data-table td { color: black !important; }
    .data-table thead tr { background: #f0f0f0 !important; }
    .badge { background: #eee !important; color: #333 !important; border: none !important; }
    .no-print { display: none !important; }
    .print-only { display: block !important; }
}
</style>

<?php if ($report_type === 'sales'): ?>
<!-- ===== SALES REPORT ===== -->
<div class="report-summary-grid">
    <div class="summary-stat glass-card">
        <div class="value"><?= number_format($sales_summary['count']) ?></div>
        <div class="label">Total Transactions</div>
    </div>
    <div class="summary-stat glass-card">
        <div class="value"><?= number_format($sales_summary['qty'],0) ?> kg</div>
        <div class="label">Total Quantity Sold</div>
    </div>
    <div class="summary-stat glass-card">
        <div class="value" style="font-size:1.2rem;"><?= formatCurrency($sales_summary['revenue']) ?></div>
        <div class="label">Total Revenue</div>
    </div>
</div>

<div class="table-container">
    <div style="padding:16px 20px;border-bottom:1px solid var(--glass-border);display:flex;align-items:center;justify-content:space-between;">
        <h3 style="display:flex;align-items:center;gap:8px;"><i class="fas fa-cart-shopping" style="color:var(--primary-accent)"></i> Sales Transactions</h3>
        <span style="font-size:0.82rem;color:var(--text-muted);"><?= date('d M Y', strtotime($date_from)) ?> — <?= date('d M Y', strtotime($date_to)) ?></span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Location</th>
                <th>Product Type</th>
                <th>Member</th>
                <th>Qty (kg)</th>
                <th>Unit Price</th>
                <th>Total</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sales_data)): ?>
            <?php foreach ($sales_data as $i => $s): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= clean($s['client_name']) ?></strong></td>
                <td><?= clean($s['client_location']) ?></td>
                <td><span class="badge badge-info"><?= clean($s['product_type']) ?></span></td>
                <td><?= clean($s['member_name']) ?></td>
                <td><?= number_format($s['quantity'], 0) ?></td>
                <td><?= formatCurrency($s['unit_price']) ?></td>
                <td style="color:var(--primary-accent);font-weight:700;"><?= formatCurrency($s['total']) ?></td>
                <td><?= formatDate($s['sale_date']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background:rgba(0,212,170,0.05);font-weight:700;">
                <td colspan="5" style="text-align:right;color:var(--text-muted);">TOTALS:</td>
                <td style="color:var(--primary-accent);"><?= number_format($sales_summary['qty'],0) ?> kg</td>
                <td>—</td>
                <td style="color:var(--primary-accent);"><?= formatCurrency($sales_summary['revenue']) ?></td>
                <td></td>
            </tr>
            <?php else: ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">No sales found for the selected period.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php elseif ($report_type === 'stock'): ?>
<!-- ===== STOCK REPORT ===== -->
<div class="report-summary-grid">
    <div class="summary-stat glass-card">
        <div class="value"><?= $stock_totals['count'] ?></div>
        <div class="label">Product Entries</div>
    </div>
    <div class="summary-stat glass-card">
        <div class="value"><?= number_format($stock_totals['total_qty'],0) ?> kg</div>
        <div class="label">Total Stock</div>
    </div>
    <div class="summary-stat glass-card">
        <div class="value" style="font-size:1.1rem;"><?= formatCurrency($stock_totals['total_value']) ?></div>
        <div class="label">Estimated Value</div>
    </div>
</div>

<div class="table-container">
    <div style="padding:16px 20px;border-bottom:1px solid var(--glass-border);">
        <h3 style="display:flex;align-items:center;gap:8px;"><i class="fas fa-boxes-stacked" style="color:var(--primary-accent)"></i> Current Stock / Inventory</h3>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Member</th>
                <th>Village</th>
                <th>Maize Type</th>
                <th>Price/kg</th>
                <th>Quantity (kg)</th>
                <th>Estimated Value</th>
                <th>Status</th>
                <th>Added On</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($stock_data)):
                $total_qty = 0; $total_val = 0;
                foreach ($stock_data as $i => $s):
                    $total_qty += $s['quantity'];
                    $total_val += $s['value'];
                    $status = $s['quantity'] <= 100 ? 'Low Stock' : ($s['quantity'] <= 500 ? 'Medium' : 'In Stock');
                    $badge  = $s['quantity'] <= 100 ? 'badge-danger' : ($s['quantity'] <= 500 ? 'badge-warning' : 'badge-success');
            ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= clean($s['member_name']) ?></strong></td>
                <td><?= clean($s['village']) ?></td>
                <td><?= clean($s['type']) ?></td>
                <td><?= formatCurrency($s['price']) ?></td>
                <td><strong><?= number_format($s['quantity'],0) ?></strong></td>
                <td style="color:var(--primary-accent);"><?= formatCurrency($s['value']) ?></td>
                <td><span class="badge <?= $badge ?>"><?= $status ?></span></td>
                <td><?= formatDate($s['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background:rgba(0,212,170,0.05);font-weight:700;">
                <td colspan="5" style="text-align:right;color:var(--text-muted);">TOTALS:</td>
                <td style="color:var(--primary-accent);"><?= number_format($total_qty,0) ?> kg</td>
                <td style="color:var(--primary-accent);"><?= formatCurrency($total_val) ?></td>
                <td colspan="2"></td>
            </tr>
            <?php else: ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">No stock data available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php else: ?>
<!-- ===== MEMBER CONTRIBUTIONS ===== -->
<div class="report-summary-grid">
    <div class="summary-stat glass-card">
        <div class="value"><?= count($contrib_data) ?></div>
        <div class="label">Total Members</div>
    </div>
    <div class="summary-stat glass-card">
        <div class="value"><?= number_format(array_sum(array_column($contrib_data,'total_qty')),0) ?> kg</div>
        <div class="label">Total Contributions</div>
    </div>
    <div class="summary-stat glass-card">
        <div class="value" style="font-size:1.1rem;"><?= formatCurrency(array_sum(array_column($contrib_data,'sales_total'))) ?></div>
        <div class="label">Total Sales Revenue</div>
    </div>
</div>

<div class="table-container">
    <div style="padding:16px 20px;border-bottom:1px solid var(--glass-border);">
        <h3 style="display:flex;align-items:center;gap:8px;"><i class="fas fa-users" style="color:var(--primary-accent)"></i> Member Contributions Report</h3>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Member Name</th>
                <th>Phone</th>
                <th>Village</th>
                <th>Join Date</th>
                <th>Products</th>
                <th>Stock (kg)</th>
                <th>Stock Value</th>
                <th>Sales Revenue</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($contrib_data)): ?>
            <?php foreach ($contrib_data as $i => $m): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><strong><?= clean($m['name']) ?></strong></td>
                <td><?= clean($m['phone']) ?></td>
                <td><?= clean($m['village']) ?></td>
                <td><?= formatDate($m['join_date']) ?></td>
                <td><span class="badge badge-info"><?= $m['product_count'] ?></span></td>
                <td style="font-weight:600;"><?= number_format($m['total_qty'],0) ?></td>
                <td style="color:var(--warning);"><?= formatCurrency($m['total_value']) ?></td>
                <td style="color:var(--primary-accent);font-weight:700;"><?= formatCurrency($m['sales_total']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background:rgba(0,212,170,0.05);font-weight:700;">
                <td colspan="6" style="text-align:right;color:var(--text-muted);">TOTALS:</td>
                <td style="color:var(--primary-accent);"><?= number_format(array_sum(array_column($contrib_data,'total_qty')),0) ?> kg</td>
                <td style="color:var(--warning);"><?= formatCurrency(array_sum(array_column($contrib_data,'total_value'))) ?></td>
                <td style="color:var(--primary-accent);"><?= formatCurrency(array_sum(array_column($contrib_data,'sales_total'))) ?></td>
            </tr>
            <?php else: ?>
            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">No member data available.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
