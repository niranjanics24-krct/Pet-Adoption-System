<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/scoring.php';

require_login('user_dashboard.php');

$requestId = (int)($_GET['id'] ?? 0);
if ($requestId <= 0) {
    flash_set('error', 'Invalid application ID.');
    header('Location: user_dashboard.php');
    exit;
}

// Fetch application with user and pet information
$sql = "
    SELECT 
        ar.id AS request_id,
        ar.user_id,
        ar.pet_id,
        ar.eligibility_score,
        ar.status AS request_status,
        ar.admin_notes,
        ar.request_date,
        u.name AS applicant_name,
        u.email AS applicant_email,
        u.phone AS applicant_phone,
        u.address AS applicant_address,
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
    JOIN users u ON ar.user_id = u.id
    JOIN pets p ON ar.pet_id = p.id
    LEFT JOIN trial_adoptions t ON t.request_id = ar.id
    WHERE ar.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $requestId);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$application) {
    flash_set('error', 'Adoption request not found.');
    header('Location: user_dashboard.php');
    exit;
}

// Security: Check ownership or admin role
$currentUserId = current_user_id();
$isAdmin = is_admin();

if (!$isAdmin && (int)$application['user_id'] !== $currentUserId) {
    flash_set('error', 'You do not have permission to view this application.');
    header('Location: user_dashboard.php');
    exit;
}

// Fetch all questionnaire answers from eligibility_answers table
$ansStmt = $conn->prepare("SELECT question_key, question_text, submitted_answer, created_at FROM eligibility_answers WHERE request_id = ? ORDER BY id ASC");
$ansStmt->bind_param("i", $requestId);
$ansStmt->execute();
$answersResult = $ansStmt->get_result();

$savedAnswers = [];
$rawAnswersMap = [];
while ($a = $answersResult->fetch_assoc()) {
    $savedAnswers[] = $a;
    $rawAnswersMap[$a['question_key']] = $a['submitted_answer'];
}
$ansStmt->close();

// Reconstruct evaluation feedback
$score = (float)$application['eligibility_score'];
$badgeClass = $score >= 85 ? 'badge-success' : ($score >= 70 ? 'badge-primary' : ($score >= 50 ? 'badge-warning' : 'badge-danger'));
$category = $score >= 85 ? 'Highly Recommended' : ($score >= 70 ? 'Eligible - Strong Match' : ($score >= 50 ? 'Conditional - Needs Discussion' : 'Needs Further Preparation'));

$pageTitle = 'Application #' . $requestId . ' Details | Pet Haven';
$activePage = $isAdmin ? 'admin_requests' : 'dashboard';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 35px; margin-bottom: 50px;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 20px; font-size: 14px; color: var(--text-muted);">
        <?php if ($isAdmin): ?>
            <a href="admin_dashboard.php">Admin Panel</a> &rsaquo; 
            <a href="admin_requests.php">Adoption Requests</a> &rsaquo;
        <?php else: ?>
            <a href="user_dashboard.php">My Applications</a> &rsaquo;
        <?php endif; ?>
        <span style="color:var(--text-dark); font-weight:600;">Application #<?php echo $requestId; ?></span>
    </div>

    <!-- Overview Card -->
    <div class="content-card" style="padding:28px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:20px; margin-bottom:20px;">
            <div style="display:flex; gap:18px; align-items:center;">
                <img src="<?php echo htmlspecialchars($application['pet_image'] ?: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=200&q=80'); ?>" alt="" style="width:75px; height:75px; border-radius:12px; object-fit:cover;">
                <div>
                    <div style="font-size:12px; font-weight:700; color:var(--warm-terracotta); text-transform:uppercase;">Application #<?php echo $requestId; ?></div>
                    <h1 style="font-size:26px; color:var(--primary-forest); margin:4px 0;">
                        Applying for: <?php echo htmlspecialchars($application['pet_name']); ?>
                    </h1>
                    <div style="font-size:13px; color:var(--text-muted);">
                        Submitted on <?php echo date('F j, Y \a\t g:i A', strtotime($application['request_date'])); ?>
                    </div>
                </div>
            </div>

            <!-- Score Box -->
            <div style="background:var(--bg-cream); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:16px 24px; text-align:center;">
                <div style="font-size:12px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Compatibility Score</div>
                <div style="font-size:32px; font-weight:800; color:var(--primary-forest); line-height:1.2;">
                    <?php echo number_format($score, 0); ?> <span style="font-size:16px; font-weight:600; color:var(--text-muted);">/ 100</span>
                </div>
                <span class="badge <?php echo $badgeClass; ?>" style="margin-top:4px;">
                    <?php echo htmlspecialchars($category); ?>
                </span>
            </div>
        </div>

        <!-- Details Grid -->
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:18px; padding-top:18px; border-top:1px solid var(--border-color);">
            <div>
                <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Applicant Name</div>
                <div style="font-size:15px; font-weight:700;"><?php echo htmlspecialchars($application['applicant_name']); ?></div>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Contact Phone / Email</div>
                <div style="font-size:14px;"><?php echo htmlspecialchars($application['applicant_phone'] ?? 'N/A'); ?></div>
                <div style="font-size:13px; color:var(--text-muted);"><?php echo htmlspecialchars($application['applicant_email']); ?></div>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Application Status</div>
                <?php 
                    $statBadge = $application['request_status'] === 'Approved' ? 'badge-success' : ($application['request_status'] === 'Pending' ? 'badge-warning' : 'badge-danger');
                ?>
                <span class="badge <?php echo $statBadge; ?>" style="font-size:13px; padding:5px 12px; margin-top:3px;">
                    <?php echo htmlspecialchars($application['request_status']); ?>
                </span>
            </div>
            <div>
                <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Trial Status</div>
                <div style="font-size:14px; font-weight:600; margin-top:3px;">
                    <?php echo !empty($application['trial_status']) ? htmlspecialchars($application['trial_status']) : 'Not started'; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($application['admin_notes'])): ?>
            <div style="background:var(--warning-bg); border:1px solid #FEEBC8; padding:14px 18px; border-radius:var(--radius-sm); margin-top:20px;">
                <strong style="color:var(--warning);">Shelter Feedback / Coordinator Note:</strong>
                <p style="margin:4px 0 0; font-size:14px; color:#744210;"><?php echo htmlspecialchars($application['admin_notes']); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <!-- Admin Quick Actions Bar -->
            <div style="margin-top:22px; padding-top:18px; border-top:1px dashed var(--border-color); display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
                <span style="font-size:13px; font-weight:700; color:var(--primary-forest);">Coordinator Decisions:</span>
                
                <?php if ($application['request_status'] === 'Pending'): ?>
                    <form method="POST" action="admin_requests.php" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Approve this adoption request?');">
                            ✓ Approve Request
                        </button>
                    </form>

                    <form method="POST" action="admin_requests.php" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="request_id" value="<?php echo $requestId; ?>">
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this adoption request?');">
                            ✗ Reject Request
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($application['request_status'] === 'Approved' && empty($application['trial_id'])): ?>
                    <a href="admin_trials.php?prefill_request=<?php echo $requestId; ?>" class="btn btn-accent btn-sm">
                        🏡 Schedule Trial Adoption
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Questionnaire Responses Table -->
    <div class="content-card">
        <h2 style="font-size:20px; color:var(--primary-forest); margin-bottom:18px; display:flex; align-items:center; gap:8px;">
            <span>📝</span>
            <span>Recorded Answers (<?php echo count($savedAnswers); ?> Questions Answered)</span>
        </h2>

        <?php if (!empty($savedAnswers)): ?>
            <div style="display:flex; flex-direction:column; gap:14px;">
                <?php $counter = 1; ?>
                <?php foreach ($savedAnswers as $ans): ?>
                    <div style="background:var(--bg-cream); border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:16px 20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                            <span class="quiz-q-num" style="margin-bottom:0;">Item #<?php echo $counter++; ?></span>
                            <span style="font-size:12px; color:var(--text-light); font-family:monospace;">key: <?php echo htmlspecialchars($ans['question_key']); ?></span>
                        </div>
                        <div style="font-weight:600; font-size:15px; color:var(--text-dark); margin-bottom:8px;">
                            <?php echo htmlspecialchars($ans['question_text']); ?>
                        </div>
                        <div style="background:#FFFFFF; border:1px solid var(--border-color); border-left:4px solid var(--primary-forest); padding:10px 14px; border-radius:6px; font-size:14px; color:var(--text-dark);">
                            <?php echo nl2br(htmlspecialchars($ans['submitted_answer'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No answers recorded for this request record.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
