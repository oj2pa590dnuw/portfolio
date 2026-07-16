<?php
// likod/update_schedule.php
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

if (empty($data) || empty($data['id'])) {
   http_response_code(400);
   exit(json_encode(["success" => false, "message" => "No schedule ID or data provided for update."]));
}

// Sanitize and validate inputs
$schedule_id_db = $conn->real_escape_string($data['id']);
$schedule_date = $conn->real_escape_string($data['date'] ?? null);
$schedule_time = $conn->real_escape_string($data['time'] ?? null);
$title = $conn->real_escape_string($data['patient'] ?? null); // 'patient' maps to 'title'
$description = $conn->real_escape_string($data['notes'] ?? null); // 'notes' maps to 'description'
$schedule_type = $conn->real_escape_string($data['type'] ?? null);
$status = $conn->real_escape_string($data['status'] ?? null);

// NEW: Handle patient_id value
$patient_id_val = $data['patient_id'] ?? null;
$patient_id = is_null($patient_id_val) ? null : ($patient_id_val === '' ? '' : $conn->real_escape_string($patient_id_val));


// --- FIX: Server-side Past Date Validation for updates ---

// -----------------------------------------------------------


// Build the SET clauses for the SQL statement
$set_clauses = [];
$params = [];
$types = '';

if (!is_null($schedule_date)) {
   $set_clauses[] = "schedule_date = ?";
   $params[] = $schedule_date;
   $types .= 's';
}

if (!is_null($schedule_time)) {
   $set_clauses[] = "schedule_time = ?";
   $params[] = $schedule_time;
   $types .= 's';
}

// Handle title (patient name for untagged)
if (!is_null($title)) {
   $set_clauses[] = "title = ?";
   $params[] = $title;
   $types .= 's';
}

if (!is_null($description)) {
   $set_clauses[] = "description = ?";
   $params[] = $description;
   $types .= 's';
}

if (!is_null($schedule_type)) {
   $set_clauses[] = "schedule_type = ?";
   $params[] = $schedule_type;
   $types .= 's';
}

// START NEW LOGIC: Handle patient_id, allowing it to be set to NULL/empty string
if (!is_null($patient_id_val)) {
   if ($patient_id_val === '' || is_null($patient_id_val)) {
      // If client explicitly sent '' or null, set patient_id to NULL in DB
      $set_clauses[] = "patient_id = NULL";
   } else {
      // If client sent a value, update it
      $set_clauses[] = "patient_id = ?";
      $params[] = $patient_id; // already real_escape_string'd
      $types .= 's';
   }
}
// END NEW LOGIC

if (!is_null($status)) {
   $set_clauses[] = "status = ?";
   $params[] = $status;
   $types .= 's';
}

if (empty($set_clauses)) {
   http_response_code(400);
   exit(json_encode(["success" => false, "message" => "No fields provided for update."]));
}

// Append the schedule ID for the WHERE clause
$params[] = $schedule_id_db;
$types .= 's';

$sql = "UPDATE schedules SET " . implode(', ', $set_clauses) . " WHERE schedule_id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
   http_response_code(500);
   exit(json_encode(["success" => false, "message" => "SQL Prepare Failed: " . $conn->error]));
}

// NOTE: The ...$params syntax requires PHP 5.6+ and correctly unpacks the array elements.
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
   if ($stmt->affected_rows > 0) {
      // Assuming activity_logger.php and ActivityLogger class exist
      require_once 'activity_logger.php';
      $logger = new ActivityLogger();
      $logger->log($_SESSION['user_id'], 'SCHEDULE_UPDATED', 'Updated schedule', 'schedules', $schedule_id_db);

      http_response_code(200);
      echo json_encode(["success" => true, "message" => "Schedule updated successfully.", "id" => $schedule_id_db]);
   } else {
      // 200 OK, but no changes made (e.g., submitted the same data)
      http_response_code(200);
      echo json_encode(["success" => true, "message" => "No changes detected or schedule not found.", "id" => $schedule_id_db]);
   }
} else {
   http_response_code(500);
   echo json_encode(["success" => false, "message" => "Database execution error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>