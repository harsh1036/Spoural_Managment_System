<?php
session_start();

// Check if session variables are set
if (!isset($_SESSION['session_start']) || !isset($_SESSION['session_timeout'])) {
    $_SESSION['session_start'] = time();
    $_SESSION['session_timeout'] = 1800; // 30 minutes
}

// Check session timeout
$current_time = time();
$session_age = $current_time - $_SESSION['session_start'];

if ($session_age > $_SESSION['session_timeout']) {
    // Session expired
    session_destroy();
    header("Location: ../index.php?error=session_expired");
    exit;
}

// Calculate remaining time
$remaining_time = $_SESSION['session_timeout'] - $session_age;
?> 