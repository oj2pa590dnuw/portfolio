<?php
ob_start(); // Ensures headers are sent even if there's pre-output garbage

// 💥 CRITICAL FIX: Handle CORS Preflight (OPTIONS Request)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // This allows the browser to send the subsequent GET request
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Allow all needed methods
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400'); // Cache preflight response for 24 hours
    http_response_code(204); // No Content is the standard response for OPTIONS success
    exit;
}
// End of CORS Preflight Fix

require 'session_utils.php';
enforce_login(true);

// Set CORS headers for API access (for the actual GET request)
header('Access-Control-Allow-Origin: *');

header('Content-Type: application/json');
require 'db_con.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

$counts_data = [
    "totalPatients"    => 0,
    "criticalCount"    => 0,
    "pregnantCount"    => 0,
    "warningFlagCount" => 0,
    "seniorCount"      => 0, // Age 60+
    "pediatricCount"   => 0, // Age below 18
    "medicationCount"  => 0
];

// total patients
$sql_total = "SELECT COUNT(*) AS count FROM patients";
if ($res = $conn->query($sql_total)) {
    $row = $res->fetch_assoc();
    $counts_data["totalPatients"] = $row["count"] ?? 0;
}

// helper for flag counts
function count_flag($conn, $col) {
    $sql = "SELECT COUNT(*) AS count FROM patients WHERE $col = 1";
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row["count"];
    }
    return 0;
}

// New helper for age-based counts
function count_by_age($conn, $condition) {
    $sql = "SELECT COUNT(*) AS count FROM patients WHERE $condition";
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return (int)$row["count"];
    }
    return 0;
}

$counts_data["criticalCount"]    = count_flag($conn, "isCritical");
$counts_data["pregnantCount"]    = count_flag($conn, "isPregnant");
$counts_data["warningFlagCount"] = count_flag($conn, "isWarningFlag");
$counts_data["medicationCount"]  = count_flag($conn, "needsMedication");

// New age-based counts
$counts_data["seniorCount"]    = count_by_age($conn, "age >= 60");
$counts_data["pediatricCount"] = count_by_age($conn, "age < 18");

$conn->close();

http_response_code(200);
echo json_encode($counts_data);