<?php
// Secure session configuration (must be before session_start)
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Strict');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

$timeout_duration = 1800; // 30 minutes

if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] >= 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

function checkSessionTimeout() {
    global $timeout_duration;
    if (isset($_SESSION['advisor_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
            session_unset();
            session_destroy();
            header("Location: login.php?timeout=1");
            exit;
        }
        $_SESSION['last_activity'] = time();
    }
}
checkSessionTimeout();

// Updated InfinityFree MySQL connection
$conn = mysqli_connect(
    "sql104.infinityfree.com",   // hostname
    "if0_40210966",              // username
    "gwavajuice2025",            // password
    "if0_40210966_system2"       // database
);

if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("A database error occurred. Please try again later.");
}

mysqli_set_charset($conn, "utf8mb4");

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token']) || (time() - ($_SESSION['csrf_token_time'] ?? 0) > 3600)) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    if (time() - ($_SESSION['csrf_token_time'] ?? 0) > 3600) {
        unset($_SESSION['csrf_token']);
        return false;
    }
    return true;
}

function regenerateCsrfToken() {
    unset($_SESSION['csrf_token']);
    generateCsrfToken();
}

function logActivity($advisor_id, $action, $details = '') {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = mysqli_prepare($conn, "INSERT INTO activity_logs (advisor_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $advisor_id, $action, $details, $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>