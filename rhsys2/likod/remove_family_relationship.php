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

if (!isset($_POST['relationship_id']) || empty($_POST['relationship_id'])) {
    http_response_code(400);
    die(json_encode(array("success" => false, "message" => "Relationship ID is required")));
}

$relationship_id = $conn->real_escape_string($_POST['relationship_id']);

$conn->begin_transaction();

try {
    // Get the relationship details
    $get_sql = "SELECT * FROM patient_relationships WHERE relationship_id = '$relationship_id'";
    $result = $conn->query($get_sql);
    
    if ($result->num_rows === 0) {
        throw new Exception("Relationship not found");
    }
    
    $relationship = $result->fetch_assoc();
    
    // Delete the main relationship
    $delete_sql = "DELETE FROM patient_relationships WHERE relationship_id = '$relationship_id'";
    if (!$conn->query($delete_sql)) {
        throw new Exception("Failed to delete relationship: " . $conn->error);
    }
    
    // Find and delete the reciprocal relationship
    $find_reciprocal_sql = "SELECT relationship_id FROM patient_relationships 
                           WHERE patient_id = '{$relationship['related_patient_id']}' 
                           AND related_patient_id = '{$relationship['patient_id']}'";
    $reciprocal_result = $conn->query($find_reciprocal_sql);
    
    if ($reciprocal_result && $reciprocal_result->num_rows > 0) {
        $reciprocal = $reciprocal_result->fetch_assoc();
        $delete_reciprocal_sql = "DELETE FROM patient_relationships WHERE relationship_id = '{$reciprocal['relationship_id']}'";
        $conn->query($delete_reciprocal_sql);
    }
    
    $conn->commit();
    
    require_once 'activity_logger.php';
    $logger = new ActivityLogger();
    $logger->log($_SESSION['user_id'], 'FAMILY_RELATIONSHIP_REMOVED', 'Removed family relationship', 'patient_relationships', $relationship_id);
    
    echo json_encode(array("success" => true, "message" => "Relationship removed successfully"));
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => $e->getMessage()));
}

$conn->close();
?>