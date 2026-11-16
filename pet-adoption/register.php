<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Create Adopter Account | Pet Haven';
$activePage = 'register';

$name = '';
$email = '';
$phone = '';
$address = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Security validation failed (CSRF token expired). Please try again.';
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Field Validations
    if ($name === '' || $email === '' || $phone === '' || $password === '' || $confirmPassword === '') {
        $errors[] = 'Please fill in all required fields.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check for duplicate email
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = 'This email address is already registered. Please log in instead.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';

            $stmt = $conn->prepare(
                "INSERT INTO users (name, email, password, phone, address, role)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssssss", $name, $email, $hashedPassword, $phone, $address, $role);

            if ($stmt->execute()) {
                $newUserId = $stmt->insert_id;
                $stmt->close();
                $check->close();

                // Automatically log user in or flash success message
                session_regenerate_id(true);
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;

                flash_set('success', 'Welcome to Pet Haven, ' . htmlspecialchars($name) . '! Your account has been created.');
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Account creation failed: ' . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="margin-top: 35px; margin-bottom: 45px;">
    <div class="auth-card">
        <div style="text-align:center; font-size:42px; margin-bottom:12px;">🐶</div>
        <h1 style="text-align:center; color:var(--primary-forest); font-size:26px; margin-bottom:8px;">
            Join Pet Haven
        </h1>
        <p style="text-align:center; color:var(--text-muted); font-size:15px; margin-bottom:25px;">
            Create your adopter account to begin taking eligibility assessments and meeting your future best friend.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin:0; padding-left:18px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label for="name">Full Name *</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control"
                    placeholder="e.g. Sarah Jenkins"
                    value="<?php echo htmlspecialchars($name); ?>"
                    maxlength="100"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="you@example.com"
                    value="<?php echo htmlspecialchars($email); ?>"
                    maxlength="150"
                    required
                >
            </div>

            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    class="form-control"
                    placeholder="e.g. 555-123-4567"
                    value="<?php echo htmlspecialchars($phone); ?>"
                    maxlength="20"
                    required
                >
            </div>

            <div class="form-group">
                <label for="address">Residential Address / City (Optional)</label>
                <input
                    type="text"
                    id="address"
                    name="address"
                    class="form-control"
                    placeholder="e.g. 14 Elm Street, Springfield"
                    value="<?php echo htmlspecialchars($address); ?>"
                    maxlength="255"
                >
            </div>

            <div class="form-group">
                <label for="password">Password * (minimum 8 characters)</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Choose a strong password"
                    minlength="8"
                    required
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-control"
                    placeholder="Re-enter your password"
                    minlength="8"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;">
                Create Account & Get Started →
            </button>
        </form>

        <div style="text-align:center; margin-top:22px; font-size:14px; color:var(--text-muted);">
            Already have an account? <a href="login.php" style="font-weight:700; color:var(--warm-terracotta);">Log in here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
