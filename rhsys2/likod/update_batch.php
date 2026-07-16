<?php
// update_batch.php - Updates an existing inventory batch record.

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

// 2. Collect and validate input
$batch_id = intval($data['batch_id'] ?? 0);
$batch_number = $conn->real_escape_string($data['batch_number'] ?? '');
$current_stock = intval($data['current_stock'] ?? 0);
$expiration_date = $conn->real_escape_string($data['expiration_date'] ?? null);

// Basic Validation
if ($batch_id <= 0 || empty($batch_number) || $current_stock < 0 || empty($expiration_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing Batch ID, Batch Number, Stock, or Expiration Date.']);
    exit;
}

// Check for valid date format
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $expiration_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit;
}

// 3. Prepare SQL statement for updating inventory_batches
$sql = "UPDATE inventory_batches SET
    batch_number = ?, 
    current_stock = ?, 
    expiration_date = ?
    WHERE batch_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param(
        "sisi",
        $batch_number, 
        $current_stock, 
        $expiration_date, 
        $batch_id
    );

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            require_once 'activity_logger.php';
    $logger = new ActivityLogger();
    $logger->log($_SESSION['user_id'], 'BATCH_UPDATED', 'Updated inventory batch', 'inventory_batches', $batch_id);
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Batch updated successfully.']);
        } else {
            // No row affected, but query ran fine (e.g., ID not found or no change)
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Batch found, but no changes were made or Batch ID not found.']);
        }
    } else {
        error_log("SQL Error: " . $stmt->error);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update batch: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    error_log("Prepare Failed: " . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error preparing statement.']);
}

$conn->close();
?>