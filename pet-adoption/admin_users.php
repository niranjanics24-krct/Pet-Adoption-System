<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_admin();

$pageTitle = 'Manage User Accounts | Pet Haven';
$activePage = 'admin_users';

$currentAdminId = current_user_id();

// Handle Role Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Security session expired.');
        header('Location: admin_users.php');
        exit;
    }

    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $newRole = trim($_POST['new_role'] ?? '');

    if ($targetUserId === $currentAdminId && $newRole === 'user') {
        flash_set('error', 'For safety, you cannot demote your own active administrator account.');
    } elseif ($targetUserId > 0 && in_array($newRole, ['user', 'admin'])) {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $newRole, $targetUserId);
        if ($stmt->execute()) {
            flash_set('success', "User #$targetUserId role changed to '$newRole'.");
        } else {
            flash_set('error', "Failed to update role: " . $conn->error);
        }
        $stmt->close();
    }
    header('Location: admin_users.php');
    exit;
}

// Fetch all users and count their adoption requests
$usersSql = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.phone,
        u.address,
        u.role,
        u.created_at,
        COUNT(ar.id) AS total_requests
    FROM users u
    LEFT JOIN adoption_requests ar ON ar.user_id = u.id
    GROUP BY u.id
    ORDER BY u.id ASC
";
$usersResult = $conn->query($usersSql);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 35px; margin-bottom: 50px;">
    <!-- Top Bar -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:25px;">
        <div>
            <h1 style="font-size:28px; color:var(--primary-forest); margin:0 0 6px;">Registered User Accounts</h1>
            <p style="color:var(--text-muted); margin:0;">View adopter accounts, contact details, application volume, and administrator permissions.</p>
        </div>
        <div>
            <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="content-card">
        <?php if ($usersResult && $usersResult->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name & Email</th>
                            <th>Phone & Address</th>
                            <th>System Role</th>
                            <th>Applications</th>
                            <th>Joined Date</th>
                            <th>Role Management</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $usersResult->fetch_assoc()): ?>
                            <?php 
                                $isSelf = ((int)$u['id'] === $currentAdminId);
                                $roleBadge = $u['role'] === 'admin' ? 'badge-primary' : 'badge-secondary';
                            ?>
                            <tr>
                                <td>
                                    <span style="font-family:monospace; font-weight:700;">#<?php echo $u['id']; ?></span>
                                </td>
                                <td>
                                    <div style="font-weight:700; color:var(--primary-forest);">
                                        <?php echo htmlspecialchars($u['name']); ?>
                                        <?php if ($isSelf): ?>
                                            <span style="font-size:11px; background:#E2E8F0; padding:2px 6px; border-radius:4px; margin-left:4px;">You</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:13px; color:var(--text-muted);">
                                        <?php echo htmlspecialchars($u['email']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:13px;"><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></div>
                                    <div style="font-size:12px; color:var(--text-light); max-width:200px;"><?php echo htmlspecialchars($u['address'] ?? '—'); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-info' : 'badge-primary'; ?>" style="font-size:13px; padding:4px 10px;">
                                        <?php echo ucfirst(htmlspecialchars($u['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo (int)$u['total_requests']; ?></strong> request(s)
                                </td>
                                <td>
                                    <div style="font-size:13px;"><?php echo date('M j, Y', strtotime($u['created_at'])); ?></div>
                                </td>
                                <td>
                                    <?php if ($isSelf): ?>
                                        <span style="font-size:12px; color:var(--text-light);">Current Account</span>
                                    <?php else: ?>
                                        <form method="POST" action="admin_users.php" style="display:inline;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <?php if ($u['role'] === 'admin'): ?>
                                                <input type="hidden" name="new_role" value="user">
                                                <button type="submit" class="btn btn-secondary btn-sm" data-confirm="Demote <?php echo htmlspecialchars($u['name']); ?> to standard user?">
                                                    Demote to User
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="new_role" value="admin">
                                                <button type="submit" class="btn btn-outline btn-sm" data-confirm="Promote <?php echo htmlspecialchars($u['name']); ?> to Administrator?">
                                                    Promote to Admin
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No user accounts found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
