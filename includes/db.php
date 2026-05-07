<?php
/**
 * Database Configuration and Connection Module
 * 
 * This module handles all database connectivity for the MediCore HMS system.
 * It uses PDO for secure database operations with prepared statements.
 */

// Database connection credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'medicore_hms');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Get a singleton PDO database connection
 * 
 * Returns the same database connection instance across multiple calls.
 * Initializes connection on first call with proper error handling and
 * configuration for UTF-8, exception handling, and associative array fetching.
 * 
 * @return PDO The singleton database connection instance
 * @throws PDOException If database connection fails (displays error page)
 */
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