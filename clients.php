<?php
require_once 'auth_check.php';
require_once 'config.php';

$db = getDB();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// DELETE
if ($action === 'delete' && $id > 0) {
    $db->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
    setFlash('success', 'Client deleted successfully.');
    redirect('clients.php');
}

// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create','update'])) {
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');

    $err = [];
    if (!$name)     $err[] = 'Client name is required.';
    if (!$phone)    $err[] = 'Phone number is required.';
    if (!$location) $err[] = 'Location is required.';

    if (empty($err)) {
        if ($action === 'create') {
            $db->prepare("INSERT INTO clients (name,phone,location) VALUES (?,?,?)")
               ->execute([$name,$phone,$location]);
            setFlash('success', 'Client "' . $name . '" added successfully.');
        } else {
            $db->prepare("UPDATE clients SET name=?,phone=?,location=? WHERE id=?")
               ->execute([$name,$phone,$location,$id]);
            setFlash('success', 'Client updated successfully.');
        }
        redirect('clients.php');
    } else {
        setFlash('danger', implode(' ', $err));
    }
}

// Search & Pagination
$search   = trim($_GET['search'] ?? '');
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset   = ($page_num - 1) * $per_page;

$where  = '';
$params = [];
if ($search) {
    $where  = "WHERE name LIKE ? OR phone LIKE ? OR location LIKE ?";
    $like   = "%$search%";
    $params = [$like, $like, $like];
}

$total = $db->prepare("SELECT COUNT(*) FROM clients $where");
$total->execute($params);
$total = $total->fetchColumn();
$pages = ceil($total / $per_page);

$stmt = $db->prepare("SELECT * FROM clients $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$clients = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="section-header">
    <div class="section-title">
        <i class="fas fa-handshake"></i>
        <h2>Clients <span class="badge badge-primary"><?= $total ?></span></h2>
    </div>
    <div class="section-actions">
        <form method="GET" class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search clients..." value="<?= clean($search) ?>">
        </form>
        <button class="btn btn-primary btn-sm" onclick="resetClientForm();openModal('client-modal')">
            <i class="fas fa-plus"></i> Add Client
        </button>
    </div>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Client Name</th>
                <th>Phone</th>
                <th>Location</th>
                <th>Total Purchases</th>
                <th>Registered On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($clients)): ?>
            <?php foreach ($clients as $i => $c):
                $purchases = $db->prepare("SELECT COUNT(*), COALESCE(SUM(total),0) FROM sales WHERE client_id=?");
                $purchases->execute([$c['id']]);
                [$count, $total_spent] = $purchases->fetch(PDO::FETCH_NUM);
            ?>
            <tr>
                <td><?= $offset + $i + 1 ?></td>
                <td><strong><?= clean($c['name']) ?></strong></td>
                <td><?= clean($c['phone']) ?></td>
                <td><?= clean($c['location']) ?></td>
                <td>
                    <span class="badge badge-success"><?= $count ?> sales</span>
                    <span style="font-size:0.8rem;color:var(--primary-accent);margin-left:6px;"><?= formatCurrency($total_spent) ?></span>
                </td>
                <td><?= formatDate($c['created_at']) ?></td>
                <td>
                    <div class="table-actions">
                        <button class="btn btn-warning btn-sm btn-icon"
                            onclick="editClient(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)"
                            title="Edit"><i class="fas fa-pencil"></i></button>
                        <a href="clients.php?action=delete&id=<?= $c['id'] ?>"
                           class="btn btn-danger btn-sm btn-icon"
                           onclick="return confirm('Delete client <?= clean($c['name']) ?>?')"
                           title="Delete"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="7" style="text-align:center;padding:50px;color:var(--text-muted);">
                    <i class="fas fa-handshake" style="font-size:2.5rem;opacity:0.25;display:block;margin-bottom:10px;"></i>
                    <?= $search ? 'No clients found.' : 'No clients added yet.' ?>
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

<!-- Add/Edit Client Modal -->
<div class="modal-overlay hidden" id="client-modal">
    <div class="modal glass-card">
        <div class="modal-header">
            <h3><i class="fas fa-user-tie"></i> <span id="client-modal-title">Add Client</span></h3>
            <button class="modal-close" onclick="closeModal('client-modal')">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" id="client-action" value="create">
            <input type="hidden" name="id" id="client-id" value="">

            <div class="form-group">
                <label>Client / Business Name <span style="color:var(--danger)">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-building"></i>
                    <input type="text" name="name" id="c-name" placeholder="Enter client or business name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number <span style="color:var(--danger)">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="text" name="phone" id="c-phone" placeholder="e.g. 0788123456" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Location <span style="color:var(--danger)">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="location" id="c-location" placeholder="City or district" required>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('client-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Client</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetClientForm() {
    document.getElementById('client-modal-title').textContent = 'Add Client';
    document.getElementById('client-action').value = 'create';
    document.getElementById('client-id').value = '';
    document.getElementById('c-name').value = '';
    document.getElementById('c-phone').value = '';
    document.getElementById('c-location').value = '';
}
function editClient(c) {
    document.getElementById('client-modal-title').textContent = 'Edit Client';
    document.getElementById('client-action').value = 'update';
    document.getElementById('client-id').value = c.id;
    document.getElementById('c-name').value = c.name;
    document.getElementById('c-phone').value = c.phone;
    document.getElementById('c-location').value = c.location;
    openModal('client-modal');
}
</script>

<?php include 'includes/footer.php'; ?>
