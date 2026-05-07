<?php
require_once 'includes/db.php';
$s = getDB()->query("SELECT email FROM users WHERE role='admin' LIMIT 1");
$u = $s->fetch();
echo $u ? $u['email'] : 'No admin found';
