<?php
/**
 * MediCore HMS — Logout Handler
 * 
 * Securely terminates user session and redirects to login page.
 * 
 * Security measures:
 * - Clears all session variables
 * - Invalidates session cookie with proper parameters
 * - Destroys session ID on server side
 * - Regenerates session ID to prevent fixation attacks
 * - Redirects to login page (index.php)
 * 
 * No output - this is a redirect handler only.
 */

session_start();

// Clear all session variables first
$_SESSION = [];

// Invalidate the session cookie
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']
    );
}

// Destroy the session and regenerate ID to prevent reuse
session_destroy();
session_start();
session_regenerate_id(true);
session_destroy();

header('Location: /hms/index.php');
exit;
