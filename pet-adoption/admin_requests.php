<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_admin();

$pageTitle = 'Adoption Applications Review | Pet Haven';
$activePage = 'admin_requests';

// Handle Decision Actions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Security token expired.');
        header('Location: admin_requests.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $requestId = (int)($_POST['request_id'] ?? 0);
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    if ($requestId > 0) {
        // Fetch request and pet status
        $q = $conn->prepare("SELECT ar.*, p.status AS pet_status, p.name AS pet_name FROM adoption_requests ar JOIN pets p ON ar.pet_id = p.id WHERE ar.id = ?");
        $q->bind_param("i", $requestId);
        $q->execute();
        $req = $q->get_result()->fetch_assoc();
        $q->close();

        if ($req) {
            if ($action === 'approve') {
                // Check if pet is already adopted
                if ($req['pet_status'] === 'Adopted') {
                    flash_set('error', "Cannot approve request: {$req['pet_name']} has already been adopted.");
                } else {
                    $note = $adminNotes ?: 'Approved by shelter coordinator.';
                    $up = $conn->prepare("UPDATE adoption_requests SET status = 'Approved', admin_notes = ? WHERE id = ?");
                    $up->bind_param("si", $note, $requestId);
                    if ($up->execute()) {
                        // Optionally update pet status to 'Pending' to prevent other adoptions
                        $upPet = $conn->prepare("UPDATE pets SET status = 'Pending' WHERE id = ? AND status = 'Available'");
                        $upPet->bind_param("i", $req['pet_id']);
                        $upPet->execute();
                        $upPet->close();

                        flash_set('success', "Adoption request #$requestId for {$req['pet_name']} has been APPROVED! You may now schedule a trial adoption.");
                    }
                    $up->close();
                }
            } elseif ($action === 'reject') {
                $note = $adminNotes ?: 'Application was reviewed and not selected at this time.';
                $up = $conn->prepare("UPDATE adoption_requests SET status = 'Rejected', admin_notes = ? WHERE id = ?");
                $up->bind_param("si", $note, $requestId);
                if ($up->execute()) {
                    flash_set('info', "Adoption request #$requestId has been marked as Rejected.");
                }
                $up->close();
            }
        }
    }

    header('Location: admin_requests.php');
    exit;
}

// Filters & Search
$status = trim($_GET['status'] ?? '');
$petId = (int)($_GET['pet_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];
$types = '';

if ($status !== '' && $status !== 'All') {
    $where[] = "ar.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($petId > 0) {
    $where[] = "ar.pet_id = ?";
    $params[] = $petId;
    $types .= 'i';
}

if ($search !== '') {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR p.name LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

$sql = "
    SELECT 
        ar.id,
        ar.eligibility_score,
        ar.status,
        ar.admin_notes,
        ar.request_date,
        u.id AS user_id,
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        p.id AS pet_id,
        p.name AS pet_name,
        p.type AS pet_type,
        p.image AS pet_image,
        t.id AS trial_id,
        t.status AS trial_status
    FROM adoption_requests ar
    JOIN users u ON ar.user_id = u.id
    JOIN pets p ON ar.pet_id = p.id
    LEFT JOIN trial_adoptions t ON t.request_id = ar.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY ar.request_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$requests = $stmt->get_result();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 35px; margin-bottom: 50px;">
    <!-- Top Bar -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:25px;">
        <div>
            <h1 style="font-size:28px; color:var(--primary-forest); margin:0 0 6px;">Adoption Applications & Assessments</h1>
            <p style="color:var(--text-muted); margin:0;">Review applicant compatibility scores, evaluate 18-point questionnaires, and record decisions.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="admin_trials.php" class="btn btn-outline">🏡 Trial Adoptions</a>
            <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" action="admin_requests.php" class="filter-grid">
            <div class="form-group" style="margin-bottom:0;">
                <label for="search">Search Applicant or Pet</label>
                <input type="text" id="search" name="search" class="form-control" placeholder="Name, email, or pet..." value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label for="status">Decision Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="All">All Statuses</option>
                    <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="Approved" <?php echo $status === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="Rejected" <?php echo $status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Filter</button>
                <a href="admin_requests.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Requests Table -->
    <div class="content-card">
        <?php if ($requests && $requests->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>App #</th>
                            <th>Pet</th>
                            <th>Applicant</th>
                            <th>Compatibility Score</th>
                            <th>Decision Status</th>
                            <th>Date Submitted</th>
                            <th>Actions & Decisions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $requests->fetch_assoc()): ?>
                            <?php 
                                $score = (float)$r['eligibility_score'];
                                $badgeClass = $score >= 85 ? 'badge-success' : ($score >= 70 ? 'badge-primary' : ($score >= 50 ? 'badge-warning' : 'badge-danger'));
                                $statBadge = $r['status'] === 'Approved' ? 'badge-success' : ($r['status'] === 'Pending' ? 'badge-warning' : 'badge-danger');
                                $img = $r['pet_image'] ?: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=150&q=80';
                            ?>
                            <tr>
                                <td>
                                    <span style="font-family:monospace; font-weight:700;">#<?php echo $r['id']; ?></span>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="" style="width:42px; height:42px; border-radius:6px; object-fit:cover;">
                                        <div>
                                            <a href="pet_details.php?id=<?php echo (int)$r['pet_id']; ?>" target="_blank" style="font-weight:700; color:var(--primary-forest);">
                                                <?php echo htmlspecialchars($r['pet_name']); ?>
                                            </a>
                                            <div style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($r['pet_type']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($r['user_name']); ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($r['user_email']); ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($r['user_phone'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badgeClass; ?>" style="font-size:13px; padding:6px 10px;">
                                        <?php echo number_format($score, 0); ?> / 100
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $statBadge; ?>" style="font-size:13px; padding:5px 10px;">
                                        <?php echo htmlspecialchars($r['status']); ?>
                                    </span>
                                    <?php if (!empty($r['trial_status'])): ?>
                                        <div style="font-size:11px; color:#3182CE; font-weight:700; margin-top:4px;">
                                            Trial: <?php echo htmlspecialchars($r['trial_status']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-size:13px;"><?php echo date('M j, Y', strtotime($r['request_date'])); ?></div>
                                    <div style="font-size:11px; color:var(--text-muted);"><?php echo date('g:i A', strtotime($r['request_date'])); ?></div>
                                </td>
                                <td>
                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                        <a href="view_application.php?id=<?php echo (int)$r['id']; ?>" class="btn btn-outline btn-sm">
                                            🔍 Review Full Test
                                        </a>

                                        <?php if ($r['status'] === 'Pending'): ?>
                                            <div style="display:flex; gap:4px;">
                                                <form method="POST" action="admin_requests.php" style="display:inline; flex:1;">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" style="width:100%;" data-confirm="Approve this adoption application?">
                                                        ✓ Approve
                                                    </button>
                                                </form>

                                                <form method="POST" action="admin_requests.php" style="display:inline; flex:1;">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" style="width:100%;" data-confirm="Reject this adoption application?">
                                                        ✗ Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php elseif ($r['status'] === 'Approved' && empty($r['trial_id'])): ?>
                                            <a href="admin_trials.php?prefill_request=<?php echo (int)$r['id']; ?>" class="btn btn-accent btn-sm">
                                                🏡 Schedule Trial
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>No Applications Found</h3>
                <p>No adoption requests match your selected filters.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
