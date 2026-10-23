<?php
/**
 * Campus Canteen Management System - Logout
 */

session_start();
require_once 'includes/functions.php';

// Log activity before logout
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'Logout', 'User logged out');
}

// Clear session
session_unset();
session_destroy();

// Redirect to login
header("Location: login.php");
exit();
?>
