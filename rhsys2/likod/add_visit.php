<?php
require 'session_utils.php'; // <--- NEW UTILITY
enforce_login(true);

// Set CORS headers for API access
header('Content-Type: application/json');

// PATH CORRECTION: Assuming db_con.php is in the same directory
require 'db_con.php'; 

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
   http_response_code(500);
   die(json_encode(array("success" => false, "message" => "Database connection failed: " . $conn->connect_error)));
}

// 1. Get the JSON data sent from the JavaScript file
$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// 2. Check if required data is present
if (!isset($data['patient_id']) || empty($data['patient_id'])) {
   http_response_code(400); // Bad Request
   die(json_encode(array("success" => false, "message" => "Patient ID is required for a visit record.")));
}

// 3. Extract data from the request
$patientId = $data['patient_id'];
// Use current date if visit_date is not provided
$visitDate = isset($data['visit_date']) && !empty($data['visit_date']) ? $data['visit_date'] : date('Y-m-d');
$chiefComplaint = isset($data['chief_complaint']) ? $data['chief_complaint'] : '';
$bloodPressure = isset($data['blood_pressure']) ? $data['blood_pressure'] : '';
$heartRate = isset($data['heart_rate']) ? $data['heart_rate'] : '';
$temperature = isset($data['temperature']) ? $data['temperature'] : '';
$clinicalNotes = isset($data['clinical_notes']) ? $data['clinical_notes'] : '';
$proceduresDone = isset($data['procedures_done']) ? $data['procedures_done'] : '';

// 4. Define the SQL INSERT query for the new `patient_visits` table
$sql = "INSERT INTO patient_visits (
    patient_id, 
    visit_date, 
    chief_complaint, 
    blood_pressure, 
    heart_rate, 
    temperature, 
    clinical_notes,
    procedures_done
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

// 5. Prepare the statement
$stmt = $conn->prepare($sql);

// Bind the parameters (8 total variables: s s s s s s s s)
$stmt->bind_param(
   "ssssssss",
   $patientId,
   $visitDate,
   $chiefComplaint,
   $bloodPressure,
   $heartRate,
   $temperature,
   $clinicalNotes,
   $proceduresDone
);

// 6. Execute the insertion
if ($stmt->execute()) {
   $new_id = $conn->insert_id; // Get the ID of the new visit record

   // Optional: Update the patient's main record with the latest checkup info
   // This keeps the patient_list.html and dashboard summaries up to date.
   $update_patient_sql = "UPDATE patients SET 
        lastCheckup = ?, 
        title = ?,
        bloodPressure = ?,
        heartRate = ?,
        temperature = ?,
        clinicalNotes = ?
        WHERE id = ?";

   $update_stmt = $conn->prepare($update_patient_sql);
   $update_stmt->bind_param(
      "sssssss",
      $visitDate,
      $chiefComplaint,
      $bloodPressure,
      $heartRate,
      $temperature,
      $clinicalNotes,
      $patientId
   );
   $update_stmt->execute();
   $update_stmt->close();
   // End Optional Update

   http_response_code(201); // Created
   echo json_encode(array("success" => true, "message" => "Visit record added successfully.", "visit_id" => $new_id));
} else {
   http_response_code(500); // Server Error
   echo json_encode(array("success" => false, "message" => "Failed to add visit record: " . $stmt->error));
}

// Close statement and connection
$stmt->close();
$conn->close();
?>