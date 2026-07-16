<?php
// delete_batch.php - Deletes a specific inventory batch record.

require 'session_utils.php';
enforce_login(true);

header('Content-Type: application/json');
require 'db_con.php'; 

// 1. Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit;
}

$batch_id = intval($data['batch_id'] ?? 0);

if ($batch_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Batch ID for deletion.']);
    exit;
}

// 2. Perform deletion
// Since inventory_batches has no cascade constraints pointing to it, this is safe.
$sql = "DELETE FROM inventory_batches WHERE batch_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $batch_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // FIX: Removed the first echo, so we only send the response once after logging.
            require_once 'activity_logger.php';
                $logger = new ActivityLogger();
                $logger->log($_SESSION['user_id'], 'BATCH_DELETED', 'Deleted inventory batch', 'inventory_batches', $batch_id);
    
    echo json_encode(['success' => true, 'message' => 'Batch deleted successfully.']);
        } else {
            // No row was deleted, meaning the Batch ID was not found.
            echo json_encode(['success' => false, 'message' => 'Batch not found.']);
        }
    } else {
        error_log("SQL Error: " . $stmt->error);
        http_response_code(500); 
        echo json_encode(['success' => false, 'message' => 'Failed to delete batch: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    error_log("DB Prepare Error: " . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error preparing statement.']);
}

$conn->close();
?>