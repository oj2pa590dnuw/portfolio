<?php
// add_batch.php - Adds a new batch record for an inventory item.

ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'session_utils.php';
enforce_login(true);

header('Content-Type: application/json');
require 'db_con.php';

// Read input (JSON)
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No input received.']);
    exit;
}

// Extract fields
$item_id = intval($data['item_id'] ?? 0);
$batch_number = trim($data['batch_number'] ?? '');
$quantity_in_batch = intval($data['quantity_in_batch'] ?? 0);
$expiration_date = trim($data['expiration_date'] ?? '');
$restocked_by_user_id = intval($_SESSION['user_id'] ?? 0);

// Validate inputs
if ($item_id <= 0 || $batch_number === '' || $quantity_in_batch <= 0 || $expiration_date === '' || $restocked_by_user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid values (ID, Batch No., Quantity, Expiry, or User ID).']);
    exit;
}

// Check for valid date format
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $expiration_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Insert new batch into inventory_batches
    $insert_sql = "INSERT INTO inventory_batches 
        (item_id, batch_number, quantity_in_batch, current_stock, expiration_date, restocked_by_user_id)
        VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insert_sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param(
        "isiisi",
        $item_id,
        $batch_number,
        $quantity_in_batch,
        $quantity_in_batch, 
        $expiration_date,
        $restocked_by_user_id
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // 🛑 CRITICAL FIX: Get the insert ID BEFORE activity logging
    $new_batch_id = $conn->insert_id;
    
    // 🛑 CRITICAL FIX: Check commit result BEFORE activity logging
    if ($conn->commit()) {
        // Now log the activity after successful commit
        require_once 'activity_logger.php';
        $logger = new ActivityLogger();
        $logger->log($_SESSION['user_id'], 'BATCH_ADDED', 'Added new inventory batch', 'inventory_batches', $new_batch_id);
        
        echo json_encode(['success' => true, 'message' => 'Batch added successfully.']);
    } else {
        throw new Exception("Database commit failed: " . $conn->error);
    }
    
    $stmt->close();
    $conn->close();
    exit;

} catch (Exception $e) {
    // Rollback the transaction on ANY failure
    $conn->rollback(); 
    error_log("Transaction failed: " . $e->getMessage());
    http_response_code(500); 
    // Send the detailed error message back to the client
    echo json_encode(['success' => false, 'message' => 'Failed to add batch: ' . $e->getMessage()]);
}

// If the script reaches here, it means something went wrong outside the try/catch
if ($conn && $conn->ping()) {
    $conn->close();
}
?>