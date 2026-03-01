<?php
require_once 'auth_check.php';
require_once 'config.php';

$db = getDB();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// DELETE
if ($action === 'delete' && $id > 0) {
    // Restore stock before deleting
    $sale = $db->prepare("SELECT * FROM sales WHERE id=?");
    $sale->execute([$id]);
    $sale = $sale->fetch();
    if ($sale) {
        $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id=?")->execute([$sale['quantity'], $sale['product_id']]);
        $db->prepare("DELETE FROM sales WHERE id=?")->execute([$id]);
        setFlash('success', 'Sale deleted and stock restored.');
    }
    redirect('sales.php');
}

// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create','update'])) {
    $client_id  = (int)($_POST['client_id'] ?? 0);
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity   = (float)($_POST['quantity'] ?? 0);
    $sale_date  = trim($_POST['sale_date'] ?? '');

    $err = [];
    if (!$client_id)  $err[] = 'Please select a client.';
    if (!$product_id) $err[] = 'Please select a product.';
    if ($quantity <= 0) $err[] = 'Quantity must be greater than 0.';
    if (!$sale_date)  $err[] = 'Sale date is required.';

    if (empty($err)) {
        // Get product price and available stock
        $prod = $db->prepare("SELECT * FROM products WHERE id=?");
        $prod->execute([$product_id]);
        $prod = $prod->fetch();

        if (!$prod) {
            setFlash('danger', 'Selected product not found.');
            redirect('sales.php');
        }

        if ($action === 'create') {
            if ($prod['quantity'] < $quantity) {
                setFlash('danger', 'Insufficient stock. Available: ' . $prod['quantity'] . ' kg');
            } else {
                $total = $quantity * $prod['price'];
                $db->prepare("INSERT INTO sales (client_id,product_id,quantity,total,sale_date) VALUES (?,?,?,?,?)")
                   ->execute([$client_id,$product_id,$quantity,$total,$sale_date]);
                // Deduct stock
                $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id=?")->execute([$quantity,$product_id]);
                setFlash('success', 'Sale recorded! Total: ' . formatCurrency($total) . '. Stock updated.');
            }
        } else {
            // Update: get original sale to restore old quantity
            $old = $db->prepare("SELECT * FROM sales WHERE id=?");
            $old->execute([$id]);
            $old = $old->fetch();
            if ($old) {
                // Restore old stock first
                $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id=?")->execute([$old['quantity'], $old['product_id']]);
                // Check new stock
                $prod_stmt = $db->prepare("SELECT * FROM products WHERE id=?");
                $prod_stmt->execute([$product_id]);
                $new_prod = $prod_stmt->fetch();
                if ($new_prod['quantity'] < $quantity) {
                    // Re-deduct old to keep consistent
                    $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id=?")->execute([$old['quantity'], $old['product_id']]);
                    setFlash('danger', 'Insufficient stock. Available: ' . $new_prod['quantity'] . ' kg');
                } else {
                    $total = $quantity * $new_prod['price'];
                    $db->prepare("UPDATE sales SET client_id=?,product_id=?,quantity=?,total=?,sale_date=? WHERE id=?")
                       ->execute([$client_id,$product_id,$quantity,$total,$sale_date,$id]);
                    $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id=?")->execute([$quantity,$product_id]);
                    setFlash('success', 'Sale updated successfully.');
                }
            }
        }
        redirect('sales.php');
    } else {
        setFlash('danger', implode(' ', $err));
    }
}

// Search & Pagination
$search   = trim($_GET['search'] ?? '');
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset   = ($page_num - 1) * $per_page;

$where  = "WHERE 1=1";
$params = [];
if ($search) {
    $where  .= " AND (c.name LIKE ? OR p.type LIKE ?)";
    $like   = "%$search%";
    $params = [$like, $like];
}

$total = $db->prepare("SELECT COUNT(*) FROM sales s JOIN clients c ON s.client_id=c.id JOIN products p ON s.product_id=p.id $where");
$total->execute($params);
$total = $total->fetchColumn();
$pages = ceil($total / $per_page);

$stmt = $db->prepare("
    SELECT s.*, c.name as client_name, p.type as product_type, p.price as unit_price, m.name as member_name
    FROM sales s
    JOIN clients c ON s.client_id = c.id
    JOIN products p ON s.product_id = p.id
    JOIN members m ON p.member_id = m.id
    $where
    ORDER BY s.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$sales = $stmt->fetchAll();

$clients  = $db->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();
$products = $db->query("SELECT p.id, p.type, p.quantity, p.price, m.name as member FROM products p JOIN members m ON p.member_id=m.id ORDER BY m.name")->fetchAll();

$total_revenue = $db->query("SELECT COALESCE(SUM(total),0) FROM sales")->fetchColumn();
$today_rev     = $db->query("SELECT COALESCE(SUM(total),0) FROM sales WHERE DATE(sale_date)=CURDATE()")->fetchColumn();

// Convert products to JSON for JS
$products_json = json_encode($products);

include 'includes/header.php';
?>

<div class="section-header">
    <div class="section-title">
        <i class="fas fa-cart-shopping"></i>
        <h2>Sales <span class="badge badge-primary"><?= $total ?></span></h2>
    </div>
    <div class="section-actions">
        <form method="GET" class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search by client or product..." value="<?= clean($search) ?>">
        </form>
        <button class="btn btn-primary btn-sm" onclick="resetSaleForm();openModal('sale-modal')">
            <i class="fas fa-plus"></i> Record Sale
        </button>
    </div>
</div>

<!-- Revenue Summary -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <div class="glass-card" style="padding:20px;display:flex;align-items:center;gap:16px;">
        <div class="stat-icon"><i class="fas fa-receipt"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:700;font-family:var(--font-heading);"><?= number_format($total) ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);">Total Transactions</div>
        </div>
    </div>
    <div class="glass-card" style="padding:20px;display:flex;align-items:center;gap:16px;">
        <div class="stat-icon orange"><i class="fas fa-sun"></i></div>
        <div>
            <div style="font-size:1.1rem;font-weight:700;font-family:var(--font-heading);color:var(--warning);"><?= formatCurrency($today_rev) ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);">Today's Revenue</div>
        </div>
    </div>
    <div class="glass-card" style="padding:20px;display:flex;align-items:center;gap:16px;">
        <div class="stat-icon"><i class="fas fa-money-bill-trend-up"></i></div>
        <div>
            <div style="font-size:1.1rem;font-weight:700;font-family:var(--font-heading);color:var(--primary-accent);"><?= formatCurrency($total_revenue) ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);">Total Revenue</div>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Product</th>
                <th>Member</th>
                <th>Qty (kg)</th>
                <th>Unit Price</th>
                <th>Total</th>
                <th>Sale Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sales)): ?>
            <?php foreach ($sales as $i => $s): ?>
            <tr>
                <td><?= $offset + $i + 1 ?></td>
                <td><strong><?= clean($s['client_name']) ?></strong></td>
                <td><span class="badge badge-info"><?= clean($s['product_type']) ?></span></td>
                <td><?= clean($s['member_name']) ?></td>
                <td><?= number_format($s['quantity'], 0) ?> kg</td>
                <td><?= formatCurrency($s['unit_price']) ?></td>
                <td style="color:var(--primary-accent);font-weight:700;"><?= formatCurrency($s['total']) ?></td>
                <td><?= formatDate($s['sale_date']) ?></td>
                <td>
                    <div class="table-actions">
                        <button class="btn btn-warning btn-sm btn-icon"
                            onclick="editSale(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)"
                            title="Edit"><i class="fas fa-pencil"></i></button>
                        <a href="sales.php?action=delete&id=<?= $s['id'] ?>"
                           class="btn btn-danger btn-sm btn-icon"
                           onclick="return confirm('Delete this sale? Stock will be restored.')"
                           title="Delete"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="9" style="text-align:center;padding:50px;color:var(--text-muted);">
                    <i class="fas fa-cart-shopping" style="font-size:2.5rem;opacity:0.25;display:block;margin-bottom:10px;"></i>
                    <?= $search ? 'No sales found.' : 'No sales recorded yet.' ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
            <?php if ($p == $page_num): ?>
                <span class="current"><?= $p ?></span>
            <?php else: ?>
                <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Sale Modal -->
<div class="modal-overlay hidden" id="sale-modal">
    <div class="modal glass-card">
        <div class="modal-header">
            <h3><i class="fas fa-cart-plus"></i> <span id="sale-modal-title">Record Sale</span></h3>
            <button class="modal-close" onclick="closeModal('sale-modal')">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" id="sale-action" value="create">
            <input type="hidden" name="id" id="sale-id" value="">

            <div class="form-group">
                <label>Client <span style="color:var(--danger)">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user-tie"></i>
                    <select name="client_id" id="s-client" required>
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= clean($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Product <span style="color:var(--danger)">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-wheat-awn"></i>
                    <select name="product_id" id="s-product" required onchange="onProductChange()">
                        <option value="">-- Select Product --</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"
                            data-price="<?= $p['price'] ?>"
                            data-stock="<?= $p['quantity'] ?>">
                            <?= clean($p['type']) ?> — <?= clean($p['member']) ?>
                            (<?= number_format($p['quantity'],0) ?> kg avail.)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="product-info" style="display:none;padding:10px 14px;background:rgba(0,212,170,0.05);border:1px solid rgba(0,212,170,0.2);border-radius:var(--radius-md);margin-bottom:16px;font-size:0.85rem;color:var(--text-secondary);">
                <span>Price: <strong id="info-price" style="color:var(--primary-accent)"></strong></span> &nbsp;|&nbsp;
                <span>Available: <strong id="info-stock" style="color:var(--warning)"></strong></span>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Quantity (kg) <span style="color:var(--danger)">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-weight-scale"></i>
                        <input type="number" name="quantity" id="s-quantity" placeholder="0" step="0.01" min="0.01" required oninput="calcTotal()">
                    </div>
                </div>
                <div class="form-group">
                    <label>Sale Date <span style="color:var(--danger)">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-calendar"></i>
                        <input type="date" name="sale_date" id="s-date" required>
                    </div>
                </div>
            </div>

            <div class="sale-total-box" id="total-box">
                <div class="total-row">
                    <span>Price per kg:</span>
                    <span id="t-price">RWF 0</span>
                </div>
                <div class="total-row">
                    <span>Quantity:</span>
                    <span id="t-qty">0 kg</span>
                </div>
                <div class="total-row grand">
                    <span>Total Amount:</span>
                    <span id="t-total">RWF 0</span>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('sale-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Sale</button>
            </div>
        </form>
    </div>
</div>

<script>
const PRODUCTS = <?= $products_json ?>;

function onProductChange() {
    const sel = document.getElementById('s-product');
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('product-info');
    if (opt.value) {
        document.getElementById('info-price').textContent = 'RWF ' + Number(opt.dataset.price).toLocaleString() + '/kg';
        document.getElementById('info-stock').textContent = Number(opt.dataset.stock).toLocaleString() + ' kg';
        info.style.display = 'block';
    } else {
        info.style.display = 'none';
    }
    calcTotal();
}

function calcTotal() {
    const sel = document.getElementById('s-product');
    const opt = sel.options[sel.selectedIndex];
    const qty = parseFloat(document.getElementById('s-quantity').value) || 0;
    const price = parseFloat(opt?.dataset?.price) || 0;
    document.getElementById('t-price').textContent = 'RWF ' + price.toLocaleString();
    document.getElementById('t-qty').textContent = qty.toLocaleString() + ' kg';
    document.getElementById('t-total').textContent = 'RWF ' + (qty * price).toLocaleString();
}

function resetSaleForm() {
    document.getElementById('sale-modal-title').textContent = 'Record Sale';
    document.getElementById('sale-action').value = 'create';
    document.getElementById('sale-id').value = '';
    document.getElementById('s-client').value = '';
    document.getElementById('s-product').value = '';
    document.getElementById('s-quantity').value = '';
    document.getElementById('s-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('product-info').style.display = 'none';
    calcTotal();
}

function editSale(s) {
    document.getElementById('sale-modal-title').textContent = 'Edit Sale';
    document.getElementById('sale-action').value = 'update';
    document.getElementById('sale-id').value = s.id;
    document.getElementById('s-client').value = s.client_id;
    document.getElementById('s-product').value = s.product_id;
    document.getElementById('s-quantity').value = s.quantity;
    document.getElementById('s-date').value = s.sale_date;
    onProductChange();
    openModal('sale-modal');
}

// Set today's date on load
document.getElementById('s-date').value = new Date().toISOString().split('T')[0];
</script>

<?php include 'includes/footer.php'; ?>
