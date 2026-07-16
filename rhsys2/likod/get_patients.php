<?php
// =========================================================
$debug_mode = false; 
// =========================================================

require 'session_utils.php'; // <--- NEW UTILITY
enforce_login(true);

// Set CORS headers for API access
header('Content-Type: application/json');

// PATH CORRECTION: Assuming db_con.php is in the same directory
require 'db_con.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);

// 3️⃣ Check connection
if ($conn->connect_error) {
    // We already sent headers, so it's safe to send a JSON body
    http_response_code(500); 
    // Note: We don't need to send headers again here.
    echo json_encode(["error" => "Database Connection Failed", "details" => $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8mb4"); // just to clean up symbols

// 4️⃣ Run the query
$sql = "SELECT * FROM patients ORDER BY fullName ASC";
$result = $conn->query($sql);

$patients_array = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patients_array[] = $row;
    }
}

// 5️⃣ Output — debug OR JSON
if ($debug_mode) {
    // This will break JSON parsing, but is fine for debugging in the browser
    echo "<h3>✅ Patients Found:</h3><pre>";
    print_r($patients_array);
    echo "</pre>";
} else {
    // Check if the array is empty and send an appropriate message
    if (empty($patients_array)) {
        echo json_encode(["message" => "No patient records found."]);
    } else {
        echo json_encode($patients_array);
    }
}

$conn->close();
?>