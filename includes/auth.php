<?php
/**
 * Authentication & Authorization Module
 * 
 * Handles user login verification, role-based access control, session management,
 * CSRF protection, and flash messaging for the HMS system.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is currently logged in
 * 
 * Verifies the presence of user_id in the session
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn(): bool { return isset($_SESSION['user_id']); }

/**
 * Check if the current user has administrator role
 * 
 * @return bool True if user role is 'admin', false otherwise
 */
function isAdmin(): bool    { return ($_SESSION['role'] ?? '') === 'admin'; }

/**
 * Check if the current user has doctor role
 * 
 * @return bool True if user role is 'doctor', false otherwise
 */
function isDoctor(): bool   { return ($_SESSION['role'] ?? '') === 'doctor'; }

/**
 * Enforce login requirement
 * 
 * Redirects unauthenticated users to the login page.
 * Exits execution after redirect.
 * 
 * @return void Redirects and exits if not logged in
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /hms/index.php');
        exit;
    }
}

/**
 * Enforce administrator access requirement
 * 
 * Requires user to be logged in AND have admin role.
 * Redirects to dashboard if authorization fails.
 * 
 * @return void Redirects and exits if not admin
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /hms/dashboard.php?error=unauthorized');
        exit;
    }
}

/**
 * Enforce doctor or administrator access requirement
 * 
 * Allows both doctor and admin roles.
 * Redirects to dashboard if authorization fails.
 * 
 * @return void Redirects and exits if not doctor or admin
 */
function requireDoctor(): void {
    requireLogin();
    if (!isDoctor() && !isAdmin()) {
        header('Location: /hms/dashboard.php?error=unauthorized');
        exit;
    }
}

/**
 * Get the current user's information from session
 * 
 * Assembles user data from session variables into a structured array.
 * 
 * @return array User information array with keys: id, name, initials, role, doctor_id
 */
function currentUser(): array {
    return [
        'id'        => $_SESSION['user_id']   ?? null,
        'name'      => $_SESSION['user_name'] ?? '',
        'initials'  => $_SESSION['initials']  ?? 'U',
        'role'      => $_SESSION['role']      ?? '',
        'doctor_id' => $_SESSION['doctor_id'] ?? null,
    ];
}

// ── Flash Messages ────────────────────────────────────────────────────────────

/**
 * Set a flash message for display on the next page load
 * 
 * Flash messages are one-time messages automatically cleared after retrieval.
 * 
 * @param string $type Message type ('success', 'danger', 'warning', 'error')
 * @param string $msg The message content to display
 * @return void
 */
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

/**
 * Retrieve and clear the current flash message from session
 * 
 * Automatically removes the flash message after retrieval (one-time display).
 * 
 * @return ?array Array with 'type' and 'msg' keys, or null if no message
 */
function getFlash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ── CSRF Protection ───────────────────────────────────────────────────────────

/**
 * Generate and retrieve a CSRF (Cross-Site Request Forgery) protection token
 * 
 * Returns existing token or generates a new random 64-character hex string
 * stored in the session for this user.
 * 
 * @return string The CSRF token for this session
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden form input field containing the CSRF token
 * 
 * Place this inside every HTML form to protect against CSRF attacks.
 * 
 * @return void Echoes HTML hidden input tag
 */
function csrfField(): void {
    echo '<input type="hidden" name="_csrf" value="' . csrfToken() . '">';
}

/**
 * Validate the CSRF token from a POST request
 * 
 * Compares submitted token with session token using timing-safe comparison.
 * Sets flash message and redirects on failure.
 * 
 * @param string $redirectBack URL to redirect to on failure (default: dashboard)
 * @return void Redirects and exits on token mismatch
 */
function verifyCsrf(string $redirectBack = '/hms/dashboard.php'): void {
    $submitted = $_POST['_csrf'] ?? '';
    if (!hash_equals(csrfToken(), $submitted)) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . $redirectBack);
        exit;
    }
}
