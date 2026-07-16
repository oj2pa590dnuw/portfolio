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

// Get the target month/year from query parameter
$period = isset($_GET['period']) ? $_GET['period'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'month'; // 'month' or 'year'

// Validate period format based on type
if ($type === 'month' && !preg_match('/^\d{4}-\d{2}$/', $period)) {
    http_response_code(400);
    die(json_encode(array("success" => false, "message" => "Invalid month format. Use YYYY-MM")));
}

if ($type === 'year' && !preg_match('/^\d{4}$/', $period)) {
    http_response_code(400);
    die(json_encode(array("success" => false, "message" => "Invalid year format. Use YYYY")));
}

// Build query based on type
if ($type === 'month') {
    $sql = "SELECT 
        pv.visit_id,
        pv.visit_date,
        TIME_FORMAT(pv.created_at, '%h:%i %p') as visit_time,
        pv.patient_id,
        pv.chief_complaint,
        pv.blood_pressure,
        pv.heart_rate,
        pv.temperature,
        pv.clinical_notes,
        pv.procedures_done,
        pv.attended_by_user_id,
        p.fullName as patient_name,
        p.age,
        p.location
    FROM patient_visits pv
    INNER JOIN patients p ON pv.patient_id = p.id
    WHERE DATE_FORMAT(pv.visit_date, '%Y-%m') = ?
    ORDER BY pv.visit_date DESC, pv.created_at DESC";
} else {
    $sql = "SELECT 
        pv.visit_id,
        pv.visit_date,
        TIME_FORMAT(pv.created_at, '%h:%i %p') as visit_time,
        pv.patient_id,
        pv.chief_complaint,
        pv.blood_pressure,
        pv.heart_rate,
        pv.temperature,
        pv.clinical_notes,
        pv.procedures_done,
        pv.attended_by_user_id,
        p.fullName as patient_name,
        p.age,
        p.location
    FROM patient_visits pv
    INNER JOIN patients p ON pv.patient_id = p.id
    WHERE YEAR(pv.visit_date) = ?
    ORDER BY pv.visit_date DESC, pv.created_at DESC";
}

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    die(json_encode(array("success" => false, "message" => "Failed to prepare statement")));
}

$stmt->bind_param("s", $period);
$stmt->execute();
$result = $stmt->get_result();

$visits = array();

while ($row = $result->fetch_assoc()) {
    $visits[] = $row;
}

http_response_code(200);
echo json_encode($visits);

$stmt->close();
$conn->close();
?>