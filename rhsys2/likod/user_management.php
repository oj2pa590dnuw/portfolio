<?php
// Load utility functions and database connection
require 'session_utils.php'; 
require 'db_con.php'; 

// 💥 STEP 1: SECURITY CHECK
// Only admins or midwives can approve/revoke/delete users.
enforce_role(['is_midwife', 'is_admin']);

// 💥 STEP 2: VALIDATE INPUT
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_id'], $_POST['action'])) {
    header('Location: ../users_page.php?status=error&message=' . urlencode('Invalid request method or missing parameters.'));
    exit();
}

$user_id = $_POST['user_id'];
$action = $_POST['action'];

// CRITICAL SAFETY CHECK: Prevent user from managing (revoking or deleting) their own account
if (in_array($action, ['revoke', 'delete'])) {
    // Assuming session_utils.php starts the session and $_SESSION['user_id'] is available.
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
        header('Location: ../users_page.php?status=error&message=' . urlencode('You cannot manage (revoke or delete) your own account.'));
        exit();
    }
}

// 💥 STEP 3: DETERMINE ACTION AND PREPARE QUERY
$sql = '';
$message_status = '';

if ($action === 'approve') {
    $sql = "UPDATE users SET approved_by_admin = 1 WHERE id = ?";
    $message_status = 'User approved successfully.';
} elseif ($action === 'revoke') {
    $sql = "UPDATE users SET approved_by_admin = 0 WHERE id = ?";
    $message_status = 'User approval revoked successfully.';
} elseif ($action === 'delete') {
    // New DELETE action
    // Note: The database is set up with CASCADE or SET NULL for foreign keys 
    // (e.g., in patients, inventory_batches, patient_visits), so deleting a user 
    // here should clean up dependencies gracefully based on your schema.
    $sql = "DELETE FROM users WHERE id = ?";
    $message_status = 'User account permanently deleted.';
} else {
    header('Location: ../users_page.php?status=error&message=' . urlencode('Invalid action specified.'));
    exit();
}

// 💥 STEP 4: PERFORM DATABASE UPDATE/DELETE USING PREPARED STATEMENT
// We use a prepared statement to prevent SQL injection.
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    header('Location: ../users_page.php?status=error&message=' . urlencode('Database error during preparation: ' . $conn->error));
    exit();
}

// Bind parameters: 'i' for integer (user_id)
$stmt->bind_param("i", $user_id);

// Execute the statement
if ($stmt->execute()) {
    require_once 'activity_logger.php';
    $logger = new ActivityLogger();
    
    if ($action === 'approve') {
        $logger->log($_SESSION['user_id'], 'USER_APPROVED', 'Approved user account', 'users', $user_id);
    } elseif ($action === 'revoke') {
        $logger->log($_SESSION['user_id'], 'USER_REVOKED', 'Revoked user approval', 'users', $user_id);
    } elseif ($action === 'delete') {
        $logger->log($_SESSION['user_id'], 'USER_DELETED', 'Deleted user account', 'users', $user_id);
    }
    
    // Success: Redirect back to the users page with a success message
    header('Location: ../users_page.php?status=success&message=' . urlencode($message_status));
    exit();
} else {
    // Failure: Redirect back with an error message
    header('Location: ../users_page.php?status=error&message=' . urlencode('Failed to perform user action: ' . $stmt->error));
    exit();
}

// Close the statement and connection
$stmt->close();
if (isset($conn)) {
    $conn->close();
}

?>