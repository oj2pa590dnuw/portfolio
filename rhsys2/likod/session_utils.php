<?php
// =======================================================
// GLOBAL PHP CONFIG: SCRIPT TIMEOUT
// Sets a 60-second limit for all requests. 
// This is crucial for long operations like creating or restoring large backups.
set_time_limit(60);
// Also set max input time for large POST data uploads (like file restores)
ini_set('max_input_time', '60');

// 🚨 GRACEFUL TIMEOUT HANDLING
// This function runs when the script finishes or FAILS (like on a timeout),
// allowing us to log the fatal error instead of just letting it vanish.
register_shutdown_function(function () {
    $error = error_get_last();
    // Check if the script ended due to a fatal error (E_ERROR)
    if ($error !== null && $error['type'] === E_ERROR) {
        // Check specifically for a timeout message
        if (strpos($error['message'], 'maximum execution time') !== false) {
            // Log the timeout to your server's PHP error log. This is VITAL for debugging.
            error_log("FATAL TIMEOUT: Script execution exceeded 60 seconds. URI: " . ($_SERVER['REQUEST_URI'] ?? 'UNKNOWN'));

            // You could add logic here to write a simplified error to the client, 
            // but logging the issue is the most important part for a quick fix.
        }
    }
});
// =======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =======================================================
// 💥 NEW: RATE LIMITING CONFIG
// =======================================================
define('RATE_LIMIT_DURATION_SECONDS', 1); // Time window to check requests (e.g., 1 second)
define('RATE_LIMIT_MAX_REQUESTS', 5);     // Max number of requests allowed in that window

/**
 * 💥 NEW FUNCTION: Enforces a simple per-session rate limit.
 * This prevents users from spamming the server with requests.
 */
function enforce_rate_limit()
{
    $now = time();
    $session_key = 'rate_limit_requests';

    // Initialize the tracking array if it doesn't exist
    if (!isset($_SESSION[$session_key]) || !is_array($_SESSION[$session_key])) {
        $_SESSION[$session_key] = [];
    }

    // 1. Clean up old requests that are outside the time window
    $_SESSION[$session_key] = array_filter($_SESSION[$session_key], function ($timestamp) use ($now) {
        return $timestamp > ($now - RATE_LIMIT_DURATION_SECONDS);
    });

    // 2. Check if the current request count exceeds the limit
    if (count($_SESSION[$session_key]) >= RATE_LIMIT_MAX_REQUESTS) {
        // Log the denial
        error_log("RATE LIMIT DENIED for User ID: " . ($_SESSION['user_id'] ?? 'N/A') . ". Too many requests in " . RATE_LIMIT_DURATION_SECONDS . "s.");

        // Block the request and send a 429 Too Many Requests status
        http_response_code(429);
        header('Retry-After: ' . RATE_LIMIT_DURATION_SECONDS); // Tell the client when to try again
        echo json_encode(["error" => "Rate limit exceeded. Too many requests. Please wait a moment."]);
        exit;
    }

    // 3. Record the current request timestamp
    $_SESSION[$session_key][] = $now;
}
// =======================================================


/**
 * Checks if a user is logged in. If not, redirects to login page.
 * @param bool $api Set to true if this is an API endpoint (sends 401 JSON error).
 */
function enforce_login($api = false)
{
    // 💥 NEW: Enforce rate limit on every call to enforce_login (or whatever the main entry point is)
    enforce_rate_limit();

    if (!isset($_SESSION['user_id'])) {
        if ($api) {
            http_response_code(401); // Unauthorized
            echo json_encode(["error" => "Not logged in"]);
            exit;
        } else {
            // Your standard redirect for regular pages
            header("Location: ../login.html");
            exit;
        }
    }
}

/**
 * 💥 NEW FUNCTION: Enforces Role-Based Access Control (RBAC).
 * Only allows access if the user has at least one of the specified roles.
 *
 * @param array $allowed_roles Array of session keys to check (e.g., ['is_midwife', 'is_admin']).
 * @param bool $api Set to true if this is an API endpoint (sends 403 JSON error).
 */
function enforce_role(array $allowed_roles, $api = false)
{
    // 1. You MUST be logged in first! (This call now includes the rate limit check)
    enforce_login($api);

    $has_access = false;

    // 2. Check if the user has AT LEAST ONE of the allowed roles
    foreach ($allowed_roles as $role) {
        // Check if the role is set in the session AND is true
        if (isset($_SESSION[$role]) && $_SESSION[$role] === true) {
            $has_access = true;
            break; // Found one! We can stop checking.
        }
    }

    // 3. If no required role was found, deny access.
    if (!$has_access) {
        if ($api) {
            http_response_code(403); // Forbidden
            echo json_encode(["error" => "Access denied. Insufficient permissions."]);
            exit;
        } else {
            // Redirect them to a permission denied page, or just the dashboard
            echo "<script>alert('❌ Access Denied: You do not have permission to view this page.'); window.location.href='../dashboard.php';</script>";
            exit;
        }
    }
}
?>