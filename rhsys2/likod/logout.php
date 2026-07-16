<?php
session_start();
require_once 'activity_logger.php';
    $logger = new ActivityLogger();
    $logger->log($_SESSION['user_id'] ?? 0, 'LOGOUT', 'User logged out successfully');
session_unset(); // remove all session variables
session_destroy(); // destroy the session
header("Location: ../login.php");
exit;
?>
