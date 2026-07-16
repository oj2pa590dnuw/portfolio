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

// Get POST data
$patient_id = $conn->real_escape_string($_POST['patient_id'] ?? '');
$relationship_type = $conn->real_escape_string($_POST['relationship_type'] ?? '');
$relative_patient_id = $conn->real_escape_string($_POST['relative_patient_id'] ?? '');

// Validate required fields
if (empty($patient_id) || empty($relationship_type) || empty($relative_patient_id)) {
    http_response_code(400);
    die(json_encode(array("success" => false, "message" => "All fields are required")));
}

// Check if patients exist
$patient_check = $conn->query("SELECT id FROM patients WHERE id = '$patient_id'");
$relative_check = $conn->query("SELECT id FROM patients WHERE id = '$relative_patient_id'");

if ($patient_check->num_rows == 0 || $relative_check->num_rows == 0) {
    http_response_code(400);
    die(json_encode(array("success" => false, "message" => "One or both patients not found")));
}

// Define reciprocal relationships
$reciprocal_map = [
    'Parent' => 'Child',
    'Child' => 'Parent', 
    'Spouse' => 'Spouse',
    'Sibling' => 'Sibling',
    'Grandparent' => 'Grandchild',
    'Grandchild' => 'Grandparent',
    'Other' => 'Other'
];

$reciprocal_type = $reciprocal_map[$relationship_type] ?? 'Other';

// Check if relationship already exists
$check_sql = "SELECT relationship_id FROM patient_relationships 
              WHERE patient_id = '$patient_id' 
              AND related_patient_id = '$relative_patient_id' 
              AND relationship_type = '$relationship_type'";
$check_result = $conn->query($check_sql);

if ($check_result && $check_result->num_rows > 0) {
    http_response_code(400);
    die(json_encode(array("success" => false, "message" => "This relationship already exists")));
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert main relationship
    $insert_sql = "INSERT INTO patient_relationships (patient_id, related_patient_id, relationship_type) 
                   VALUES ('$patient_id', '$relative_patient_id', '$relationship_type')";
    
    if (!$conn->query($insert_sql)) {
        throw new Exception("Failed to add relationship: " . $conn->error);
    }
    
    $main_relationship_id = $conn->insert_id;
    
    // Insert reciprocal relationship
    $insert_reciprocal_sql = "INSERT INTO patient_relationships (patient_id, related_patient_id, relationship_type) 
                              VALUES ('$relative_patient_id', '$patient_id', '$reciprocal_type')";
    
    if (!$conn->query($insert_reciprocal_sql)) {
        throw new Exception("Failed to add reciprocal relationship: " . $conn->error);
    }
    
    $conn->commit();
    
    require_once 'activity_logger.php';
    $logger = new ActivityLogger();
    $logger->log($_SESSION['user_id'], 'FAMILY_RELATIONSHIP_ADDED', "Added $relationship_type relationship", 'patient_relationships', $main_relationship_id);
    
    echo json_encode(array("success" => true, "message" => "Relationship added successfully"));
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => $e->getMessage()));
}

$conn->close();
?>