<?php
require 'session_utils.php';
enforce_login(true);

header('Content-Type: application/json');
require 'db_con.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(array("success" => false, "message" => "Database connection failed")));
}

if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    http_response_code(400);
    die(json_encode(array("success" => false, "message" => "Patient ID is required")));
}

$patient_id = $_GET['patient_id'];

// Get patient info
$sql_patient = "SELECT fullName, age, birthDate, location, contactNumber FROM patients WHERE id = ?";
$stmt_patient = $conn->prepare($sql_patient);
$stmt_patient->bind_param("s", $patient_id);
$stmt_patient->execute();
$result_patient = $stmt_patient->get_result();

if ($result_patient->num_rows === 0) {
    http_response_code(404);
    die(json_encode(array("success" => false, "message" => "Patient not found")));
}

$patient = $result_patient->fetch_assoc();
$stmt_patient->close();

// Get all visits for this patient
$sql_visits = "SELECT 
    visit_id,
    visit_date,
    TIME_FORMAT(created_at, '%h:%i %p') as visit_time,
    chief_complaint,
    blood_pressure,
    heart_rate,
    temperature,
    clinical_notes,
    procedures_done,
    attended_by_user_id
FROM patient_visits 
WHERE patient_id = ? 
ORDER BY visit_date DESC, created_at DESC";

$stmt_visits = $conn->prepare($sql_visits);
$stmt_visits->bind_param("s", $patient_id);
$stmt_visits->execute();
$result_visits = $stmt_visits->get_result();

$visits = array();
while ($row = $result_visits->fetch_assoc()) {
    $visits[] = $row;
}

$stmt_visits->close();
$conn->close();

// Return combined data
echo json_encode(array(
    "success" => true,
    "patient" => $patient,
    "visits" => $visits
));
?>