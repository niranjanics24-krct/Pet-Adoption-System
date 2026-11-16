<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$petId = (int)($_GET['id'] ?? 0);

if ($petId <= 0) {
    flash_set('error', 'Invalid pet specified.');
    header('Location: pets.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
$stmt->bind_param("i", $petId);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pet) {
    flash_set('error', 'Pet profile could not be found.');
    header('Location: pets.php');
    exit;
}

$pageTitle = htmlspecialchars($pet['name']) . ' | Pet Profile | Pet Haven';
$activePage = 'pets';

$img = !empty($pet['image']) ? $pet['image'] : 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=800&q=80';
$statusClass = 'status-' . strtolower($pet['status'] ?? 'available');

// Check if logged-in user already applied for this pet
$userHasActiveRequest = false;
if (is_logged_in()) {
    $uid = current_user_id();
    $qcheck = $conn->prepare("SELECT id, status FROM adoption_requests WHERE user_id = ? AND pet_id = ? AND status IN ('Pending', 'Approved')");
    $qcheck->bind_param("ii", $uid, $petId);
    $qcheck->execute();
    $existing = $qcheck->get_result()->fetch_assoc();
    if ($existing) {
        $userHasActiveRequest = true;
    }
    $qcheck->close();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 35px; margin-bottom: 50px;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 20px; font-size: 14px; color: var(--text-muted);">
        <a href="index.php">Home</a> &rsaquo; 
        <a href="pets.php">Available Pets</a> &rsaquo; 
        <span style="color:var(--text-dark); font-weight:600;"><?php echo htmlspecialchars($pet['name']); ?></span>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1.1fr; gap: 40px; align-items: start;">
        <!-- Pet Image Box -->
        <div style="background:var(--bg-surface); padding:15px; border-radius:var(--radius-lg); border:1px solid var(--border-color); box-shadow:var(--shadow-md);">
            <div style="position:relative; border-radius:var(--radius-md); overflow:hidden; max-height:450px;">
                <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=800&q=80'">
                <span class="pet-status-badge <?php echo $statusClass; ?>" style="top:18px; right:18px; font-size:13px; padding:6px 14px;">
                    <?php echo htmlspecialchars($pet['status']); ?>
                </span>
            </div>

            <!-- Pet Snapshot Details -->
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-top:18px;">
                <div style="background:var(--bg-warm-tint); padding:12px 16px; border-radius:var(--radius-sm);">
                    <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Species</div>
                    <div style="font-size:16px; font-weight:700; color:var(--primary-forest);"><?php echo htmlspecialchars($pet['type'] ?? 'Pet'); ?></div>
                </div>
                <div style="background:var(--bg-warm-tint); padding:12px 16px; border-radius:var(--radius-sm);">
                    <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Breed</div>
                    <div style="font-size:16px; font-weight:700; color:var(--primary-forest);"><?php echo htmlspecialchars($pet['breed'] ?? 'Mixed'); ?></div>
                </div>
                <div style="background:var(--bg-warm-tint); padding:12px 16px; border-radius:var(--radius-sm);">
                    <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Age</div>
                    <div style="font-size:16px; font-weight:700; color:var(--primary-forest);"><?php echo htmlspecialchars($pet['age'] ?? 'Not specified'); ?></div>
                </div>
                <div style="background:var(--bg-warm-tint); padding:12px 16px; border-radius:var(--radius-sm);">
                    <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Gender</div>
                    <div style="font-size:16px; font-weight:700; color:var(--primary-forest);"><?php echo htmlspecialchars($pet['gender'] ?? 'Not specified'); ?></div>
                </div>
            </div>
        </div>

        <!-- Pet Profile & Action Card -->
        <div>
            <div class="content-card" style="padding:32px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <h1 style="font-size:36px; color:var(--primary-forest); margin:0;">
                        <?php echo htmlspecialchars($pet['name']); ?>
                    </h1>
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px;">
                    <span class="meta-chip" style="font-size:13px; padding:6px 12px;">🐾 ID #<?php echo $pet['id']; ?></span>
                    <span class="meta-chip" style="font-size:13px; padding:6px 12px;">🗓 Added <?php echo date('F Y', strtotime($pet['created_at'])); ?></span>
                    <span class="meta-chip" style="font-size:13px; padding:6px 12px; background:var(--sage-light); color:var(--primary-forest);">✓ Vet Health Checked</span>
                </div>

                <h3 style="font-size:18px; color:var(--primary-forest); margin-bottom:10px;">About <?php echo htmlspecialchars($pet['name']); ?></h3>
                <p style="font-size:16px; color:var(--text-muted); line-height:1.8; margin-bottom:28px;">
                    <?php echo nl2br(htmlspecialchars($pet['description'] ?: 'This lovely companion is ready for their forever family! They have been lovingly looked after and are looking forward to a safe, nurturing home.')); ?>
                </p>

                <div style="background:var(--bg-warm-tint); padding:20px; border-radius:var(--radius-md); margin-bottom:28px; border-left:4px solid var(--primary-forest);">
                    <h4 style="font-size:15px; color:var(--primary-forest); margin-bottom:8px;">💡 Adoption Screening Note</h4>
                    <p style="font-size:13px; color:var(--text-muted); line-height:1.6; margin:0;">
                        To adopt <?php echo htmlspecialchars($pet['name']); ?>, you will be asked to complete our 18-point Compatibility & Eligibility Assessment. 
                        This covers your living environment, daily schedule, healthcare budget, and emergency plans.
                    </p>
                </div>

                <!-- Call to Action -->
                <div>
                    <?php if ($pet['status'] === 'Available'): ?>
                        <?php if ($userHasActiveRequest): ?>
                            <div class="alert alert-info">
                                ℹ️ You already have an active adoption request for <?php echo htmlspecialchars($pet['name']); ?>. 
                                <a href="user_dashboard.php" style="font-weight:700;">Check your dashboard</a> to track its status.
                            </div>
                        <?php elseif (is_logged_in()): ?>
                            <a href="eligibility_test.php?pet_id=<?php echo $pet['id']; ?>" class="btn btn-primary btn-lg" style="width:100%;">
                                Start Compatibility Test & Adopt <?php echo htmlspecialchars($pet['name']); ?> →
                            </a>
                        <?php else: ?>
                            <a href="login.php?redirect=<?php echo urlencode('eligibility_test.php?pet_id=' . $pet['id']); ?>" class="btn btn-primary btn-lg" style="width:100%;">
                                Log In to Apply for <?php echo htmlspecialchars($pet['name']); ?> →
                            </a>
                            <div style="text-align:center; margin-top:10px; font-size:13px; color:var(--text-muted);">
                                New here? <a href="register.php">Create a free account in 1 minute</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            ⚠️ <strong>Notice:</strong> <?php echo htmlspecialchars($pet['name']); ?> is currently marked as 
                            <strong><?php echo htmlspecialchars($pet['status']); ?></strong> and is not open for new applications.
                        </div>
                        <a href="pets.php" class="btn btn-outline" style="width:100%;">Browse Other Available Pets</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
