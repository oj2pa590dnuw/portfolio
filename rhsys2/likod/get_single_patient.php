<?php
require 'session_utils.php';
enforce_login(true);

header('Content-Type: application/json');
require 'db_con.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(array("error" => array("message" => "Database connection failed: " . $conn->connect_error))));
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    die(json_encode(array("error" => array("message" => "Patient ID is required"))));
}

$patient_id = $_GET['id'];

// Fixed: Added all missing fields that the frontend expects
$sql = "SELECT 
    id,
    patient_code,
    fullName,
    middle_name,
    last_name,
    age,
    birthDate,
    philhealth_id,
    local_patient_id,
    contactNumber,
    location,
    lastCheckup,
    warning,
    bloodPressure,
    heartRate,
    respiratoryRate,
    temperature,
    clinicalNotes,
    isCritical,
    isPregnant,
    isElderly,
    isWarningFlag,
    isStable,
    registered_by_user_id,
    weight,
    height,
    title,
    description,
    otherInfo,
    time,
    normalRanges,
    hasHighBP,
    needsMedication,
    needsAppointment
FROM patients 
WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    die(json_encode(array("error" => array("message" => "Failed to prepare statement: " . $conn->error))));
}

$stmt->bind_param("s", $patient_id);

if (!$stmt->execute()) {
    http_response_code(500);
    die(json_encode(array("error" => array("message" => "Query failed: " . $stmt->error))));
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die(json_encode(array("error" => array("message" => "Patient not found with ID: " . $patient_id))));
}

$patient = $result->fetch_assoc();

// Clean NULL values for better JSON handling
foreach ($patient as $key => $value) {
    if ($value === null) {
        $patient[$key] = '';
    }
}

http_response_code(200);
echo json_encode($patient);

$stmt->close();
$conn->close();
?>