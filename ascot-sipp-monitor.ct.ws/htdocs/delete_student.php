<?php
require_once 'db.php';

if (!isset($_SESSION['advisor_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['student_id'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    die("Invalid CSRF token.");
}

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

$student_id = (int)$_POST['student_id'];
$return_hte = isset($_POST['hte_id']) ? (int)$_POST['hte_id'] : 0;
$allowed_redirects = ['index.php', 'edit_student.php', 'missing_requirements.php'];
$raw_redirect = isset($_POST['redirect']) ? $_POST['redirect'] : 'index.php';
// Strip query string for the whitelist check, then re-attach
$redirect_base = strtok($raw_redirect, '?');
if (!in_array($redirect_base, $allowed_redirects)) {
    $redirect = 'index.php';
} else {
    $redirect = $raw_redirect;
}
// Permission check
$stmt = mysqli_prepare($conn, "SELECT advisor_id FROM students WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student_advisor = mysqli_fetch_assoc($result)['advisor_id'];
mysqli_stmt_close($stmt);

$is_superadmin = ($_SESSION['role'] == 'superadmin');
if (!$is_superadmin && $student_advisor != $_SESSION['advisor_id']) {
    die("You are not allowed to delete this student.");
}

$stmt = mysqli_prepare($conn, "DELETE FROM students WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

regenerateCsrfToken();
header("Location: $redirect");
exit;