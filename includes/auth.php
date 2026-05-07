<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool { return isset($_SESSION['user_id']); }
function isAdmin(): bool    { return ($_SESSION['role'] ?? '') === 'admin'; }
function isDoctor(): bool   { return ($_SESSION['role'] ?? '') === 'doctor'; }

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /hms/index.php');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /hms/dashboard.php?error=unauthorized');
        exit;
    }
}

function requireDoctor(): void {
    requireLogin();
    if (!isDoctor() && !isAdmin()) {
        header('Location: /hms/dashboard.php?error=unauthorized');
        exit;
    }
}

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
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function getFlash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ── CSRF Protection ───────────────────────────────────────────────────────────
/**
 * Returns (and generates if needed) a per-session CSRF token.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Outputs a hidden <input> carrying the CSRF token — drop inside every <form>.
 */
function csrfField(): void {
    echo '<input type="hidden" name="_csrf" value="' . csrfToken() . '">';
}

/**
 * Validates the CSRF token submitted with a POST request.
 * Calls setFlash + redirects and exits on failure.
 */
function verifyCsrf(string $redirectBack = '/hms/dashboard.php'): void {
    $submitted = $_POST['_csrf'] ?? '';
    if (!hash_equals(csrfToken(), $submitted)) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . $redirectBack);
        exit;
    }
}
