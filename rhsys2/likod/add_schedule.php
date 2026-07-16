<?php
// likod/add_schedule.php
require 'session_utils.php';
enforce_login(true);

header('Content-Type: application/json');

require 'db_con.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
   http_response_code(500);
   exit(json_encode(["success" => false, "message" => "Database Connection Error: " . $conn->connect_error]));
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data)) {
   http_response_code(400);
   exit(json_encode(["success" => false, "message" => "No data provided."]));
}

// Sanitize and validate inputs
$schedule_date = $conn->real_escape_string($data['schedule_date'] ?? '');
$schedule_time = $conn->real_escape_string($data['schedule_time'] ?? '');
$title = $conn->real_escape_string($data['title'] ?? '');
$description = $conn->real_escape_string($data['description'] ?? '');
$schedule_type = $conn->real_escape_string($data['schedule_type'] ?? 'Appointment');
$patient_id = $conn->real_escape_string($data['patient_id'] ?? null); // NEW: Get patient_id

$status = 'Pending';

if (empty($schedule_date) || empty($schedule_time) || empty($schedule_type)) {
   http_response_code(400);
   exit(json_encode(["success" => false, "message" => "Missing required fields (Date, Time, Type)."]));
}

// --- FIX: Server-side Past Date Validation ---
$today = date('Y-m-d');
if ($schedule_date < $today) {
   http_response_code(400);
   exit(json_encode(["success" => false, "message" => "Cannot add schedule to a date that has already passed. Please select a future or current date."]));
}
// ---------------------------------------------


// Add check for title if patient_id is not set
if (empty($patient_id) && empty($title)) {
   http_response_code(400);
   exit(json_encode(["success" => false, "message" => "Missing required fields: Patient Name/Title is required if no Patient ID is selected."]));
}


try {
   // Prepare SQL statement for insertion. Added patient_id column.
   $sql = "INSERT INTO schedules (schedule_date, schedule_time, title, description, schedule_type, status, patient_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

   $stmt = $conn->prepare($sql);

   // Bind parameters (s = string). Added patient_id.
   $stmt->bind_param(
      "sssssss",
      $schedule_date,
      $schedule_time,
      $title,
      $description,
      $schedule_type,
      $status,
      $patient_id
   );

   if ($stmt->execute()) {
      // Assuming activity_logger.php and ActivityLogger class exist
      require_once 'activity_logger.php';
      $logger = new ActivityLogger();
      $logger->log($_SESSION['user_id'], 'SCHEDULE_ADDED', 'Added new schedule', 'schedules', $conn->insert_id);

      http_response_code(201);
      echo json_encode(["success" => true, "message" => "Schedule added successfully.", "id" => $conn->insert_id]);
   } else {
      http_response_code(500);
      throw new Exception("Database execution error: " . $stmt->error);
   }

   $stmt->close();
} catch (Exception $e) {
   http_response_code(500);
   echo json_encode(["success" => false, "message" => "Server Error: " . $e->getMessage()]);
}

$conn->close();
?>