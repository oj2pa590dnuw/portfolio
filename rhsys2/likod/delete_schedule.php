<?php
// delete_schedule.php
require 'session_utils.php'; // <--- NEW UTILITY
enforce_login(true);

// Set CORS headers for API access
header('Content-Type: application/json');

// PATH CORRECTION: Assuming db_con.php is in the same directory
require 'db_con.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
   http_response_code(500);
   exit(json_encode(["success" => false, "message" => "Database Connection Error: " . $conn->connect_error]));
}

// Read raw JSON data from the request body
$data = json_decode(file_get_contents("php://input"), true);

if (empty($data) || empty($data['id'])) {
   $schedule_id = $_GET['id'] ?? null;
   if (empty($schedule_id)) {
      http_response_code(400);
      exit(json_encode(["success" => false, "message" => "No schedule ID provided for deletion."]));
   }
} else {
   // FIX: schedule_id_db is the primary key in the DB, though it comes from frontend as 'id'
   $schedule_id = $conn->real_escape_string($data['id']);
}

try {
   // Prepare SQL statement for deletion
   // FIX: Use schedule_id in the WHERE clause
   $sql = "DELETE FROM schedules WHERE schedule_id = ?";

   $stmt = $conn->prepare($sql);
   $stmt->bind_param("s", $schedule_id);

   if ($stmt->execute()) {
      if ($stmt->affected_rows > 0) {
         require_once 'activity_logger.php';
    $logger = new ActivityLogger();
    $logger->log($_SESSION['user_id'], 'SCHEDULE_DELETED', 'Deleted schedule', 'schedules', $schedule_id);
    
    http_response_code(200);
    echo json_encode(["success" => true, "message" => "Schedule deleted successfully.", "id" => $schedule_id]);
      } else {
         http_response_code(404); // Not Found
         echo json_encode(["success" => false, "message" => "Schedule not found.", "id" => $schedule_id]);
      }
   } else {
      http_response_code(500);
      throw new Exception("Database execution error: " . $stmt->error);
   }

   $stmt->close();

} catch (Exception $e) {
   http_response_code(500);
   echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
?>