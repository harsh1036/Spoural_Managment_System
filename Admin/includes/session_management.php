<?php
// Start the session at the very beginning
session_start();

// Define your desired session timeout duration (e.g., 30 minutes = 1800 seconds)
$session_timeout_duration = 1800; // 30 minutes

// If the session_start_timestamp is not set, set it now.
// This ensures it's only set once per session, typically at login.
if (!isset($_SESSION['session_start_timestamp'])) {
    $_SESSION['session_start_timestamp'] = time();
}

// Store the timeout duration in the session (optional, but good for consistency)
if (!isset($_SESSION['session_timeout'])) {
    $_SESSION['session_timeout'] = $session_timeout_duration;
}

// Calculate the current session age
$current_time = time();
$session_age = $current_time - $_SESSION['session_start_timestamp'];

// Check if the session has expired
if ($session_age > $_SESSION['session_timeout']) {
    // Session expired
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    // Redirect to login page and exit to prevent further code execution
    header("Location: ../index.php?error=session_expired");
    exit;
}

// Calculate the remaining time for display in the client-side
$remaining_time = $_SESSION['session_timeout'] - $session_age;

// If you want to extend the session (e.g., keep it alive as long as user is active),
// you would update session_start_timestamp here. BUT you specifically asked NOT to do this.
// So, the above logic keeps a fixed timeout from initial login.

// Important: If you want to automatically log out after 30 minutes of INACTIVITY,
// you would update the timestamp on every request.
// For example: $_SESSION['last_activity'] = time();
// And then check time() - $_SESSION['last_activity'] > $session_timeout_duration

// Since you want a fixed 30 minutes from LOGIN, the current setup is correct.
?>