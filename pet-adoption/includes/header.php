<?php
// Shared Header Component
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

$pageTitle = $pageTitle ?? 'Pet Haven | Every Pet Deserves Love';
$activePage = $activePage ?? '';
$isUserLoggedIn = is_logged_in();
$userName = current_user_name();
$userRole = current_user_role();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Design System Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon (Emoji SVG) -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220%22%3E<text y=%2226%22 font-size=%2224%22>🐾</text></svg>">
</head>
<body>

<header class="navbar">
    <div class="container nav-inner">
        <a href="index.php" class="brand">
            <span class="brand-icon">🐾</span>
            <span>Pet Haven</span>
        </a>

        <ul class="nav-links">
            <li>
                <a href="index.php" class="nav-link <?php echo $activePage === 'home' ? 'active' : ''; ?>">Home</a>
            </li>
            <li>
                <a href="pets.php" class="nav-link <?php echo $activePage === 'pets' ? 'active' : ''; ?>">Find Pets</a>
            </li>

            <?php if ($isUserLoggedIn): ?>
                <?php if ($userRole === 'admin'): ?>
                    <li>
                        <a href="admin_dashboard.php" class="nav-link <?php echo strpos($activePage, 'admin') === 0 ? 'active' : ''; ?>">
                            Admin Panel <span class="nav-badge-admin">Admin</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li>
                    <a href="user_dashboard.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">
                        My Applications
                    </a>
                </li>
                <li>
                    <a href="profile.php" class="nav-link <?php echo $activePage === 'profile' ? 'active' : ''; ?>">
                        Profile
                    </a>
                </li>
                <li>
                    <a href="logout.php" class="btn btn-secondary btn-sm">
                        Logout (<?php echo htmlspecialchars(explode(' ', trim($userName))[0]); ?>)
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="login.php" class="nav-link <?php echo $activePage === 'login' ? 'active' : ''; ?>">Login</a>
                </li>
                <li>
                    <a href="register.php" class="btn btn-primary btn-sm">Register Free</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</header>

<!-- Flash Alerts Container -->
<?php echo flash_render(); ?>

<main>
