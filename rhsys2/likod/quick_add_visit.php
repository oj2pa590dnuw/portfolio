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

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

// Check if we're searching for a patient or adding a visit
if (isset($data['action']) && $data['action'] === 'search_patient') {
    $search_term = $data['search_term'];
    
    // Search by name (case-insensitive, partial match)
    $sql = "SELECT id, fullName, age, location, lastCheckup 
            FROM patients 
            WHERE fullName LIKE ? 
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $search_param = "%{$search_term}%";
    $stmt->bind_param("s", $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $patients = array();
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
    
    echo json_encode(array("success" => true, "patients" => $patients));
    $stmt->close();
    $conn->close();
    exit;
}

// If we reach here, we're adding a new patient and/or visit
if (isset($data['create_new_patient']) && $data['create_new_patient'] === true) {
    // Create new patient first
    if (!isset($data['patient_name']) || empty($data['patient_name'])) {
        http_response_code(400);
        die(json_encode(array("success" => false, "message" => "Patient name is required")));
    }
    
    $patient_id = uniqid('PT-', true); // Generate unique ID
    $patient_name = $data['patient_name'];
    $age = isset($data['age']) ? (int)$data['age'] : 0;
    $location = isset($data['location']) ? $data['location'] : '';
    $user_id = $_SESSION['user_id'];
    
    $sql_patient = "INSERT INTO patients (id, fullName, age, location, registered_by_user_id) 
                    VALUES (?, ?, ?, ?, ?)";
    
    $stmt_patient = $conn->prepare($sql_patient);
    $stmt_patient->bind_param("ssisi", $patient_id, $patient_name, $age, $location, $user_id);
    
    if (!$stmt_patient->execute()) {
        http_response_code(500);
        die(json_encode(array("success" => false, "message" => "Failed to create patient")));
    }
    $stmt_patient->close();
    
    // Use the newly created patient_id for the visit
    $data['patient_id'] = $patient_id;
}

// Now add the visit (whether for new or existing patient)
if (!isset($data['patient_id']) || empty($data['patient_id'])) {
    http_response_code(400);
    die(json_encode(array("success" => false, "message" => "Patient ID is required")));
}

$patient_id = $data['patient_id'];
$visit_date = isset($data['visit_date']) && !empty($data['visit_date']) ? $data['visit_date'] : date('Y-m-d');
$chief_complaint = isset($data['chief_complaint']) ? $data['chief_complaint'] : '';
$blood_pressure = isset($data['blood_pressure']) ? $data['blood_pressure'] : '';
$heart_rate = isset($data['heart_rate']) ? $data['heart_rate'] : '';
$temperature = isset($data['temperature']) ? $data['temperature'] : '';
$clinical_notes = isset($data['clinical_notes']) ? $data['clinical_notes'] : '';
$procedures_done = isset($data['procedures_done']) ? $data['procedures_done'] : '';
$user_id = $_SESSION['user_id'];

$sql = "INSERT INTO patient_visits (
    patient_id, 
    visit_date, 
    chief_complaint, 
    blood_pressure, 
    heart_rate, 
    temperature, 
    clinical_notes,
    procedures_done,
    attended_by_user_id
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssssssi",
    $patient_id,
    $visit_date,
    $chief_complaint,
    $blood_pressure,
    $heart_rate,
    $temperature,
    $clinical_notes,
    $procedures_done,
    $user_id
);

if ($stmt->execute()) {
    $visit_id = $conn->insert_id;
    
    // Update patient's latest info
    $update_sql = "UPDATE patients SET 
        lastCheckup = ?, 
        title = ?,
        bloodPressure = ?,
        heartRate = ?,
        temperature = ?,
        clinicalNotes = ?
        WHERE id = ?";
    
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param(
        "sssssss",
        $visit_date,
        $chief_complaint,
        $blood_pressure,
        $heart_rate,
        $temperature,
        $clinical_notes,
        $patient_id
    );
    $update_stmt->execute();
    $update_stmt->close();
    
    http_response_code(201);
    echo json_encode(array(
        "success" => true, 
        "message" => "Visit recorded successfully", 
        "visit_id" => $visit_id,
        "patient_id" => $patient_id
    ));
} else {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Failed to add visit: " . $stmt->error));
}

$stmt->close();
$conn->close();
?>