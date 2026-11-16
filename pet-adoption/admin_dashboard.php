<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_admin();

$pageTitle = 'Admin Management Dashboard | Pet Haven';
$activePage = 'admin_dashboard';

// Fetch Live Database Metrics
$stats = [
    'users' => 0,
    'pets' => 0,
    'available_pets' => 0,
    'pending_requests' => 0,
    'approved_requests' => 0,
    'rejected_requests' => 0,
    'ongoing_trials' => 0,
    'successful_adoptions' => 0,
];

$rUsers = $conn->query("SELECT COUNT(*) FROM users");
if ($rUsers) $stats['users'] = (int)$rUsers->fetch_row()[0];

$rPets = $conn->query("SELECT COUNT(*) FROM pets");
if ($rPets) $stats['pets'] = (int)$rPets->fetch_row()[0];

$rAvail = $conn->query("SELECT COUNT(*) FROM pets WHERE status = 'Available'");
if ($rAvail) $stats['available_pets'] = (int)$rAvail->fetch_row()[0];

$rPending = $conn->query("SELECT COUNT(*) FROM adoption_requests WHERE status = 'Pending'");
if ($rPending) $stats['pending_requests'] = (int)$rPending->fetch_row()[0];

$rApproved = $conn->query("SELECT COUNT(*) FROM adoption_requests WHERE status = 'Approved'");
if ($rApproved) $stats['approved_requests'] = (int)$rApproved->fetch_row()[0];

$rRejected = $conn->query("SELECT COUNT(*) FROM adoption_requests WHERE status = 'Rejected'");
if ($rRejected) $stats['rejected_requests'] = (int)$rRejected->fetch_row()[0];

$rTrials = $conn->query("SELECT COUNT(*) FROM trial_adoptions WHERE status = 'Ongoing'");
if ($rTrials) $stats['ongoing_trials'] = (int)$rTrials->fetch_row()[0];

$rSuccess = $conn->query("SELECT COUNT(*) FROM trial_adoptions WHERE status = 'Successful'");
if ($rSuccess) $stats['successful_adoptions'] = (int)$rSuccess->fetch_row()[0];

// Fetch recent 6 adoption requests
$recentSql = "
    SELECT 
        ar.id,
        ar.eligibility_score,
        ar.status,
        ar.request_date,
        u.name AS user_name,
        u.email AS user_email,
        p.name AS pet_name,
        p.type AS pet_type,
        p.image AS pet_image
    FROM adoption_requests ar
    JOIN users u ON ar.user_id = u.id
    JOIN pets p ON ar.pet_id = p.id
    ORDER BY ar.request_date DESC
    LIMIT 6
";
$recentRequests = $conn->query($recentSql);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 35px; margin-bottom: 50px;">
    <!-- Admin Top Banner -->
    <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:28px; box-shadow:var(--shadow-sm); margin-bottom:30px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px;">
        <div>
            <div style="font-size:12px; font-weight:700; color:var(--primary-forest); text-transform:uppercase; letter-spacing:1.5px;">Shelter Coordinator Portal</div>
            <h1 style="font-size:28px; color:var(--primary-forest); margin:5px 0;">Admin Control Center</h1>
            <p style="color:var(--text-muted); margin:0; font-size:15px;">Monitor pet inventory, evaluate incoming applicant compatibility tests, and manage trial adoptions.</p>
        </div>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <a href="admin_pet_form.php" class="btn btn-primary">+ Add New Pet</a>
            <a href="admin_requests.php" class="btn btn-secondary">Review Requests (<?php echo $stats['pending_requests']; ?>)</a>
            <a href="admin_trials.php" class="btn btn-outline">Trial Adoptions</a>
        </div>
    </div>

    <!-- Live Statistics Grid -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--sage-light); color:var(--primary-forest);">👥</div>
            <div class="stat-content">
                <div class="stat-val"><?php echo $stats['users']; ?></div>
                <div class="stat-label">Registered Users</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#FAF1E8; color:var(--warm-terracotta);">🐾</div>
            <div class="stat-content">
                <div class="stat-val"><?php echo $stats['pets']; ?></div>
                <div class="stat-label">Total Pets Listed</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:var(--success-bg); color:var(--success);">✨</div>
            <div class="stat-content">
                <div class="stat-val"><?php echo $stats['available_pets']; ?></div>
                <div class="stat-label">Currently Available</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:var(--warning-bg); color:var(--warning);">⏳</div>
            <div class="stat-content">
                <div class="stat-val"><?php echo $stats['pending_requests']; ?></div>
                <div class="stat-label">Pending Reviews</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#EBF8FF; color:#3182CE;">🏡</div>
            <div class="stat-content">
                <div class="stat-val"><?php echo $stats['ongoing_trials']; ?></div>
                <div class="stat-label">Ongoing Trials</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:var(--success-bg); color:var(--success);">❤️</div>
            <div class="stat-content">
                <div class="stat-val"><?php echo $stats['successful_adoptions']; ?></div>
                <div class="stat-label">Adopted Forever</div>
            </div>
        </div>
    </div>

    <!-- Admin Quick Nav Tabs -->
    <div style="display:flex; gap:12px; margin-bottom:25px; flex-wrap:wrap;">
        <a href="admin_pets.php" class="btn btn-secondary btn-sm">🐾 Manage All Pets</a>
        <a href="admin_requests.php" class="btn btn-secondary btn-sm">📋 All Adoption Applications</a>
        <a href="admin_trials.php" class="btn btn-secondary btn-sm">🏡 Trial Adoptions Tracker</a>
        <a href="admin_users.php" class="btn btn-secondary btn-sm">👤 Manage User Accounts</a>
    </div>

    <!-- Recent Adoption Requests Table -->
    <div class="content-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="font-size:19px; color:var(--primary-forest); margin:0;">
                Recent Adoption Requests & Assessments
            </h2>
            <a href="admin_requests.php" style="font-size:14px; font-weight:600;">View All Requests →</a>
        </div>

        <?php if ($recentRequests && $recentRequests->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Applicant</th>
                            <th>Date</th>
                            <th>Eligibility Score</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $recentRequests->fetch_assoc()): ?>
                            <?php 
                                $score = (float)$r['eligibility_score'];
                                $badgeClass = $score >= 85 ? 'badge-success' : ($score >= 70 ? 'badge-primary' : ($score >= 50 ? 'badge-warning' : 'badge-danger'));
                                $statBadge = $r['status'] === 'Approved' ? 'badge-success' : ($r['status'] === 'Pending' ? 'badge-warning' : 'badge-danger');
                                $img = $r['pet_image'] ?: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=150&q=80';
                            ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="" style="width:40px; height:40px; border-radius:6px; object-fit:cover;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($r['pet_name']); ?></strong>
                                            <div style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($r['pet_type']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($r['user_name']); ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($r['user_email']); ?></div>
                                </td>
                                <td>
                                    <div style="font-size:13px;"><?php echo date('M j, Y', strtotime($r['request_date'])); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo number_format($score, 0); ?> / 100
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $statBadge; ?>">
                                        <?php echo htmlspecialchars($r['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_application.php?id=<?php echo (int)$r['id']; ?>" class="btn btn-outline btn-sm">
                                        Review Test →
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No adoption requests received yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
