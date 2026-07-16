<?php
// likod/get_completed_schedule.php
require 'session_utils.php';
enforce_login(true);

header('Content-Type: application/json');
require 'db_con.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
   http_response_code(500);
   exit(json_encode([
      "success" => false,
      "message" => "Database Connection Error: " . $conn->connect_error
   ]));
}

try {
   // Fetch all completed schedules
   $sql = "SELECT 
               s.schedule_id AS id, 
               s.schedule_date AS date, 
               s.schedule_time AS time, 
               COALESCE(p.name, s.title) AS patient, 
               s.description AS notes, 
               s.schedule_type AS type,
               s.status,
               s.patient_id
           FROM schedules s
           LEFT JOIN patients p ON s.patient_id = p.patient_id
           WHERE s.status = 'Completed'
           ORDER BY s.schedule_date DESC, s.schedule_time DESC"; // Most recent completed first

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