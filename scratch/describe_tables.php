<?php
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
