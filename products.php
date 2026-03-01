<?php
require_once 'auth_check.php';
require_once 'config.php';

$db = getDB();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// DELETE
if ($action === 'delete' && $id > 0) {
    $db->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    setFlash('success', 'Product record deleted successfully.');
    redirect('products.php');
}

// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create','update'])) {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $type      = trim($_POST['type'] ?? '');
    $quantity  = (float)($_POST['quantity'] ?? 0);
    $price     = (float)($_POST['price'] ?? 0);

    $err = [];
    if (!$member_id)         $err[] = 'Please select a member.';
    if (!$type)              $err[] = 'Product type is required.';
    if ($quantity <= 0)      $err[] = 'Quantity must be greater than 0.';
    if ($price <= 0)         $err[] = 'Price must be greater than 0.';

    if (empty($err)) {
        if ($action === 'create') {
            $db->prepare("INSERT INTO products (member_id,type,quantity,price) VALUES (?,?,?,?)")
               ->execute([$member_id,$type,$quantity,$price]);
            setFlash('success', 'Product added successfully.');
        } else {
            $db->prepare("UPDATE products SET member_id=?,type=?,quantity=?,price=? WHERE id=?")
               ->execute([$member_id,$type,$quantity,$price,$id]);
            setFlash('success', 'Product updated successfully.');
        }
        redirect('products.php');
    } else {
        setFlash('danger', implode(' ', $err));
    }
}

// Search & Pagination
$search   = trim($_GET['search'] ?? '');
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset   = ($page_num - 1) * $per_page;

$where  = "WHERE 1=1";
$params = [];
if ($search) {
    $where  .= " AND (p.type LIKE ? OR m.name LIKE ?)";
    $like   = "%$search%";
    $params = [$like, $like];
}

$total = $db->prepare("SELECT COUNT(*) FROM products p JOIN members m ON p.member_id=m.id $where");
$total->execute($params);
$total = $total->fetchColumn();
$pages = ceil($total / $per_page);

$stmt = $db->prepare("
    SELECT p.*, m.name as member_name
    FROM products p
    JOIN members m ON p.member_id = m.id
    $where
    ORDER BY p.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$products = $stmt->fetchAll();

$members = $db->query("SELECT id, name FROM members ORDER BY name")->fetchAll();

// Stock thresholds (kg)
define('STOCK_LOW', 100);
define('STOCK_MEDIUM', 500);

include 'includes/header.php';
?>

<div class="section-header">
    <div class="section-title">
        <i class="fas fa-wheat-awn"></i>
        <h2>Products & Stock <span class="badge badge-primary"><?= $total ?></span></h2>
    </div>
    <div class="section-actions">
        <form method="GET" class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search by type or member..." value="<?= clean($search) ?>">
        </form>
        <button class="btn btn-primary btn-sm" onclick="resetProductForm();openModal('product-modal')">
            <i class="fas fa-plus"></i> Add Product
        </button>
    </div>
</div>

<!-- Stock Summary Cards -->
<?php
$low_count  = $db->query("SELECT COUNT(*) FROM products WHERE quantity <= " . STOCK_LOW)->fetchColumn();
$total_qty  = $db->query("SELECT COALESCE(SUM(quantity),0) FROM products")->fetchColumn();
$total_val  = $db->query("SELECT COALESCE(SUM(quantity*price),0) FROM products")->fetchColumn();
?>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;">
    <div class="glass-card" style="padding:20px;display:flex;align-items:center;gap:16px;">
        <div class="stat-icon"><i class="fas fa-boxes-stacked"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:700;font-family:var(--font-heading);"><?= number_format($total_qty,0) ?> kg</div>
            <div style="font-size:0.8rem;color:var(--text-muted);">Total Stock Available</div>
        </div>
    </div>
    <div class="glass-card" style="padding:20px;display:flex;align-items:center;gap:16px;">
        <div class="stat-icon red"><i class="fas fa-triangle-exclamation"></i></div>
        <div>
            <div style="font-size:1.4rem;font-weight:700;font-family:var(--font-heading);color:var(--danger);"><?= $low_count ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);">Low Stock Items (≤<?= STOCK_LOW ?>kg)</div>
        </div>
    </div>
    <div class="glass-card" style="padding:20px;display:flex;align-items:center;gap:16px;">
        <div class="stat-icon orange"><i class="fas fa-money-bill"></i></div>
        <div>
            <div style="font-size:1.2rem;font-weight:700;font-family:var(--font-heading);color:var(--warning);"><?= formatCurrency($total_val) ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);">Estimated Stock Value</div>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Member</th>
                <th>Product Type</th>
                <th>Price/kg (RWF)</th>
                <th>Quantity (kg)</th>
                <th>Stock Value</th>
                <th>Stock Status</th>
                <th>Added On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($products)): ?>
            <?php foreach ($products as $i => $p):
                $status = $p['quantity'] <= STOCK_LOW ? 'low' : ($p['quantity'] <= STOCK_MEDIUM ? 'medium' : 'high');
                $badge  = $status === 'low' ? 'badge-danger' : ($status === 'medium' ? 'badge-warning' : 'badge-success');
                $label  = $status === 'low' ? 'Low Stock' : ($status === 'medium' ? 'Medium' : 'In Stock');
                $pct    = min(100, round(($p['quantity'] / STOCK_MEDIUM) * 100));
            ?>
            <tr>
                <td><?= $offset + $i + 1 ?></td>
                <td><strong><?= clean($p['member_name']) ?></strong></td>
                <td><span class="badge badge-info"><?= clean($p['type']) ?></span></td>
                <td><?= number_format($p['price'], 0) ?></td>
                <td>
                    <strong><?= number_format($p['quantity'], 0) ?></strong> kg
                    <div class="stock-bar-wrap">
                        <div class="stock-bar-fill <?= $status ?>" style="width:<?= min(100,$pct) ?>%"></div>
                    </div>
                </td>
                <td style="color:var(--primary-accent);"><?= formatCurrency($p['quantity'] * $p['price']) ?></td>
                <td><span class="badge <?= $badge ?>"><i class="fas fa-circle" style="font-size:0.5rem"></i> <?= $label ?></span></td>
                <td><?= formatDate($p['created_at']) ?></td>
                <td>
                    <div class="table-actions">
                        <button class="btn btn-warning btn-sm btn-icon"
                            onclick="editProduct(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)"
                            title="Edit"><i class="fas fa-pencil"></i></button>
                        <a href="products.php?action=delete&id=<?= $p['id'] ?>"
                           class="btn btn-danger btn-sm btn-icon"
                           onclick="return confirm('Delete this product record?')"
                           title="Delete"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="9" style="text-align:center;padding:50px;color:var(--text-muted);">
                    <i class="fas fa-wheat-awn" style="font-size:2.5rem;opacity:0.25;display:block;margin-bottom:10px;"></i>
                    <?= $search ? 'No products found.' : 'No products added yet.' ?>
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

<!-- Add/Edit Product Modal -->
<div class="modal-overlay hidden" id="product-modal">
    <div class="modal glass-card">
        <div class="modal-header">
            <h3><i class="fas fa-wheat-awn"></i> <span id="prod-modal-title">Add Product</span></h3>
            <button class="modal-close" onclick="closeModal('product-modal')">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" id="prod-action" value="create">
            <input type="hidden" name="id" id="prod-id" value="">

            <div class="form-group">
                <label>Member <span style="color:var(--danger)">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <select name="member_id" id="prod-member" required>
                        <option value="">-- Select Member --</option>
                        <?php foreach ($members as $mem): ?>
                        <option value="<?= $mem['id'] ?>"><?= clean($mem['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Maize Type / Description <span style="color:var(--danger)">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-tag"></i>
                    <input type="text" name="type" id="prod-type" placeholder="e.g. White Maize, Yellow Maize" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Quantity (kg) <span style="color:var(--danger)">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-weight-scale"></i>
                        <input type="number" name="quantity" id="prod-quantity" placeholder="0.00" step="0.01" min="0.01" required onchange="calcValue()">
                    </div>
                </div>
                <div class="form-group">
                    <label>Price per kg (RWF) <span style="color:var(--danger)">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-money-bill"></i>
                        <input type="number" name="price" id="prod-price" placeholder="0" step="1" min="1" required onchange="calcValue()">
                    </div>
                </div>
            </div>

            <div class="sale-total-box" id="prod-value-box" style="display:none;">
                <div class="total-row grand">
                    <span>Estimated Value:</span>
                    <span id="prod-value">RWF 0</span>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('product-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Product</button>
            </div>
        </form>
    </div>
</div>

<script>
function calcValue() {
    const q = parseFloat(document.getElementById('prod-quantity').value) || 0;
    const p = parseFloat(document.getElementById('prod-price').value) || 0;
    const box = document.getElementById('prod-value-box');
    const val = document.getElementById('prod-value');
    if (q > 0 && p > 0) {
        box.style.display = 'block';
        val.textContent = 'RWF ' + (q * p).toLocaleString();
    } else {
        box.style.display = 'none';
    }
}

function resetProductForm() {
    document.getElementById('prod-modal-title').textContent = 'Add Product';
    document.getElementById('prod-action').value = 'create';
    document.getElementById('prod-id').value = '';
    document.getElementById('prod-member').value = '';
    document.getElementById('prod-type').value = '';
    document.getElementById('prod-quantity').value = '';
    document.getElementById('prod-price').value = '';
    document.getElementById('prod-value-box').style.display = 'none';
}

function editProduct(p) {
    document.getElementById('prod-modal-title').textContent = 'Edit Product';
    document.getElementById('prod-action').value = 'update';
    document.getElementById('prod-id').value = p.id;
    document.getElementById('prod-member').value = p.member_id;
    document.getElementById('prod-type').value = p.type;
    document.getElementById('prod-quantity').value = p.quantity;
    document.getElementById('prod-price').value = p.price;
    calcValue();
    openModal('product-modal');
}
</script>

<?php include 'includes/footer.php'; ?>
