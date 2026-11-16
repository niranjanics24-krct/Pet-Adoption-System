<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Log In | Pet Haven';
$activePage = 'login';

$email = '';
$redirect = $_GET['redirect'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Security session expired. Please try submitting again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $redirect = trim($_POST['redirect'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'Please enter both your email address and password.';
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password'])) {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];

                    $stmt->close();

                    flash_set('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

                    // Redirect to safe intended destination or home/admin
                    if (!empty($redirect) && strpos($redirect, '://') === false && strpos($redirect, '//') === false) {
                        header("Location: " . $redirect);
                    } elseif ($user['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: index.php");
                    }
                    exit;
                }
            }

            $error = 'Invalid email address or password. Please verify your credentials.';
            if (isset($stmt)) $stmt->close();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="margin-top: 40px; margin-bottom: 50px;">
    <div class="auth-card">
        <div style="text-align:center; font-size:42px; margin-bottom:12px;">🐾</div>
        <h1 style="text-align:center; color:var(--primary-forest); font-size:26px; margin-bottom:8px;">
            Welcome Back
        </h1>
        <p style="text-align:center; color:var(--text-muted); font-size:15px; margin-bottom:25px;">
            Sign in to check adoption status, continue applications, or manage pets.
        </p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="Enter your registered email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Enter your account password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-top:10px;">
                Sign In →
            </button>
        </form>

        <div style="text-align:center; margin-top:22px; font-size:14px; color:var(--text-muted);">
            Don't have an account yet? 
            <a href="register.php" style="font-weight:700; color:var(--warm-terracotta);">Register now</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
