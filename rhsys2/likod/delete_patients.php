<?php
// likod/delete_patients.php - COMPLETE FIXED VERSION

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set JSON header
header('Content-Type: application/json');

// Check if required files exist
if (!file_exists('session_utils.php')) {
    echo json_encode(['success' => false, 'message' => 'session_utils.php not found']);
    exit;
}

if (!file_exists('db_con.php')) {
    echo json_encode(['success' => false, 'message' => 'db_con.php not found']);
    exit;
}

try {
    // Start session and check permissions
    require 'session_utils.php';
    enforce_login(true);
    enforce_role(['is_admin', 'is_midwife']); // Only admins and midwives can delete patients
    
    // Database connection
    require 'db_con.php';
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    // Get input data
    $input = file_get_contents("php://input");
    if (!$input) {
        throw new Exception('No input data received');
    }
    
    $data = json_decode($input, true);
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    $patient_id = $data['id'] ?? null;
    
    if (empty($patient_id)) {
        throw new Exception('Patient ID is required');
    }
    
    // Start transaction for atomic operations
    $conn->begin_transaction();
    
    try {
        // First, delete related records to maintain referential integrity
        
        // 1. Delete family relationships where this patient is involved
        $sql1 = "DELETE FROM patient_relationships WHERE patient_id = ? OR related_patient_id = ?";
        $stmt1 = $conn->prepare($sql1);
        $stmt1->bind_param("ss", $patient_id, $patient_id);
        $stmt1->execute();
        $stmt1->close();
        
        // 2. Delete family medical history
        $sql2 = "DELETE FROM family_medical_history WHERE patient_id = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("s", $patient_id);
        $stmt2->execute();
        $stmt2->close();
        
        // 3. Delete patient visits
        $sql3 = "DELETE FROM patient_visits WHERE patient_id = ?";
        $stmt3 = $conn->prepare($sql3);
        $stmt3->bind_param("s", $patient_id);
        $stmt3->execute();
        $stmt3->close();
        
        // 4. Update schedules that reference this patient (set to NULL instead of deleting)
        $sql4 = "UPDATE schedules SET patient_id = NULL WHERE patient_id = ?";
        $stmt4 = $conn->prepare($sql4);
        $stmt4->bind_param("s", $patient_id);
        $stmt4->execute();
        $stmt4->close();
        
        // 5. Finally delete the patient
        $sql5 = "DELETE FROM patients WHERE id = ?";
        $stmt5 = $conn->prepare($sql5);
        $stmt5->bind_param("s", $patient_id);
        
        if (!$stmt5->execute()) {
            throw new Exception('Failed to delete patient: ' . $stmt5->error);
        }
        
        $rows_affected = $stmt5->affected_rows;
        $stmt5->close();
        
        // Commit transaction
        $conn->commit();
        
        if ($rows_affected > 0) {
            // Log the activity
            require_once 'activity_logger.php';
            $logger = new ActivityLogger();
            $logger->log($_SESSION['user_id'], 'PATIENT_DELETED', 'Deleted patient and all related records', 'patients', $patient_id);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Patient and all related records deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Patient not found or already deleted'
            ]);
        }
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
    $conn->close();
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Patient delete error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
    exit;
}
?>