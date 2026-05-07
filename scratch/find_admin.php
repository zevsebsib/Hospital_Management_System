<?php
/**
 * Admin Account Lookup Utility
 * 
 * Development utility for finding the first admin user account email.
 * Useful for quick login credential retrieval during development.
 * 
 * Usage: Call directly via PHP CLI or web browser
 * Output: Displays admin email or 'No admin found' message
 * 
 * Note: This is a development-only file. Remove before production deployment.
 */

require_once 'includes/db.php';
$s = getDB()->query("SELECT email FROM users WHERE role='admin' LIMIT 1");
$u = $s->fetch();
echo $u ? $u['email'] : 'No admin found';
