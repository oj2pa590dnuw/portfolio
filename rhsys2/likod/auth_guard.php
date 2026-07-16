<?php
// auth_guard.php - Ensures a user is logged in before rendering the page content.

// 1. Start the session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Check if the 'user_id' session variable is set
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit; // Stop execution to prevent the rest of the page from loading
}

// 💥 Inalis: Walang database connection o approval check dito. Tuloy-tuloy sa dashboard!

// If user_id is set, the script continues and the rest of the PHP/HTML page loads.
?>