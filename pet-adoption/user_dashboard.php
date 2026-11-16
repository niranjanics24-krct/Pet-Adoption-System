<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_login('user_dashboard.php');

$userId = current_user_id();
$pageTitle = 'Adoption Dashboard | Pet Haven';
$activePage = 'dashboard';

// Fetch user's adoption requests with pet info and trial info
$sql = "
    SELECT 
        ar.id AS request_id,
        ar.eligibility_score,
        ar.status AS request_status,
        ar.admin_notes,
        ar.request_date,
        p.id AS pet_id,
        p.name AS pet_name,
        p.type AS pet_type,
        p.breed AS pet_breed,
        p.gender AS pet_gender,
        p.age AS pet_age,
        p.image AS pet_image,
        p.status AS pet_status,
        t.id AS trial_id,
        t.start_date AS trial_start,
        t.end_date AS trial_end,
        t.status AS trial_status,
        t.notes AS trial_notes
    FROM adoption_requests ar
    JOIN pets p ON ar.pet_id = p.id
    LEFT JOIN trial_adoptions t ON t.request_id = ar.id
    WHERE ar.user_id = ?
    ORDER BY ar.request_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$requestsResult = $stmt->get_result();

$requests = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'trials' => 0,
    'adopted' => 0,
];

while ($row = $requestsResult->fetch_assoc()) {
    $requests[] = $row;
    $stats['total']++;
    if ($row['request_status'] === 'Pending') $stats['pending']++;
    if ($row['request_status'] === 'Approved') $stats['approved']++;
    if (!empty($row['trial_status']) && $row['trial_status'] === 'Ongoing') $stats['trials']++;
    if (!empty($row['trial_status']) && $row['trial_status'] === 'Successful') $stats['adopted']++;
}
$stmt->close();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 35px; margin-bottom: 50px;">
    <!-- Welcome Header -->
    <div style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:30px; box-shadow:var(--shadow-sm); margin-bottom:30px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px;">
        <div>
            <div style="font-size:13px; font-weight:700; color:var(--warm-terracotta); text-transform:uppercase; letter-spacing:1px;">Adopter Dashboard</div>
            <h1 style="font-size:30px; color:var(--primary-forest); margin:5px 0;">Welcome back, <?php echo htmlspecialchars(current_user_name()); ?>! 👋</h1>
            <p style="color:var(--text-muted); margin:0; font-size:15px;">Track your ongoing pet adoption applications, compatibility scores, and trial progress.</p>
        </div>
        <div style="display:flex; gap:12px;">
            <a href="pets.php" class="btn btn-primary">Find More Pets</a>
            <a href="profile.php" class="btn btn-secondary">Edit Profile</a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon">📄</div>
            <div class="stat-content">
                <div class="stat-val"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:var(--warning-bg); color:var(--warning);">⏳</div>
            <div class="stat-content">
                <div class="stat-val"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:#EBF8FF; color:#3182CE;">🏡</div>
            <div class="stat-content">
                <div class="stat-val"><?php echo $stats['trials']; ?></div>
                <div class="stat-label">Active Trials</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background:var(--success-bg); color:var(--success);">❤️</div>
            <div class="stat-content">
                <div class="stat-val"><?php echo $stats['adopted']; ?></div>
                <div class="stat-label">Finalized Adoptions</div>
            </div>
        </div>
    </div>

    <!-- Applications List -->
    <div class="content-card">
        <h2 style="font-size:20px; color:var(--primary-forest); margin-bottom:20px;">
            🐾 Your Adoption Applications
        </h2>

        <?php if (!empty($requests)): ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Date Applied</th>
                            <th>Compatibility Score</th>
                            <th>Request Status</th>
                            <th>Trial Adoption</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <?php 
                                $score = (float)$req['eligibility_score'];
                                $scoreBadgeClass = $score >= 85 ? 'badge-success' : ($score >= 70 ? 'badge-primary' : ($score >= 50 ? 'badge-warning' : 'badge-danger'));

                                $reqStatusClass = $req['request_status'] === 'Approved' ? 'badge-success' : ($req['request_status'] === 'Pending' ? 'badge-warning' : 'badge-danger');
                                $petImg = $req['pet_image'] ?: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=150&q=80';
                            ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:12px;">
                                        <img src="<?php echo htmlspecialchars($petImg); ?>" alt="" style="width:48px; height:48px; border-radius:8px; object-fit:cover;">
                                        <div>
                                            <a href="pet_details.php?id=<?php echo (int)$req['pet_id']; ?>" style="font-weight:700; font-size:15px; color:var(--primary-forest);">
                                                <?php echo htmlspecialchars($req['pet_name']); ?>
                                            </a>
                                            <div style="font-size:12px; color:var(--text-muted);">
                                                <?php echo htmlspecialchars($req['pet_type']); ?> • <?php echo htmlspecialchars($req['pet_breed']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:14px; font-weight:600;"><?php echo date('M j, Y', strtotime($req['request_date'])); ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?php echo date('g:i A', strtotime($req['request_date'])); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $scoreBadgeClass; ?>" style="font-size:13px; padding:6px 12px;">
                                        <?php echo number_format($score, 0); ?> / 100
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $reqStatusClass; ?>" style="font-size:13px; padding:6px 12px;">
                                        <?php echo htmlspecialchars($req['request_status']); ?>
                                    </span>
                                    <?php if (!empty($req['admin_notes'])): ?>
                                        <div style="font-size:12px; color:var(--text-muted); margin-top:4px;">
                                            Note: <?php echo htmlspecialchars($req['admin_notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($req['trial_status'])): ?>
                                        <?php 
                                            $tBadge = $req['trial_status'] === 'Successful' ? 'badge-success' : ($req['trial_status'] === 'Ongoing' ? 'badge-info' : 'badge-danger');
                                        ?>
                                        <span class="badge <?php echo $tBadge; ?>">
                                            <?php echo htmlspecialchars($req['trial_status']); ?>
                                        </span>
                                        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                                            <?php echo date('M j', strtotime($req['trial_start'])); ?> to <?php echo date('M j, Y', strtotime($req['trial_end'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--text-light); font-size:13px;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view_application.php?id=<?php echo (int)$req['request_id']; ?>" class="btn btn-outline btn-sm">
                                        View Answers & Score
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🐾</div>
                <h3>No Adoption Applications Yet</h3>
                <p>Browse our catalog of rescue companions and begin your journey by applying for a pet.</p>
                <a href="pets.php" class="btn btn-primary">Find Pets to Adopt →</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
