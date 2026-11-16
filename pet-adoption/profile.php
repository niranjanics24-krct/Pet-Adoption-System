<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

require_login('profile.php');

$userId = current_user_id();
$pageTitle = 'My Profile | Pet Haven';
$activePage = 'profile';

// Fetch latest user data from DB
$stmt = $conn->prepare("SELECT id, name, email, phone, address, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    flash_set('error', 'User account not found.');
    header('Location: logout.php');
    exit;
}

$profileSuccess = '';
$profileError = '';
$passwordSuccess = '';
$passwordError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Session expired. Please try again.');
        header('Location: profile.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    // Handle Profile Details Update
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if ($name === '' || $phone === '') {
            $profileError = 'Name and phone number cannot be blank.';
        } else {
            $update = $conn->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
            $update->bind_param("sssi", $name, $phone, $address, $userId);
            if ($update->execute()) {
                $_SESSION['user_name'] = $name;
                $user['name'] = $name;
                $user['phone'] = $phone;
                $user['address'] = $address;
                $profileSuccess = 'Your profile details have been updated successfully!';
            } else {
                $profileError = 'Failed to update profile: ' . $conn->error;
            }
            $update->close();
        }
    }

    // Handle Password Change
    if ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if ($currentPass === '' || $newPass === '' || $confirmPass === '') {
            $passwordError = 'Please fill out all password fields.';
        } elseif (strlen($newPass) < 8) {
            $passwordError = 'New password must be at least 8 characters.';
        } elseif ($newPass !== $confirmPass) {
            $passwordError = 'New password and confirmation do not match.';
        } else {
            // Verify current password
            $pcheck = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $pcheck->bind_param("i", $userId);
            $pcheck->execute();
            $currHash = $pcheck->get_result()->fetch_assoc()['password'] ?? '';
            $pcheck->close();

            if (!password_verify($currentPass, $currHash)) {
                $passwordError = 'Current password is incorrect.';
            } else {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $pupdate = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $pupdate->bind_param("si", $newHash, $userId);
                if ($pupdate->execute()) {
                    $passwordSuccess = 'Password updated successfully!';
                } else {
                    $passwordError = 'Failed to update password: ' . $conn->error;
                }
                $pupdate->close();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container container-narrow" style="margin-top: 35px; margin-bottom: 50px;">
    <div style="margin-bottom: 25px;">
        <h1 style="color:var(--primary-forest); font-size:28px; margin-bottom:6px;">My Profile Settings</h1>
        <p style="color:var(--text-muted);">Manage your contact information and security preferences.</p>
    </div>

    <!-- Personal Details Form -->
    <div class="content-card">
        <h2 style="font-size:19px; color:var(--primary-forest); margin-bottom:18px;">
            👤 Contact Information
        </h2>

        <?php if ($profileSuccess): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($profileSuccess); ?></div>
        <?php endif; ?>
        <?php if ($profileError): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($profileError); ?></div>
        <?php endif; ?>

        <form method="POST" action="profile.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="background:#F7F4EE; color:#718096; cursor:not-allowed;">
                <small style="color:var(--text-muted); font-size:12px;">Email cannot be changed directly.</small>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label for="address">Residential Address / City</label>
                <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px;">
                <span style="font-size:13px; color:var(--text-muted);">
                    Account Role: <strong><?php echo ucfirst(htmlspecialchars($user['role'])); ?></strong> • Member since <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                </span>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>

    <!-- Change Password Form -->
    <div class="content-card">
        <h2 style="font-size:19px; color:var(--primary-forest); margin-bottom:18px;">
            🔒 Security & Password
        </h2>

        <?php if ($passwordSuccess): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($passwordSuccess); ?></div>
        <?php endif; ?>
        <?php if ($passwordError): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($passwordError); ?></div>
        <?php endif; ?>

        <form method="POST" action="profile.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="change_password">

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter your current password" required>
            </div>

            <div class="form-group">
                <label for="new_password">New Password (min 8 characters)</label>
                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new strong password" minlength="8" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter new password" minlength="8" required>
            </div>

            <button type="submit" class="btn btn-secondary">Update Password</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
