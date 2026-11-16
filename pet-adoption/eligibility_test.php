<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/scoring.php';

// User must be logged in
require_login('eligibility_test.php?' . http_build_query($_GET));

$userId = current_user_id();
$petId = (int)($_GET['pet_id'] ?? ($_POST['pet_id'] ?? 0));

if ($petId <= 0) {
    flash_set('error', 'Please select a valid pet before starting the assessment.');
    header('Location: pets.php');
    exit;
}

// Fetch pet details
$stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
$stmt->bind_param("i", $petId);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pet) {
    flash_set('error', 'The selected pet was not found.');
    header('Location: pets.php');
    exit;
}

// Check pet availability
if ($pet['status'] !== 'Available') {
    flash_set('error', htmlspecialchars($pet['name']) . ' is currently ' . htmlspecialchars($pet['status']) . ' and not open for new adoption applications.');
    header('Location: pet_details.php?id=' . $petId);
    exit;
}

// Check for existing active application for this user and pet
$checkExisting = $conn->prepare("SELECT id, status FROM adoption_requests WHERE user_id = ? AND pet_id = ? AND status IN ('Pending', 'Approved')");
$checkExisting->bind_param("ii", $userId, $petId);
$checkExisting->execute();
$existingReq = $checkExisting->get_result()->fetch_assoc();
$checkExisting->close();

if ($existingReq) {
    flash_set('info', 'You already have an active (' . htmlspecialchars($existingReq['status']) . ') application for ' . htmlspecialchars($pet['name']) . '. You can track its status in your dashboard.');
    header('Location: user_dashboard.php');
    exit;
}

$definition = get_questionnaire_definition();
$errors = [];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Security token expired. Please try submitting again.';
    }

    // Collect submitted answers
    $submittedAnswers = [];
    $allRequiredAnswered = true;

    foreach ($definition as $secKey => $secDef) {
        foreach ($secDef['questions'] as $qKey => $qDef) {
            $val = trim($_POST[$qKey] ?? '');
            if ($qDef['required'] && $val === '') {
                $allRequiredAnswered = false;
                $errors[] = 'Please provide an answer for: ' . $qDef['text'];
            }
            $submittedAnswers[$qKey] = $val;
        }
    }

    if (empty($errors)) {
        // Calculate eligibility score on the PHP server
        $evaluation = calculate_eligibility_score($submittedAnswers, $pet);
        $score = $evaluation['score'];

        // Begin MySQL database transaction
        $conn->begin_transaction();

        try {
            // Re-validate pet status inside transaction (lock row for check)
            $petCheck = $conn->prepare("SELECT status FROM pets WHERE id = ? FOR UPDATE");
            $petCheck->bind_param("i", $petId);
            $petCheck->execute();
            $currentPetStatus = $petCheck->get_result()->fetch_assoc()['status'] ?? '';
            $petCheck->close();

            if ($currentPetStatus !== 'Available') {
                throw new Exception("We apologize, but this pet's status was just updated to {$currentPetStatus} by shelter staff.");
            }

            // Insert adoption request
            $reqStmt = $conn->prepare("INSERT INTO adoption_requests (user_id, pet_id, eligibility_score, status) VALUES (?, ?, ?, 'Pending')");
            $reqStmt->bind_param("iid", $userId, $petId, $score);
            if (!$reqStmt->execute()) {
                throw new Exception("Failed to record adoption request: " . $reqStmt->error);
            }
            $requestId = $reqStmt->insert_id;
            $reqStmt->close();

            // Insert each individual answer into eligibility_answers
            $ansStmt = $conn->prepare("INSERT INTO eligibility_answers (request_id, question_key, question_text, submitted_answer) VALUES (?, ?, ?, ?)");
            
            foreach ($evaluation['answers_keyed'] as $k => $item) {
                $qText = $item['question_text'];
                $ansVal = $item['display_answer'];
                $ansStmt->bind_param("isss", $requestId, $k, $qText, $ansVal);
                if (!$ansStmt->execute()) {
                    throw new Exception("Failed to record questionnaire answer: " . $ansStmt->error);
                }
            }
            $ansStmt->close();

            // Commit transaction
            $conn->commit();

            flash_set('success', '🎉 Adoption request submitted successfully for ' . htmlspecialchars($pet['name']) . '! Your compatibility score is ' . number_format($score, 0) . '/100.');
            header('Location: user_dashboard.php');
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

$pageTitle = 'Adoption Eligibility Questionnaire | Pet Haven';
$activePage = 'pets';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container quiz-container">
    <!-- Header Banner -->
    <div style="background:var(--bg-surface); padding:24px; border-radius:var(--radius-md); border:1px solid var(--border-color); box-shadow:var(--shadow-sm); margin-bottom:25px; display:flex; align-items:center; gap:20px;">
        <img src="<?php echo htmlspecialchars($pet['image'] ?: 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=200&q=80'); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>" style="width:75px; height:75px; border-radius:var(--radius-sm); object-fit:cover;">
        <div>
            <div style="font-size:12px; font-weight:700; color:var(--warm-terracotta); text-transform:uppercase;">Application Questionnaire</div>
            <h1 style="font-size:24px; color:var(--primary-forest); margin:4px 0;">Applying to Adopt: <?php echo htmlspecialchars($pet['name']); ?></h1>
            <p style="font-size:14px; color:var(--text-muted); margin:0;">
                <?php echo htmlspecialchars($pet['breed'] ?? 'Companion'); ?> • <?php echo htmlspecialchars($pet['gender'] ?? 'Pet'); ?> • <?php echo htmlspecialchars($pet['age'] ?? 'Age unknown'); ?>
            </p>
        </div>
    </div>

    <!-- Stepper Navigation -->
    <div class="quiz-stepper">
        <div class="step-item active" data-target-step="1">
            <div class="step-circle">A</div>
            <div class="step-label">Environment</div>
        </div>
        <div class="step-item" data-target-step="2">
            <div class="step-circle">B</div>
            <div class="step-label">Time & Care</div>
        </div>
        <div class="step-item" data-target-step="3">
            <div class="step-circle">C</div>
            <div class="step-label">Financial</div>
        </div>
        <div class="step-item" data-target-step="4">
            <div class="step-circle">D</div>
            <div class="step-label">Commitment</div>
        </div>
        <div class="step-item" data-target-step="5">
            <div class="step-circle">✓</div>
            <div class="step-label">Review</div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div style="background:#E2DCD5; height:6px; border-radius:3px; margin-bottom:25px; overflow:hidden;">
        <div id="wizard-progress-bar" style="background:var(--primary-forest); width:20%; height:100%; transition:width 0.3s ease;"></div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <span id="wizard-step-counter" style="font-size:14px; font-weight:700; color:var(--primary-forest);">Step 1 of 5 (Questions 1 - 5)</span>
        <span style="font-size:13px; color:var(--text-muted);">* All questions required unless noted</span>
    </div>

    <!-- Validation Error Box -->
    <div id="wizard-step-error" class="alert alert-danger" style="display:none;"></div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Please resolve the following items:</strong>
            <ul style="margin:6px 0 0; padding-left:20px;">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Questionnaire Form -->
    <form id="quiz-wizard" method="POST" action="eligibility_test.php?pet_id=<?php echo $petId; ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="pet_id" value="<?php echo $petId; ?>">

        <?php 
        $stepIndex = 1;
        foreach ($definition as $secKey => $section): 
        ?>
            <div class="wizard-step" data-step="<?php echo $stepIndex; ?>" style="<?php echo $stepIndex === 1 ? 'display:block;' : 'display:none;'; ?>">
                <div style="margin-bottom:22px;">
                    <h2 style="font-size:20px; color:var(--primary-forest); display:flex; align-items:center; gap:8px;">
                        <span><?php echo $section['icon']; ?></span>
                        <span><?php echo htmlspecialchars($section['title']); ?></span>
                    </h2>
                    <p style="color:var(--text-muted); font-size:14px; margin-top:4px;">
                        <?php echo htmlspecialchars($section['description']); ?>
                    </p>
                </div>

                <?php foreach ($section['questions'] as $qKey => $q): ?>
                    <div class="quiz-question-box" data-required="<?php echo $q['required'] ? '1' : '0'; ?>" id="box_<?php echo $qKey; ?>">
                        <div class="quiz-q-num">Question <?php echo $q['number']; ?> of 18</div>
                        <div class="quiz-q-title">
                            <?php echo htmlspecialchars($q['text']); ?>
                            <?php if ($q['required']): ?>
                                <span style="color:#C53030;">*</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($q['type'] === 'radio'): ?>
                            <div class="quiz-options">
                                <?php foreach ($q['options'] as $optKey => $opt): ?>
                                    <label class="option-card">
                                        <input 
                                            type="radio" 
                                            name="<?php echo $qKey; ?>" 
                                            value="<?php echo $optKey; ?>"
                                            <?php echo (isset($_POST[$qKey]) && $_POST[$qKey] === $optKey) ? 'checked' : ''; ?>
                                        >
                                        <span class="option-text" style="font-size:14px; color:var(--text-dark);">
                                            <?php echo htmlspecialchars($opt['label']); ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($q['type'] === 'select'): ?>
                            <select name="<?php echo $qKey; ?>" class="form-control" style="margin-top:8px;">
                                <option value="">-- Please select an option --</option>
                                <?php foreach ($q['options'] as $optKey => $opt): ?>
                                    <option value="<?php echo $optKey; ?>" <?php echo (isset($_POST[$qKey]) && $_POST[$qKey] === $optKey) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($opt['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        <?php elseif ($q['type'] === 'textarea'): ?>
                            <textarea 
                                name="<?php echo $qKey; ?>" 
                                class="form-control" 
                                rows="3" 
                                placeholder="Share any relevant notes, routine preferences, or questions for our shelter team..."
                            ><?php echo htmlspecialchars($_POST[$qKey] ?? ''); ?></textarea>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php 
            $stepIndex++;
        endforeach; 
        ?>

        <!-- Step 5: Review & Submit -->
        <div class="wizard-step" data-step="5" style="display:none;">
            <div style="margin-bottom:22px;">
                <h2 style="font-size:20px; color:var(--primary-forest); display:flex; align-items:center; gap:8px;">
                    <span>📋</span>
                    <span>Review Your Application Summary</span>
                </h2>
                <p style="color:var(--text-muted); font-size:14px; margin-top:4px;">
                    Please verify your responses before submitting. Our server will evaluate your answers to generate an initial compatibility rating for <?php echo htmlspecialchars($pet['name']); ?>.
                </p>
            </div>

            <!-- Dynamic Summary populated by JS -->
            <div id="quiz-review-container" style="margin-bottom:25px;"></div>

            <div style="background:var(--sage-light); padding:20px; border-radius:var(--radius-md); border-left:4px solid var(--primary-forest); margin-bottom:25px;">
                <h4 style="color:var(--primary-forest); font-size:15px; margin-bottom:6px;">Applicant Acknowledgment</h4>
                <p style="font-size:13px; color:var(--text-muted); line-height:1.6; margin:0;">
                    By submitting this application, I confirm that all provided details are true and accurate. 
                    I understand that the calculated score is an initial screening aid and the shelter coordinator 
                    makes the final adoption approval decision.
                </p>
            </div>
        </div>

        <!-- Wizard Navigation Actions -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:30px; padding-top:20px; border-top:1px solid var(--border-color);">
            <button type="button" id="wizard-prev-btn" class="btn btn-secondary" style="display:none;">
                ← Previous Section
            </button>
            <div style="flex:1;"></div>
            <button type="button" id="wizard-next-btn" class="btn btn-primary">
                Next Section →
            </button>
            <button type="submit" id="wizard-submit-btn" class="btn btn-accent btn-lg" style="display:none;">
                Confirm & Submit Adoption Application →
            </button>
        </div>
    </form>
</div>

<!-- Load Questionnaire Script -->
<script src="assets/js/questionnaire.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
