<?php
// delete_inventory.php - Deletes an inventory item and all related batch records.

// 1. START SESSION AND ENFORCE LOGIN
require 'session_utils.php'; // <--- NEW UTILITY
enforce_login(true);

header('Content-Type: application/json');

// PATH CORRECTION: Assuming db_con.php is in the same directory
require 'db_con.php'; 

// 1. Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
    exit;
}

$item_id = intval($data['item_id'] ?? 0);

if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid item ID for deletion.']);
    exit;
}

// 2. Perform deletion
// Because we set the Foreign Key constraint on inventory_batches with ON DELETE CASCADE,
// deleting the item from the inventory table will automatically delete all associated batches.
$sql = "DELETE FROM inventory WHERE item_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $item_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // FIX: Removed the first echo, so we only send the response once after logging.
            require_once 'activity_logger.php';
    $logger = new ActivityLogger();
    $logger->log($_SESSION['user_id'], 'INVENTORY_DELETED', 'Deleted inventory item and batches', 'inventory', $item_id);
    
    echo json_encode(['success' => true, 'message' => 'Item and all associated batches deleted successfully.']);
        } else {
            // No row was deleted, meaning the item ID was not found.
            echo json_encode(['success' => false, 'message' => 'Item not found.']);
        }
    } else {
        error_log("SQL Error: " . $stmt->error);
        http_response_code(500); 
        echo json_encode(['success' => false, 'message' => 'Failed to delete item: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    error_log("DB Prepare Error: " . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error preparing statement.']);
}

$conn->close();
?>