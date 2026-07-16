<?php
// CRITICAL: We are removing the JSON header and forcing errors to display.
// This will tell us if PHP is crashing before reaching the JSON output line.
error_reporting(E_ALL); 
ini_set('display_errors', 1);

// I will revert the file paths back to the version you said was working
// If 'update_patient.php' is in 'likod/', then 'require' needs to be relative to 'likod/'.
// If 'session_utils.php' is in the root, it needs '../session_utils.php'.
// Assuming your original files were in the same directory:
// If the paths worked before, we'll keep the paths the same as your initial file:
require 'session_utils.php'; 
enforce_login(true);

// TEMPORARILY COMMENTED OUT: We need to see the error, not force JSON
// header('Content-Type: application/json'); 
require 'db_con.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // We are still echoing JSON, but the browser will see this as text/html
    die(json_encode(["success" => false, "message" => "DB connection failed: " . $conn->connect_error]));
}

$json = file_get_contents("php://input");
$data = json_decode($json, true);

$id = $data['id'] ?? null;
if (!$id) {
    die(json_encode(["success" => false, "message" => "No patient ID"]));
}

// Get values with defaults
$fullName = $data['fullName'] ?? '';
$age = (int)($data['age'] ?? 0);
$location = $data['location'] ?? '';
$contactNumber = $data['contactNumber'] ?? '';
$bloodPressure = $data['bloodPressure'] ?? '';
$heartRate = $data['heartRate'] ?? '';
$temperature = $data['temperature'] ?? '';
$weight = $data['weight'] ?? '';
$height = $data['height'] ?? '';
$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$clinicalNotes = $data['clinicalNotes'] ?? '';
$warning = $data['warning'] ?? '';
$otherInfo = $data['otherInfo'] ?? '';

// Booleans (force to 0 or 1)
$isPregnant = isset($data['isPregnant']) && $data['isPregnant'] ? 1 : 0;
$isElderly = isset($data['isElderly']) && $data['isElderly'] ? 1 : 0;
$hasHighBP = isset($data['hasHighBP']) && $data['hasHighBP'] ? 1 : 0;
$needsMedication = isset($data['needsMedication']) && $data['needsMedication'] ? 1 : 0;
$isCritical = isset($data['isCritical']) && $data['isCritical'] ? 1 : 0;
$isWarningFlag = isset($data['isWarningFlag']) && $data['isWarningFlag'] ? 1 : 0;
$isStable = isset($data['isStable']) && $data['isStable'] ? 1 : 0;
$needsAppointment = isset($data['needsAppointment']) && $data['needsAppointment'] ? 1 : 0;

$sql = "UPDATE patients SET 
    fullName=?, age=?, location=?, contactNumber=?,
    bloodPressure=?, heartRate=?, temperature=?, weight=?, height=?,
    isPregnant=?, isElderly=?, hasHighBP=?, needsMedication=?,
    isCritical=?, isWarningFlag=?, isStable=?, needsAppointment=?,
    title=?, description=?, clinicalNotes=?, warning=?, otherInfo=?
    WHERE id=?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    // CRITICAL: Showing the actual error here
    die(json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]));
}

// *** CONFIRMED FIX: Type string is now "sisssssssiiiiiiissssss" (23 types) ***
$stmt->bind_param(
    "sisssssssiiiiiiisssssss",  // <-- 23 total types
    $fullName, $age, $location, $contactNumber,
    $bloodPressure, $heartRate, $temperature, $weight, $height,
    $isPregnant, $isElderly, $hasHighBP, $needsMedication,
    $isCritical, $isWarningFlag, $isStable, $needsAppointment,
    $title, $description, $clinicalNotes, $warning, $otherInfo, $id
);


if ($stmt->execute()) {
    require_once 'activity_logger.php';
    $logger = new ActivityLogger();
    $logger->log($_SESSION['user_id'], 'PATIENT_UPDATED', 'Updated patient record', 'patients', $id);
    
    echo json_encode(["success" => true, "message" => "Updated"]);
} else {
    // CRITICAL: Showing the actual execution error here
    echo json_encode(["success" => false, "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>