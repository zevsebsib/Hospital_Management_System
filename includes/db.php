<?php
// includes/db.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'medicore_hms');
define('DB_USER', 'root');
define('DB_PASS', '');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            error_log('DB Connection Error: ' . $e->getMessage()); // logs to XAMPP error log
            die('<div style="font-family:sans-serif;padding:40px;color:#dc2626;max-width:600px">
                <h2>⚠️ Database Connection Failed</h2>
                <p style="color:#64748b;">Could not connect to <strong>' . DB_NAME . '</strong> on <strong>' . DB_HOST . '</strong>.</p>
                <p style="color:#94a3b8;font-size:14px;">Check your XAMPP MySQL service and credentials in <code>includes/db.php</code></p>
            </div>');
        }
    }
    return $pdo;
}