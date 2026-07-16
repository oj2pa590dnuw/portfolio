<?php
require 'session_utils.php'; // <--- NEW UTILITY
enforce_login(true);

// Set CORS headers for API access
header('Content-Type: application/json');

// PATH CORRECTION: Assuming db_con.php is in the same directory
require 'db_con.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
   http_response_code(500);
   // Send a detailed error message in JSON format
   exit(json_encode([
      "success" => false,
      "message" => "Database Connection Error: " . $conn->connect_error . ". Check XAMPP/WAMPP services."
   ]));
}

try {
   // FIX: Use schedule_id AS id to match your DB column name (schedules.sql) 
   // but provide the 'id' field expected by the JavaScript frontend.
   $sql = "SELECT 
               schedule_id AS id, 
               schedule_date AS date, 
               schedule_time AS time, 
               title AS patient, 
               description AS notes, 
               schedule_type AS type,
               status,
               patient_id  /* <--- FIX: Added patient_id */
           FROM schedules 
           ORDER BY schedule_date, schedule_time";

   $result = $conn->query($sql);
   $schedules = [];

   if ($result === false) {
      throw new Exception("SQL Query Failed: " . $conn->error);
   }

   if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
         $schedules[] = $row;
      }
   }

   http_response_code(200);
   echo json_encode(["success" => true, "data" => $schedules]);

} catch (Exception $e) {
   http_response_code(500);
   echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}

$conn->close();
?>