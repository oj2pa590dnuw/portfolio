<?php
session_start();
// 💥 PATH CHECK: Correct. db_con.php is in the same folder (likod/)
require_once 'db_con.php';

$email = $_POST['email'] ?? '';
$password_input = $_POST['password'] ?? '';

if (empty($email) || empty($password_input)) {
    // 💥 CHANGED: Replaced alert() with URL redirect
    $msg = '❌ Email and password are required for login!';
    echo "<script>window.location.href='../login.php?msg_type=error&msg=" . urlencode($msg) . "';</script>";
    exit;
}

// 🚨 NEW SECURITY CHECK: Password Minimum Length
if (strlen($password_input) < 8) {
    // 💥 CHANGED: Replaced alert() with URL redirect
    $msg = '❌ Invalid login attempt: The password provided is too short.';
    echo "<script>window.location.href='../login.php?msg_type=error&msg=" . urlencode($msg) . "';</script>";
    exit;
}
// 🚨 END SECURITY CHECK

// 💥 FIXED: Selecting ALL role columns + approval column, INCLUDING is_admin
$sql = "SELECT id, first_name, password, is_bns, is_bhw, is_midwife, is_admin, approved_by_admin 
        FROM users WHERE email = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password_input, $user['password'])) {

            // 💥 NEW SECURITY CHECK: Check for admin approval!
            if ($user['approved_by_admin'] != 1) {
                $conn->close();
                // 💥 CHANGED: Replaced alert() with URL redirect (Using 'warning' type)
                $msg = '❌ Account not yet approved by an Administrator. Please wait.';
                echo "<script>window.location.href='../login.php?msg_type=warning&msg=" . urlencode($msg) . "';</script>";
                exit;
            }

            // If we reach here, they are approved and the password is correct.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];

            // 💥 ROLE SETUP: Storing all roles in the session as booleans
            $_SESSION['is_bns'] = (bool) $user['is_bns'];
            $_SESSION['is_bhw'] = (bool) $user['is_bhw'];
            $_SESSION['is_midwife'] = (bool) $user['is_midwife'];
            $_SESSION['is_admin'] = (bool) $user['is_admin'];

            // 💥 FIXED: Correct path for activity logger and use correct variable
            if (file_exists('activity_logger.php')) {
                require_once 'activity_logger.php';
                $logger = new ActivityLogger();
                $logger->log($user['id'], 'LOGIN', 'User logged in successfully');
            }

            // --- 💥 COOKIE SAVE FIX HERE 💥 ---
            // Close the database connection first
            $conn->close();

            // Use PHP's echo to output a JavaScript block.
            // This JS will run in the browser and save the cookie before redirecting.
            echo "<script>";
            // 1. Find the PHPSESSID cookie string
            echo "const cookieString = document.cookie.split('; ').find(row => row.startsWith('PHPSESSID='));";
            // 2. Call the native bridge method we added in WebAppInterface.java
            echo "if (typeof Android !== 'undefined' && Android.savePHPSessionCookie && cookieString) {";
            echo "    Android.savePHPSessionCookie(cookieString);";
            echo "}";
            // 3. Use JavaScript for the redirect, which is safer after outputting a script
            echo "window.location.href = '../dashboard.php';";
            echo "</script>";

            exit; // Exit the PHP script after sending the output
        } else {
            $conn->close();
            // 💥 CHANGED: Replaced alert() with URL redirect
            $msg = '❌ Wrong password! Please check your credentials.';
            echo "<script>window.location.href='../login.php?msg_type=error&msg=" . urlencode($msg) . "';</script>";
            exit;
        }
    } else {
        $conn->close();
        // 💥 CHANGED: Replaced alert() with URL redirect
        $msg = '❌ No user found with that email! Check the address or register.';
        echo "<script>window.location.href='../login.php?msg_type=error&msg=" . urlencode($msg) . "';</script>";
        exit;
    }
} else {
    $conn->close();
    // 💥 CHANGED: Replaced alert() with URL redirect (and kept addslashes for safety)
    $msg = '❌ Database Error: ' . addslashes($conn->error);
    echo "<script>window.location.href='../login.php?msg_type=error&msg=" . urlencode($msg) . "';</script>";
    exit;
}
?>