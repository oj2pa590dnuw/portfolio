<?php
require_once 'db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Verify user is logged in
if (!isset($_SESSION['advisor_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$advisor_id = (int)$_SESSION['advisor_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Input validation
if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit;
}

if (!isStrongPassword($new_password)) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters and contain uppercase, lowercase, number, and special character.']);
    exit;
}

// Fetch current password hash using prepared statement
$stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE advisor_id = ?");
mysqli_stmt_bind_param($stmt, "i", $advisor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

if (!password_verify($current_password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

// Update password
$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$update_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE advisor_id = ?");
mysqli_stmt_bind_param($update_stmt, "si", $hashed, $advisor_id);

if (mysqli_stmt_execute($update_stmt)) {
    mysqli_stmt_close($update_stmt);
    // Regenerate CSRF token after successful action
    regenerateCsrfToken();
    // Regenerate session ID to prevent session fixation after privilege change
    session_regenerate_id(true);
    echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
} else {
    error_log("Password change failed for user $advisor_id: " . mysqli_error($conn));
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
exit;

/**
 * Validate password strength server‑side
 */
function isStrongPassword($pwd) {
    return strlen($pwd) >= 8 &&
           preg_match('/[A-Z]/', $pwd) &&
           preg_match('/[a-z]/', $pwd) &&
           preg_match('/[0-9]/', $pwd) &&
           preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>\/?]/', $pwd);
}
