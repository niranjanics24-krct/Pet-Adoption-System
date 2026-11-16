<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_admin();

$pageTitle = 'Manage Pets Inventory | Pet Haven';
$activePage = 'admin_pets';

// Handle quick actions: Delete pet or quick change status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Security token expired.');
        header('Location: admin_pets.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $petId = (int)($_POST['pet_id'] ?? 0);

    if ($action === 'delete' && $petId > 0) {
        // Check if pet has requests
        $del = $conn->prepare("DELETE FROM pets WHERE id = ?");
        $del->bind_param("i", $petId);
        if ($del->execute()) {
            flash_set('success', "Pet #$petId deleted successfully.");
        } else {
            flash_set('error', "Could not delete pet: " . $conn->error);
        }
        $del->close();
        header('Location: admin_pets.php');
        exit;
    }

    if ($action === 'update_status' && $petId > 0) {
        $newStatus = trim($_POST['status'] ?? '');
        $allowed = ['Available', 'Pending', 'Trial', 'Adopted'];
        if (in_array($newStatus, $allowed)) {
            $up = $conn->prepare("UPDATE pets SET status = ? WHERE id = ?");
            $up->bind_param("si", $newStatus, $petId);
            if ($up->execute()) {
                flash_set('success', "Pet #$petId status updated to $newStatus.");
            }
            $up->close();
        }
        header('Location: admin_pets.php');
        exit;
    }
}

// Filters & Search
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$type = trim($_GET['type'] ?? '');

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(name LIKE ? OR breed LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

if ($status !== '' && $status !== 'All') {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($type !== '' && $type !== 'All') {
    $where[] = "type = ?";
    $params[] = $type;
    $types .= 's';
}

$sql = "SELECT * FROM pets";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$pets = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 35px; margin-bottom: 50px;">
    <!-- Top Action Bar -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:25px;">
        <div>
            <h1 style="font-size:28px; color:var(--primary-forest); margin:0 0 6px;">Manage Pets Inventory</h1>
            <p style="color:var(--text-muted); margin:0;">Create, edit, upload photos, and update pet availability statuses.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="admin_pet_form.php" class="btn btn-primary">+ Add New Pet Profile</a>
            <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-card">
        <form method="GET" action="admin_pets.php" class="filter-grid">
            <div class="form-group" style="margin-bottom:0;">
                <label for="search">Search Pet Name or Breed</label>
                <input type="text" id="search" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="e.g. Bella...">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label for="type">Species</label>
                <select id="type" name="type" class="form-control">
                    <option value="All">All Species</option>
                    <option value="Dog" <?php echo $type === 'Dog' ? 'selected' : ''; ?>>Dog</option>
                    <option value="Cat" <?php echo $type === 'Cat' ? 'selected' : ''; ?>>Cat</option>
                    <option value="Rabbit" <?php echo $type === 'Rabbit' ? 'selected' : ''; ?>>Rabbit</option>
                    <option value="Bird" <?php echo $type === 'Bird' ? 'selected' : ''; ?>>Bird</option>
                    <option value="Other" <?php echo $type === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label for="status">Availability Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="All">All Statuses</option>
                    <option value="Available" <?php echo $status === 'Available' ? 'selected' : ''; ?>>Available</option>
                    <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Trial" <?php echo $status === 'Trial' ? 'selected' : ''; ?>>Trial</option>
                    <option value="Adopted" <?php echo $status === 'Adopted' ? 'selected' : ''; ?>>Adopted</option>
                </select>
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Filter</button>
                <a href="admin_pets.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Pet Records Table -->
    <div class="content-card">
        <?php if ($pets && $pets->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Species & Breed</th>
                            <th>Age & Gender</th>
                            <th>Current Status</th>
                            <th>Quick Status Change</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $pets->fetch_assoc()): ?>
                            <?php 
                                $statBadge = 'status-' . strtolower($p['status']);
                                $img = $p['image'] ?: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=150&q=80';
                            ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="" style="width:48px; height:48px; border-radius:8px; object-fit:cover;">
                                        <div>
                                            <a href="pet_details.php?id=<?php echo (int)$p['id']; ?>" target="_blank" style="font-weight:700; color:var(--primary-forest);">
                                                <?php echo htmlspecialchars($p['name']); ?>
                                            </a>
                                            <div style="font-size:12px; color:var(--text-muted);">ID #<?php echo $p['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div><strong><?php echo htmlspecialchars($p['type'] ?? 'Unknown'); ?></strong></div>
                                    <div style="font-size:13px; color:var(--text-muted);"><?php echo htmlspecialchars($p['breed'] ?? 'Mixed'); ?></div>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($p['gender'] ?? 'Unknown'); ?></div>
                                    <div style="font-size:13px; color:var(--text-muted);"><?php echo htmlspecialchars($p['age'] ?? 'Not specified'); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $p['status'] === 'Available' ? 'badge-success' : ($p['status'] === 'Adopted' ? 'badge-primary' : 'badge-warning'); ?>">
                                        <?php echo htmlspecialchars($p['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="admin_pets.php" style="display:inline-flex; gap:6px; align-items:center;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="pet_id" value="<?php echo $p['id']; ?>">
                                        <select name="status" class="form-control" style="padding:4px 8px; font-size:13px; width:auto;" onchange="this.form.submit();">
                                            <option value="Available" <?php echo $p['status'] === 'Available' ? 'selected' : ''; ?>>Available</option>
                                            <option value="Pending" <?php echo $p['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="Trial" <?php echo $p['status'] === 'Trial' ? 'selected' : ''; ?>>Trial</option>
                                            <option value="Adopted" <?php echo $p['status'] === 'Adopted' ? 'selected' : ''; ?>>Adopted</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <a href="admin_pet_form.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-secondary btn-sm" title="Edit Pet">
                                            ✏️ Edit
                                        </a>
                                        <form method="POST" action="admin_pets.php" style="display:inline;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="pet_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Are you sure you want to delete <?php echo htmlspecialchars($p['name']); ?>? This will also remove associated requests." title="Delete Pet">
                                                🗑
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🐾</div>
                <h3>No Pets Found</h3>
                <p>No pet records match your current filter settings.</p>
                <a href="admin_pet_form.php" class="btn btn-primary">+ Add New Pet Profile</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
