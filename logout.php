<?php
session_start();

// Clear all session variables to remove user data
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect the user back to the login page
header("Location: login.php");
exit;
?>