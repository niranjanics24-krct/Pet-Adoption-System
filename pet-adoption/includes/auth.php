<?php
// Authentication & Session Management Helper
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    // Set secure session cookie parameters
    session_start();
}

/**
 * Check if the user is logged in
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get the current user's ID or null
 */
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get current user's display name
 */
function current_user_name(): string {
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Get current user's role: 'admin' or 'user'
 */
function current_user_role(): string {
    return $_SESSION['user_role'] ?? 'guest';
}

/**
 * Check if current user is an admin
 */
function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * Require user login; redirect to login page if unauthenticated
 */
function require_login(string $redirect_after = ''): void {
    if (!is_logged_in()) {
        $target = 'login.php';
        if (!empty($redirect_after)) {
            $target .= '?redirect=' . urlencode($redirect_after);
        }
        flash_set('error', 'Please log in to continue.');
        header("Location: $target");
        exit;
    }
}

/**
 * Require admin access; abort with 403 or redirect if not admin
 */
function require_admin(): void {
    if (!is_logged_in()) {
        flash_set('error', 'Please log in with an administrator account.');
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? 'admin_dashboard.php'));
        exit;
    }

    if (!is_admin()) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>403 Access Denied | Pet Haven</title>
            <style>
                body { font-family: sans-serif; background: #FAF7F2; color: #2D3748; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
                .card { background: white; padding: 35px; border-radius: 14px; max-width: 500px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.06); border-top: 6px solid #C53030; }
                h1 { color: #C53030; font-size: 24px; margin-bottom: 12px; }
                p { line-height: 1.6; color: #718096; margin-bottom: 25px; }
                a { display: inline-block; padding: 10px 20px; background: #2D5A43; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>🚫 Access Denied</h1>
                <p>This area requires administrator privileges. Your account does not have access to this page.</p>
                <a href="index.php">Return to Homepage</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/**
 * Flash message helper: set message for next request
 */
function flash_set(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Flash message helper: render and clear stored messages
 */
function flash_render(): string {
    if (empty($_SESSION['flash'])) {
        return '';
    }

    $html = '<div class="flash-messages-container" style="max-width:1100px; margin: 15px auto; padding: 0 15px;">';
    foreach ($_SESSION['flash'] as $item) {
        $type = htmlspecialchars($item['type']);
        $msg = htmlspecialchars($item['message']);
        $colorClass = $type === 'success' ? 'alert-success' : ($type === 'error' ? 'alert-danger' : 'alert-info');
        $icon = $type === 'success' ? '✓ ' : ($type === 'error' ? '⚠️ ' : 'ℹ️ ');
        $html .= "<div class=\"alert {$colorClass}\" role=\"alert\" style=\"padding:14px 18px; border-radius:10px; margin-bottom:12px; font-size:15px; display:flex; justify-content:space-between; align-items:center;\">
                    <div><strong>{$icon}</strong> {$msg}</div>
                    <button type=\"button\" onclick=\"this.parentElement.remove();\" style=\"background:none; border:none; font-size:18px; cursor:pointer; color:inherit;\">&times;</button>
                  </div>";
    }
    $html .= '</div>';
    unset($_SESSION['flash']);
    return $html;
}
