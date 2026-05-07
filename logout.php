<?php
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
