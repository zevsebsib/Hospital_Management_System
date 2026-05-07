<?php
/**
 * Database Schema Inspection Utility
 * 
 * Development utility for examining database table structures.
 * Outputs schema information for 'users' and 'doctors' tables.
 * 
 * Usage: Call directly via PHP CLI or web browser
 * Output: Prints array dump of table column definitions
 * 
 * Note: This is a development-only file. Remove before production deployment.
 */

require 'includes/db.php';
try {
    $s = getDB()->query('DESCRIBE users');
    print_r($s->fetchAll());
} catch (Exception $e) {
    echo "Error describing users: " . $e->getMessage() . "\n";
}

try {
    $s = getDB()->query('DESCRIBE doctors');
    print_r($s->fetchAll());
} catch (Exception $e) {
    echo "Error describing doctors: " . $e->getMessage() . "\n";
}
