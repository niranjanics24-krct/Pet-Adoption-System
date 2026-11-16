<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_admin();

$pageTitle = 'Manage Trial Adoptions | Pet Haven';
$activePage = 'admin_trials';

$prefillRequestId = (int)($_GET['prefill_request'] ?? 0);

// Handle Actions: Schedule new trial or Update outcome
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Security token expired. Please try again.');
        header('Location: admin_trials.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Schedule New Trial
    if ($action === 'schedule_trial') {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $startDate = trim($_POST['start_date'] ?? date('Y-m-d'));
        $endDate = trim($_POST['end_date'] ?? date('Y-m-d', strtotime('+14 days')));
        $notes = trim($_POST['notes'] ?? '');

        if ($requestId <= 0 || empty($startDate) || empty($endDate)) {
            flash_set('error', 'Please select a valid approved request and valid trial dates.');
        } else {
            // Check request exists and is Approved
            $q = $conn->prepare("SELECT ar.*, p.id AS pet_id, p.name AS pet_name, p.status AS pet_status FROM adoption_requests ar JOIN pets p ON ar.pet_id = p.id WHERE ar.id = ?");
            $q->bind_param("i", $requestId);
            $q->execute();
            $req = $q->get_result()->fetch_assoc();
            $q->close();

            if (!$req || $req['status'] !== 'Approved') {
                flash_set('error', 'Trial can only be initiated for an Approved adoption request.');
            } else {
                // Begin transaction
                $conn->begin_transaction();
                try {
                    // Check if trial already exists for this request
                    $tcheck = $conn->prepare("SELECT id FROM trial_adoptions WHERE request_id = ?");
                    $tcheck->bind_param("i", $requestId);
                    $tcheck->execute();
                    if ($tcheck->get_result()->num_rows > 0) {
                        throw new Exception("A trial adoption record already exists for this request.");
                    }
                    $tcheck->close();

                    // Insert trial adoption
                    $stmt = $conn->prepare("INSERT INTO trial_adoptions (request_id, start_date, end_date, status, notes) VALUES (?, ?, ?, 'Ongoing', ?)");
                    $stmt->bind_param("isss", $requestId, $startDate, $endDate, $notes);
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to schedule trial: " . $stmt->error);
                    }
                    $stmt->close();

                    // Update pet status to 'Trial'
                    $upPet = $conn->prepare("UPDATE pets SET status = 'Trial' WHERE id = ?");
                    $upPet->bind_param("i", $req['pet_id']);
                    if (!$upPet->execute()) {
                        throw new Exception("Failed to update pet status: " . $upPet->error);
                    }
                    $upPet->close();

                    $conn->commit();
                    flash_set('success', "Trial adoption successfully scheduled for {$req['pet_name']} from $startDate to $endDate! Pet status is now set to 'Trial'.");
                } catch (Exception $e) {
                    $conn->rollback();
                    flash_set('error', $e->getMessage());
                }
            }
        }
        header('Location: admin_trials.php');
        exit;
    }

    // Record Outcome
    if ($action === 'record_outcome') {
        $trialId = (int)($_POST['trial_id'] ?? 0);
        $outcome = trim($_POST['outcome'] ?? '');
        $outcomeNotes = trim($_POST['notes'] ?? '');

        if (!in_array($outcome, ['Successful', 'Unsuccessful'])) {
            flash_set('error', 'Invalid outcome specified.');
        } else {
            // Fetch trial info
            $q = $conn->prepare("
                SELECT t.*, ar.pet_id, p.name AS pet_name 
                FROM trial_adoptions t
                JOIN adoption_requests ar ON t.request_id = ar.id
                JOIN pets p ON ar.pet_id = p.id
                WHERE t.id = ?
            ");
            $q->bind_param("i", $trialId);
            $q->execute();
            $trial = $q->get_result()->fetch_assoc();
            $q->close();

            if ($trial) {
                $conn->begin_transaction();
                try {
                    // Update trial outcome
                    $upTrial = $conn->prepare("UPDATE trial_adoptions SET status = ?, notes = ? WHERE id = ?");
                    $upTrial->bind_param("ssi", $outcome, $outcomeNotes, $trialId);
                    if (!$upTrial->execute()) {
                        throw new Exception("Failed to update trial: " . $upTrial->error);
                    }
                    $upTrial->close();

                    // If Successful -> pet status becomes 'Adopted'
                    // If Unsuccessful -> pet status reverts to 'Available'
                    $newPetStatus = ($outcome === 'Successful') ? 'Adopted' : 'Available';
                    $upPet = $conn->prepare("UPDATE pets SET status = ? WHERE id = ?");
                    $upPet->bind_param("si", $newPetStatus, $trial['pet_id']);
                    if (!$upPet->execute()) {
                        throw new Exception("Failed to update pet status: " . $upPet->error);
                    }
                    $upPet->close();

                    $conn->commit();

                    if ($outcome === 'Successful') {
                        flash_set('success', "🎉 Congratulations! Trial concluded as SUCCESSFUL! {$trial['pet_name']} is now permanently marked as 'Adopted'.");
                    } else {
                        flash_set('info', "Trial concluded as Unsuccessful. {$trial['pet_name']} has been returned to 'Available' status for other adopters.");
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    flash_set('error', $e->getMessage());
                }
            }
        }
        header('Location: admin_trials.php');
        exit;
    }
}

// Fetch Approved Requests eligible for Trial Scheduling (no trial created yet)
$eligibleRequests = $conn->query("
    SELECT ar.id, ar.request_date, u.name AS user_name, p.name AS pet_name, p.type AS pet_type
    FROM adoption_requests ar
    JOIN users u ON ar.user_id = u.id
    JOIN pets p ON ar.pet_id = p.id
    LEFT JOIN trial_adoptions t ON t.request_id = ar.id
    WHERE ar.status = 'Approved' AND t.id IS NULL
    ORDER BY ar.request_date DESC
");

// Fetch All Trial Adoptions with related details
$trialsSql = "
    SELECT 
        t.id AS trial_id,
        t.start_date,
        t.end_date,
        t.status AS trial_status,
        t.notes AS trial_notes,
        t.created_at,
        ar.id AS request_id,
        ar.eligibility_score,
        u.name AS user_name,
        u.email AS user_email,
        u.phone AS user_phone,
        p.id AS pet_id,
        p.name AS pet_name,
        p.type AS pet_type,
        p.image AS pet_image,
        p.status AS pet_status
    FROM trial_adoptions t
    JOIN adoption_requests ar ON t.request_id = ar.id
    JOIN users u ON ar.user_id = u.id
    JOIN pets p ON ar.pet_id = p.id
    ORDER BY (t.status = 'Ongoing') DESC, t.created_at DESC
";
$trialsResult = $conn->query($trialsSql);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 35px; margin-bottom: 50px;">
    <!-- Top Bar -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:25px;">
        <div>
            <h1 style="font-size:28px; color:var(--primary-forest); margin:0 0 6px;">Trial Adoptions Manager</h1>
            <p style="color:var(--text-muted); margin:0;">Coordinate trial placement periods, evaluate bonding progress, and record final adoption outcomes.</p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="admin_requests.php" class="btn btn-secondary">Review Applications</a>
            <a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Schedule Trial Form Card -->
    <div class="content-card" style="border-top:4px solid var(--warm-terracotta);">
        <h2 style="font-size:19px; color:var(--primary-forest); margin-bottom:15px; display:flex; align-items:center; gap:8px;">
            <span>🏡</span>
            <span>Schedule New Trial Adoption</span>
        </h2>

        <?php if ($eligibleRequests && $eligibleRequests->num_rows > 0): ?>
            <form method="POST" action="admin_trials.php" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; align-items:flex-end;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="schedule_trial">

                <div class="form-group" style="margin-bottom:0; grid-column: span 2;">
                    <label for="request_id">Select Approved Application *</label>
                    <select id="request_id" name="request_id" class="form-control" required>
                        <option value="">-- Choose Approved Applicant --</option>
                        <?php while ($er = $eligibleRequests->fetch_assoc()): ?>
                            <option value="<?php echo $er['id']; ?>" <?php echo $prefillRequestId === (int)$er['id'] ? 'selected' : ''; ?>>
                                App #<?php echo $er['id']; ?>: <?php echo htmlspecialchars($er['user_name']); ?> for <?php echo htmlspecialchars($er['pet_name']); ?> (<?php echo htmlspecialchars($er['pet_type']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label for="start_date">Trial Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label for="end_date">Trial End Date (14 Days)</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>" required>
                </div>

                <div class="form-group" style="margin-bottom:0; grid-column: span 2;">
                    <label for="notes">Trial Notes / Support Arrangement</label>
                    <input type="text" id="notes" name="notes" class="form-control" placeholder="e.g. Adopter receives food starter kit; 7-day phone check-in scheduled.">
                </div>

                <div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        Start Trial Period →
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div style="background:var(--bg-cream); padding:16px 20px; border-radius:var(--radius-sm); font-size:14px; color:var(--text-muted);">
                ℹ️ There are currently no approved requests waiting for trial scheduling. 
                <a href="admin_requests.php" style="font-weight:700;">Review pending applications</a> to approve qualified adopters.
            </div>
        <?php endif; ?>
    </div>

    <!-- Trial Adoptions Table -->
    <div class="content-card">
        <h2 style="font-size:20px; color:var(--primary-forest); margin-bottom:18px;">
            📋 All Trial Adoption Records & Outcomes
        </h2>

        <?php if ($trialsResult && $trialsResult->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Pet</th>
                            <th>Adopter</th>
                            <th>Dates / Duration</th>
                            <th>Trial Status</th>
                            <th>Notes</th>
                            <th>Record Final Outcome</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = $trialsResult->fetch_assoc()): ?>
                            <?php 
                                $statusBadge = $t['trial_status'] === 'Successful' ? 'badge-success' : ($t['trial_status'] === 'Ongoing' ? 'badge-info' : 'badge-danger');
                                $img = $t['pet_image'] ?: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=150&q=80';
                            ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="" style="width:44px; height:44px; border-radius:6px; object-fit:cover;">
                                        <div>
                                            <a href="pet_details.php?id=<?php echo (int)$t['pet_id']; ?>" target="_blank" style="font-weight:700; color:var(--primary-forest);">
                                                <?php echo htmlspecialchars($t['pet_name']); ?>
                                            </a>
                                            <div style="font-size:12px; color:var(--text-muted);">
                                                Current: <strong><?php echo htmlspecialchars($t['pet_status']); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($t['user_name']); ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?php echo htmlspecialchars($t['user_phone'] ?? $t['user_email']); ?></div>
                                </td>
                                <td>
                                    <div style="font-size:13px; font-weight:600;">
                                        <?php echo date('M j', strtotime($t['start_date'])); ?> &ndash; <?php echo date('M j, Y', strtotime($t['end_date'])); ?>
                                    </div>
                                    <div style="font-size:11px; color:var(--text-muted);">
                                        Started <?php echo date('M j, Y', strtotime($t['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $statusBadge; ?>" style="font-size:13px; padding:5px 12px;">
                                        <?php echo htmlspecialchars($t['trial_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size:13px; max-width:200px; color:var(--text-muted);">
                                        <?php echo htmlspecialchars($t['trial_notes'] ?: '—'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($t['trial_status'] === 'Ongoing'): ?>
                                        <div style="display:flex; gap:6px;">
                                            <!-- Mark Successful -->
                                            <form method="POST" action="admin_trials.php" style="display:inline;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="record_outcome">
                                                <input type="hidden" name="trial_id" value="<?php echo $t['trial_id']; ?>">
                                                <input type="hidden" name="outcome" value="Successful">
                                                <input type="hidden" name="notes" value="Bonding complete. Formal adoption finalized!">
                                                <button type="submit" class="btn btn-primary btn-sm" data-confirm="Finalize this adoption as SUCCESSFUL? Pet status will become 'Adopted'.">
                                                    ✓ Finalize Adoption
                                                </button>
                                            </form>

                                            <!-- Mark Unsuccessful -->
                                            <form method="POST" action="admin_trials.php" style="display:inline;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="record_outcome">
                                                <input type="hidden" name="trial_id" value="<?php echo $t['trial_id']; ?>">
                                                <input type="hidden" name="outcome" value="Unsuccessful">
                                                <input type="hidden" name="notes" value="Trial did not work out. Companion returned to shelter.">
                                                <button type="submit" class="btn btn-secondary btn-sm" data-confirm="Mark trial as Unsuccessful? Pet status will revert back to 'Available'.">
                                                    ✗ Return Pet
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span style="font-size:13px; color:var(--text-light);">Outcome Finalized</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">🏡</div>
                <h3>No Trial Adoptions Yet</h3>
                <p>When an adoption request is approved, schedule their trial adoption here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
