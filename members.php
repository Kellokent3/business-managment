<?php
require_once 'auth_check.php';
require_once 'config.php';

$db = getDB();

// ===== HANDLE ACTIONS =====
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// DELETE
if ($action === 'delete' && $id > 0) {
    $db->prepare("DELETE FROM members WHERE id=?")->execute([$id]);
    setFlash('success', 'Member deleted successfully.');
    redirect('members.php');
}

// CREATE / UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['create','update'])) {
    $name      = trim($_POST['name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $village   = trim($_POST['village'] ?? '');
    $join_date = trim($_POST['join_date'] ?? '');

    $err = [];
    if (!$name)      $err[] = 'Name is required.';
    if (!$phone)     $err[] = 'Phone is required.';
    if (!$village)   $err[] = 'Village is required.';
    if (!$join_date) $err[] = 'Join date is required.';

    if (empty($err)) {
        if ($action === 'create') {
            $db->prepare("INSERT INTO members (name,phone,village,join_date) VALUES (?,?,?,?)")
               ->execute([$name,$phone,$village,$join_date]);
            setFlash('success', 'Member "' . $name . '" added successfully.');
        } else {
            $db->prepare("UPDATE members SET name=?,phone=?,village=?,join_date=? WHERE id=?")
               ->execute([$name,$phone,$village,$join_date,$id]);
            setFlash('success', 'Member updated successfully.');
        }
        redirect('members.php');
    } else {
        setFlash('danger', implode(' ', $err));
    }
}

// EDIT - get member data
$edit_member = null;
if ($action === 'edit' && $id > 0) {
    $edit_member = $db->prepare("SELECT * FROM members WHERE id=?");
    $edit_member->execute([$id]);
    $edit_member = $edit_member->fetch();
}

// ===== FETCH WITH SEARCH =====
$search = trim($_GET['search'] ?? '');
$page_num = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset   = ($page_num - 1) * $per_page;

$where = '';
$params = [];
if ($search) {
    $where  = "WHERE name LIKE ? OR phone LIKE ? OR village LIKE ?";
    $like   = "%$search%";
    $params = [$like, $like, $like];
}

$total = $db->prepare("SELECT COUNT(*) FROM members $where");
$total->execute($params);
$total = $total->fetchColumn();
$pages = ceil($total / $per_page);

$stmt = $db->prepare("SELECT * FROM members $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$stmt->execute($params);
$members = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="section-header">
    <div class="section-title">
        <i class="fas fa-users"></i>
        <h2>Members <span class="badge badge-primary"><?= $total ?></span></h2>
    </div>
    <div class="section-actions">
        <form method="GET" class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search members..." value="<?= clean($search) ?>">
        </form>
        <button class="btn btn-primary btn-sm" onclick="openModal('member-modal')">
            <i class="fas fa-plus"></i> Add Member
        </button>
    </div>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Village</th>
                <th>Join Date</th>
                <th>Total Stock (kg)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($members)): ?>
            <?php foreach ($members as $i => $m):
                $stock_stmt = $db->prepare("SELECT COALESCE(SUM(quantity),0) FROM products WHERE member_id=?");
                $stock_stmt->execute([$m['id']]);
                $stock = $stock_stmt->fetchColumn();
            ?>
            <tr>
                <td><?= $offset + $i + 1 ?></td>
                <td><strong><?= clean($m['name']) ?></strong></td>
                <td><?= clean($m['phone']) ?></td>
                <td><?= clean($m['village']) ?></td>
                <td><?= formatDate($m['join_date']) ?></td>
                <td>
                    <span class="badge <?= $stock > 0 ? 'badge-success' : 'badge-danger' ?>">
                        <?= number_format($stock,0) ?> kg
                    </span>
                </td>
                <td>
                    <div class="table-actions">
                        <button class="btn btn-warning btn-sm btn-icon"
                            onclick="editMember(<?= htmlspecialchars(json_encode($m), ENT_QUOTES) ?>)"
                            title="Edit"><i class="fas fa-pencil"></i></button>
                        <a href="members.php?action=delete&id=<?= $m['id'] ?>"
                           class="btn btn-danger btn-sm btn-icon"
                           onclick="return confirm('Delete member <?= clean($m['name']) ?>? This will also delete their products.')"
                           title="Delete"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="7" style="text-align:center;padding:50px;color:var(--text-muted);">
                    <i class="fas fa-users" style="font-size:2.5rem;opacity:0.25;display:block;margin-bottom:10px;"></i>
                    <?= $search ? 'No members found for "' . clean($search) . '"' : 'No members added yet.' ?>
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
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

<!-- Add/Edit Member Modal -->
<div class="modal-overlay hidden" id="member-modal">
    <div class="modal glass-card">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> <span id="modal-title">Add Member</span></h3>
            <button class="modal-close" onclick="closeModal('member-modal')">&times;</button>
        </div>
        <form method="POST" class="modal-form">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id" id="form-id" value="">

            <div class="form-group">
                <label>Full Name <span style="color:var(--danger)">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" name="name" id="f-name" placeholder="Enter member's full name" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone Number <span style="color:var(--danger)">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone"></i>
                        <input type="text" name="phone" id="f-phone" placeholder="e.g. 0788123456" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Village <span style="color:var(--danger)">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-map-marker-alt"></i>
                        <input type="text" name="village" id="f-village" placeholder="Village name" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Join Date <span style="color:var(--danger)">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-calendar"></i>
                    <input type="date" name="join_date" id="f-join_date" required>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('member-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Member</button>
            </div>
        </form>
    </div>
</div>

<script>
function editMember(m) {
    document.getElementById('modal-title').textContent = 'Edit Member';
    document.getElementById('form-action').value = 'update';
    document.getElementById('form-id').value = m.id;
    document.getElementById('f-name').value = m.name;
    document.getElementById('f-phone').value = m.phone;
    document.getElementById('f-village').value = m.village;
    document.getElementById('f-join_date').value = m.join_date;
    openModal('member-modal');
}

function resetMemberForm() {
    document.getElementById('modal-title').textContent = 'Add Member';
    document.getElementById('form-action').value = 'create';
    document.getElementById('form-id').value = '';
    document.getElementById('f-name').value = '';
    document.getElementById('f-phone').value = '';
    document.getElementById('f-village').value = '';
    document.getElementById('f-join_date').value = '';
}
document.querySelector('[onclick="openModal(\'member-modal\')"]')?.addEventListener('click', resetMemberForm);
</script>

<?php
// Auto-open modal if validation failed
$flash = $_SESSION['flash'] ?? null;
if ($flash && $flash['type'] === 'danger' && $_SERVER['REQUEST_METHOD'] === 'POST'):
?>
<script>openModal('member-modal');</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
