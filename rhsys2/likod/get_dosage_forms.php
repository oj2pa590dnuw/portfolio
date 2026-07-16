<?php
require 'session_utils.php'; // <--- NEW UTILITY
enforce_login(true);

// Set CORS headers for API access
header('Content-Type: application/json');

// PATH CORRECTION: Assuming db_con.php is in the same directory
require 'db_con.php';

// 1. Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
   http_response_code(500);
   // 💥 FIXED: Returning 'success: false' and a 'message'
   exit(json_encode(['success' => false, 'message' => 'Database Connection Error: ' . $conn->connect_error]));
}

try {
   // FIX: Switched to MySQLi syntax (using $conn->query())
   $sql = "SELECT form_name FROM dosage_forms ORDER BY form_name ASC";
   $result = $conn->query($sql);

   if ($result === false) {
      throw new Exception("SQL Query Failed: " . $conn->error);
   }

   $forms = [];
   // FIX: Switched to MySQLi fetch_assoc()
   while ($row = $result->fetch_assoc()) {
      // Collect only the name string
      $forms[] = $row['form_name'];
   }

   http_response_code(200);
   // 💥 FIXED: Now returns { "success": true, "data": [...] }
   echo json_encode(['success' => true, 'data' => $forms]);

} catch (Exception $e) {
   // Fail safe: return a general error
   http_response_code(500);
   // 💥 FIXED: Returning 'success: false' and a 'message'
   echo json_encode(['success' => false, 'message' => 'Database query failed: Could not load dosage forms. Details: ' . $e->getMessage()]);
}

$conn->close();
?>