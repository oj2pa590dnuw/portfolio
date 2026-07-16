<?php
session_start();
require_once 'db_con.php'; // db_con.php is in the same folder

// 1. Collect POST data
$first = $_POST['first_name'] ?? '';
$last = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$password_input = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

// Basic input validation
if (empty($first) || empty($last) || empty($email) || empty($password_input) || empty($role)) {
    // 💥 CHANGED: Redirect back to the registration form with a URL message parameter.
    $msg = '❌ Missing required registration fields! Please check all inputs.';
    echo "<script>window.location.href='../registerform.php?msg_type=error&msg=" . urlencode($msg) . "';</script>";
    exit;
}

// =========================================================
// 💥 NEW SECURITY STEP: CHECK FOR DUPLICATE EMAIL
// =========================================================
$check_sql = "SELECT id FROM users WHERE email = ?";

if ($check_stmt = $conn->prepare($check_sql)) {
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result(); // Store result to check row count

    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();
        $conn->close();

        // 💥 CHANGED: Redirect to login.php with URL message parameter for duplicate email error!
        $msg = "🚨 The email address '$email' is already registered!";
        echo "<script>window.location.href='../login.php?msg_type=warning&msg=" . urlencode($msg) . "';</script>";
        exit;
    }
    $check_stmt->close(); // Close statement if no duplicates found
} else {
    // Critical error handling for the check query itself
    $conn->close();
    // 💥 CHANGED: Redirect to login.php with URL message parameter for critical error!
    $msg = '❌ Critical Error during email check: SQL Prepare Failed.';
    echo "<script>window.location.href='../login.php?msg_type=error&msg=" . urlencode($msg) . "';</script>";
    exit;
}
// =========================================================

// Hash the password for secure storage
$hashed_password = password_hash($password_input, PASSWORD_DEFAULT);

// Map the single role field to the boolean columns
$is_bns = $role === 'bns' ? 1 : 0;
$is_bhw = $role === 'bhw' ? 1 : 0;
$is_midwife = $role === 'midwife' ? 1 : 0; // The new user *could* be a midwife

// 💥 HERE ARE THE SECURITY DEFAULTS
$is_admin = 0; // 💥 NEW: A new user is NEVER an admin by default
$approved_by_admin = 0; // 💥 NEW: A new user is NEVER approved by default

// 2. SQL: ADDED 'is_admin' and 'approved_by_admin' columns
// Total of 9 columns to insert
$sql = "INSERT INTO users (first_name, last_name, email, password, is_bns, is_bhw, is_midwife, is_admin, approved_by_admin)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 9 parameters now!

if ($stmt = $conn->prepare($sql)) {

    // 💥 CHANGED: Added 'i' for 'is_admin' and 'approved_by_admin'. 
    // Total: ssssiiiii (9 parameters)
    $stmt->bind_param(
        "ssssiiiii",
        $first,
        $last,
        $email,
        $hashed_password,
        $is_bns,
        $is_bhw,
        $is_midwife,
        $is_admin,            // This is 0
        $approved_by_admin  // This is 0
    );

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();

        // 3. 💬 CHANGED: Redirect to login.php with URL message parameter for success!
        $msg = '✅ Registration successful! Please wait for admin approval before logging in.';
        echo "<script>window.location.href='../login.php?msg_type=success&msg=" . urlencode($msg) . "';</script>";
        exit;

    } else {
        $error_message = $stmt->error;
        $stmt->close();
        $conn->close();

        // 🚨 FAIL-SAFE 2: Show the database error message!
        $msg = "❌ Registration failed! Database Error: " . $error_message;
        // 💥 CHANGED: Redirect to login.php with URL message parameter for database error!
        echo "<script>window.location.href='../login.php?msg_type=error&msg=" . urlencode($msg) . "';</script>";
        exit;
    }
} else {
    $conn->close();
    // 🚨 FAIL-SAFE 3: Show connection/prepare error
    $msg = '❌ Critical Error: SQL Prepare Failed. Check db_con.php path.';
    // 💥 CHANGED: Redirect to login.php with URL message parameter for prepare error!
    echo "<script>window.location.href='../login.php?msg_type=error&msg=" . urlencode($msg) . "';</script>";
    exit;
}
?>