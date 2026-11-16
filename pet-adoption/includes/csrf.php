<?php
// CSRF Protection Helper

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or retrieve the existing CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden HTML input containing the CSRF token
 */
function csrf_field(): string {
    $token = htmlspecialchars(csrf_token());
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verify submitted CSRF token
 */
function csrf_verify(?string $submitted_token = null): bool {
    if ($submitted_token === null) {
        $submitted_token = $_POST['csrf_token'] ?? '';
    }
    
    if (empty($_SESSION['csrf_token']) || empty($submitted_token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $submitted_token);
}
