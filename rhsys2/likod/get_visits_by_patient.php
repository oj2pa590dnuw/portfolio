<?php
require 'session_utils.php';
enforce_login(true);

header('Content-Type: application/json');
require 'db_con.php'; 

if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
   http_response_code(400);
   die(json_encode(array("success" => false, "message" => "Patient ID is required")));
}

$patient_id = $_GET['patient_id'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
   http_response_code(500);
   die(json_encode(array("success" => false, "message" => "Connection failed: " . $conn->connect_error)));
}

$sql = "SELECT 
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
ORDER BY visit_date DESC, visit_id DESC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    die(json_encode(array("success" => false, "message" => "Failed to prepare statement: " . $conn->error)));
}

$stmt->bind_param("s", $patient_id);

if (!$stmt->execute()) {
    http_response_code(500);
    die(json_encode(array("success" => false, "message" => "Query failed: " . $stmt->error)));
}

$result = $stmt->get_result();
$visits_array = array();

while ($row = $result->fetch_assoc()) {
    // Clean NULL values
    foreach ($row as $key => $value) {
        if ($value === null) {
            $row[$key] = '';
        }
    }
    $visits_array[] = $row;
}

http_response_code(200);
echo json_encode($visits_array);

$stmt->close();
$conn->close();
?>