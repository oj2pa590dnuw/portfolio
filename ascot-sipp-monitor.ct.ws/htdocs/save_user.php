<?php
require_once 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'superadmin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users_list.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    die("Invalid CSRF token.");
}

// Verify current user's password
if (!isset($_POST['superadmin_password'])) {
    die("Password confirmation required.");
}
$stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE advisor_id = ?");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['advisor_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
if (!$user || !password_verify($_POST['superadmin_password'], $user['password'])) {
    die("Invalid password.");
}

$advisor_id = isset($_POST['advisor_id']) ? (int)$_POST['advisor_id'] : 0;
$advisor_name = trim($_POST['advisor_name']);
$email = trim($_POST['email']);
$department = trim($_POST['department']);
$role = trim($_POST['role']);
$password = $_POST['password'] ?? '';

function isStrongPassword($pwd) {
    return strlen($pwd) >= 8 &&
           preg_match('/[A-Z]/', $pwd) &&
           preg_match('/[a-z]/', $pwd) &&
           preg_match('/[0-9]/', $pwd) &&
           preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>\/?]/', $pwd);
}

if ($advisor_id == 0) {
    if (empty($password)) die("Password is required.");
    if (!isStrongPassword($password)) die("Weak password.");

    $check_stmt = mysqli_prepare($conn, "SELECT advisor_id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        mysqli_stmt_close($check_stmt);
        die("Email already exists.");
    }
    mysqli_stmt_close($check_stmt);

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (advisor_name, email, department, password, role, approved, registration_date) VALUES (?, ?, ?, ?, ?, 1, NOW())");
    mysqli_stmt_bind_param($stmt, "sssss", $advisor_name, $email, $department, $hashed, $role);
} else {
    $check_stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE advisor_id = ?");
    mysqli_stmt_bind_param($check_stmt, "i", $advisor_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    $user_role = mysqli_fetch_assoc($result)['role'];
    mysqli_stmt_close($check_stmt);
    if ($user_role == 'superadmin') die("Superadmin account cannot be edited.");

    $email_check = mysqli_prepare($conn, "SELECT advisor_id FROM users WHERE email = ? AND advisor_id != ?");
    mysqli_stmt_bind_param($email_check, "si", $email, $advisor_id);
    mysqli_stmt_execute($email_check);
    mysqli_stmt_store_result($email_check);
    if (mysqli_stmt_num_rows($email_check) > 0) {
        mysqli_stmt_close($email_check);
        die("Email already exists.");
    }
    mysqli_stmt_close($email_check);

    if (!empty($password)) {
        if (!isStrongPassword($password)) die("Weak password.");
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET advisor_name=?, email=?, department=?, role=?, password=? WHERE advisor_id=?");
        mysqli_stmt_bind_param($stmt, "sssssi", $advisor_name, $email, $department, $role, $hashed, $advisor_id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET advisor_name=?, email=?, department=?, role=? WHERE advisor_id=?");
        mysqli_stmt_bind_param($stmt, "ssssi", $advisor_name, $email, $department, $role, $advisor_id);
    }
}

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    regenerateCsrfToken();
    header("Location: users_list.php");
    exit;
} else {
    error_log("MySQL Error in save_user.php: " . mysqli_error($conn));
    die("An error occurred while saving.");
}