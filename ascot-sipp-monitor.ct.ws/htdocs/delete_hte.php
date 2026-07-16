<?php
require_once 'db.php';

if (!isset($_SESSION['advisor_id']) || $_SESSION['role'] != 'superadmin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['hte_id'])) {
    header("Location: hte_list.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    die("Invalid CSRF token.");
}

// Superadmin password verification
if (!isset($_POST['superadmin_password'])) {
    die("Password confirmation required.");
}
$stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE advisor_id = ? AND role = 'superadmin'");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['advisor_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
if (!$user || !password_verify($_POST['superadmin_password'], $user['password'])) {
    die("Invalid superadmin password.");
}

$hte_id = (int)$_POST['hte_id'];

$stmt1 = mysqli_prepare($conn, "UPDATE students SET hte_id = NULL WHERE hte_id = ?");
mysqli_stmt_bind_param($stmt1, "i", $hte_id);
mysqli_stmt_execute($stmt1);
mysqli_stmt_close($stmt1);

$stmt2 = mysqli_prepare($conn, "DELETE FROM host_training_establishment WHERE hte_id = ?");
mysqli_stmt_bind_param($stmt2, "i", $hte_id);
mysqli_stmt_execute($stmt2);
mysqli_stmt_close($stmt2);

regenerateCsrfToken();
header("Location: hte_list.php");
exit;
?>