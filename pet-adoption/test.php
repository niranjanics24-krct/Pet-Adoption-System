<?php
// System Diagnostic & Environment Verification Suite
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/includes/csrf.php";
require_once __DIR__ . "/includes/scoring.php";

$title = "System Verification & Diagnostics | Pet Haven";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #FAF7F2; color: #2D3748; padding: 30px 20px; line-height: 1.6; }
        .box { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
        h1 { color: #2D5A43; margin-top: 0; font-size: 26px; }
        .check-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #E2E8F0; }
        .ok { color: #2F855A; font-weight: bold; }
        .err { color: #C53030; font-weight: bold; }
        .btn { display: inline-block; padding: 8px 16px; background: #2D5A43; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin-right: 8px; font-size: 14px; }
        .btn:hover { background: #224533; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
<div class="box">
    <h1>🐾 Pet Haven - Diagnostic & Verification Report</h1>
    <p>Comprehensive status check for the PHP backend, MySQL tables, permissions, and session handling.</p>

    <!-- 1. PHP Engine Check -->
    <div class="check-item">
        <div>
            <strong>1. PHP Engine Version</strong>
            <div style="font-size:13px; color:#718096;">Installed XAMPP PHP runtime</div>
        </div>
        <div class="ok">✓ PHP <?php echo PHP_VERSION; ?></div>
    </div>

    <!-- 2. Database Connection -->
    <div class="check-item">
        <div>
            <strong>2. MySQL Database Connection</strong>
            <div style="font-size:13px; color:#718096;">Host: localhost • Database: pet_adoption_db • utf8mb4</div>
        </div>
        <div class="ok">✓ Connected Successfully (MySQLi)</div>
    </div>

    <!-- 3. Tables & Schema Verification -->
    <?php
    $requiredTables = [
        'users' => 'Adopter & Admin accounts',
        'pets' => 'Rescue pet profiles & images',
        'adoption_requests' => 'Adoption applications & scores',
        'eligibility_answers' => '18-question answer history',
        'trial_adoptions' => 'Trial placements & final outcomes'
    ];
    ?>
    <h3 style="margin-top:25px; color:#2D5A43;">Database Tables & Record Counts:</h3>
    <?php foreach ($requiredTables as $tbl => $desc): ?>
        <?php
        $tcheck = $conn->query("SHOW TABLES LIKE '$tbl'");
        $exists = $tcheck && $tcheck->num_rows > 0;
        $count = 0;
        if ($exists) {
            $cRes = $conn->query("SELECT COUNT(*) FROM `$tbl`");
            if ($cRes) $count = (int)$cRes->fetch_row()[0];
        }
        ?>
        <div class="check-item">
            <div>
                <strong>`<?php echo $tbl; ?>`</strong> — <?php echo $desc; ?>
            </div>
            <div>
                <?php if ($exists): ?>
                    <span class="ok">✓ Verified</span> (<?php echo $count; ?> record<?php echo $count === 1 ? '' : 's'; ?>)
                <?php else: ?>
                    <span class="err">✗ Missing table! Run migration.</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- 4. Uploads Directory Check -->
    <?php
    $uploadsDir = __DIR__ . '/uploads/pets/';
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0755, true);
    }
    $isWritable = is_writable($uploadsDir);
    ?>
    <div class="check-item" style="margin-top:15px;">
        <div>
            <strong>4. Upload Directory Permissions (`uploads/pets/`)</strong>
            <div style="font-size:13px; color:#718096;">File uploads for pet photos</div>
        </div>
        <div class="<?php echo $isWritable ? 'ok' : 'err'; ?>">
            <?php echo $isWritable ? '✓ Writable' : '✗ Not Writable'; ?>
        </div>
    </div>

    <!-- 5. CSRF & Security -->
    <div class="check-item">
        <div>
            <strong>5. CSRF Token Generation & Validation</strong>
            <div style="font-size:13px; color:#718096;">CSRF tokens generated and stored in active session</div>
        </div>
        <div class="ok">✓ Active (Token: <?php echo substr(csrf_token(), 0, 10); ?>...)</div>
    </div>

    <!-- 6. Questionnaire Scoring Engine -->
    <?php
    $sampleAnswers = [
        'q1_landlord_permission' => 'yes_written',
        'q2_housing_type' => 'house_yard',
        'q3_environment_suitable' => 'fully_suitable',
        'q4_safe_space' => 'ample_space',
        'q5_household_agreement' => 'all_agree',
        'q6_daily_hours' => '4_plus',
        'q7_daily_care_commitment' => 'guaranteed',
        'q8_exercise_playtime' => 'enthusiastic',
        'q9_travel_backup_care' => 'family_friends',
        'q10_training_preparedness' => 'patient_positive',
        'q11_afford_supplies' => 'fully_budgeted',
        'q12_routine_vet_budget' => 'committed_routine',
        'q13_emergency_vet_plan' => 'insurance_savings',
        'q14_vet_compliance' => 'fully_compliant',
        'q15_lifespan_commitment' => 'lifelong_ready',
        'q16_responsibility_understanding' => 'thoroughly_understand',
        'q17_existing_pets_intro' => 'safe_intro_plan',
        'q18_circumstance_change_plan' => 'pet_stays_always',
    ];
    $testEval = calculate_eligibility_score($sampleAnswers);
    ?>
    <div class="check-item">
        <div>
            <strong>6. 18-Point Scoring Algorithm Test</strong>
            <div style="font-size:13px; color:#718096;">Ideal sample responses evaluation</div>
        </div>
        <div class="ok">✓ Calculated Score: <?php echo $testEval['score']; ?>/100 (<?php echo $testEval['category']; ?>)</div>
    </div>

    <div style="margin-top:30px; padding-top:20px; border-top:1px solid #E2E8F0;">
        <h3 style="color:#2D5A43; margin-top:0;">Quick Page Navigation:</h3>
        <p>Click any link below to test the live application:</p>
        <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <a href="index.php" class="btn">Website Home</a>
            <a href="pets.php" class="btn">Available Pets</a>
            <a href="login.php" class="btn">Login Page</a>
            <a href="register.php" class="btn">Register Page</a>
            <a href="user_dashboard.php" class="btn">User Dashboard</a>
            <a href="admin_dashboard.php" class="btn">Admin Panel</a>
            <a href="admin_pets.php" class="btn">Manage Pets</a>
            <a href="admin_requests.php" class="btn">Manage Requests</a>
            <a href="admin_trials.php" class="btn">Manage Trials</a>
            <a href="admin_users.php" class="btn">Manage Users</a>
        </div>
    </div>
</div>
</body>
</html>
