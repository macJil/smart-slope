<?php
// auth.php — Authentication, authorization, and CSRF protection

session_start();

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Auto-require login — redirect to login.php if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ── Authorization helpers ──────────────────────────────

/** Redirect if user role is not in the allowed list. */
function requireRole(string ...$roles): void {
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('Access denied. You do not have permission to view this page.');
    }
}

/** True if the current user is an admin. */
function isAdmin(): bool {
    return ($_SESSION['role'] ?? '') === 'admin';
}

/** True if the current user is admin or CDRRMO staff. */
function isStaff(): bool {
    return in_array($_SESSION['role'] ?? '', ['admin', 'cdrmo_staff'], true);
}

/** Return the current user's session info as an array. */
function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'user_id'  => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role'] ?? '',
    ];
}

// ── CSRF helpers ───────────────────────────────────────

/** Return the current CSRF token (output in forms and JS). */
function csrfToken(): string {
    return $_SESSION['csrf_token'] ?? '';
}

/** Verify the CSRF token on POST requests. Call before modifying the database. */
function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid CSRF token. Please refresh the page and try again.');
    }
}