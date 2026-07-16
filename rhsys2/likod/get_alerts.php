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

// PATH CORRECTION: Assuming db_con.php is in the same directory
require 'db_con.php'; 

// 1. Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
   http_response_code(500);
   die(json_encode(array("message" => "Connection failed: " . $conn->connect_error)));
}

// 2. Build the SQL query
// FIX: Alias existing columns to match the JSON output expected by the front-end (dashboard.php)
$sql = "SELECT 
            id, 
            fullName, 
            location, 
            warning AS title,               /* Maps existing 'warning' column to required 'title' */
            clinicalNotes AS medication,    /* Maps existing 'clinicalNotes' to required 'medication' */
            isCritical, 
            isWarningFlag, 
            needsMedication                 /* New column from final SQL fix */
        FROM 
            patients 
        WHERE 
            isCritical = '1' 
            OR isWarningFlag = '1' 
            OR needsMedication = '1'        /* Check the new flag */
        ORDER BY 
            isCritical DESC, fullName ASC";

$result = $conn->query($sql);

$alerts_array = array();

// 3. Process the results
if ($result->num_rows > 0) {
   while ($row = $result->fetch_assoc()) {
      $alerts_array[] = $row;
   }

   http_response_code(200);
   echo json_encode($alerts_array);
} else {
   http_response_code(200);
   echo json_encode(array("message" => "No current alerts."));
}

$conn->close();

function return_json_error($code, $message, $conn) {
    if ($conn) {
        $conn->close();
    }
    http_response_code($code);
    die(json_encode(array("success" => false, "message" => $message)));
}