<?php
// update_inventory.php - Updates an existing inventory item definition.

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

// 2. Collect and sanitize input data
$item_id = intval($data['item_id'] ?? 0);
$item_name = $data['item_name'] ?? '';
$category_name = $data['item_category'] ?? '';
$dosage_form_name = $data['dosage_form'] ?? '';
$unit_of_issue = $data['unit_of_issue'] ?? '';
$reorder_point = intval($data['reorder_point'] ?? 0);
$description = $data['description'] ?? null; 

// Basic validation
if ($item_id <= 0 || empty($item_name) || empty($category_name) || empty($dosage_form_name) || empty($unit_of_issue)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing data or invalid Item ID.']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // 3. Get Category ID and Dosage Form ID by name (Assume no change if the names are the same)
    
    // Get Category ID
    $cat_sql = "SELECT category_id FROM item_categories WHERE category_name = ?";
    $cat_stmt = $conn->prepare($cat_sql);
    if (!$cat_stmt) {
        throw new Exception("Category Prepare failed: " . $conn->error);
    }
    $cat_stmt->bind_param("s", $category_name);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    $category_id = $cat_result->fetch_assoc()['category_id'] ?? null;
    $cat_stmt->close();

    // Get Dosage Form ID
    $form_sql = "SELECT dosage_form_id FROM dosage_forms WHERE form_name = ?";
    $form_stmt = $conn->prepare($form_sql);
    if (!$form_stmt) {
        throw new Exception("Dosage Form Prepare failed: " . $conn->error);
    }
    $form_stmt->bind_param("s", $dosage_form_name);
    $form_stmt->execute();
    $form_result = $form_stmt->get_result();
    $dosage_form_id = $form_result->fetch_assoc()['dosage_form_id'] ?? null;
    $form_stmt->close();

    if (!$category_id || !$dosage_form_id) {
        throw new Exception("Invalid Category or Dosage Form selected. Please check dropdown values.");
    }
    
    // 4. Update Item Definition
    $sql = "UPDATE inventory SET
            item_name = ?, description = ?, category_id = ?, dosage_form_id = ?, unit_of_issue = ?, reorder_point = ?
            WHERE item_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssiisii",
        $item_name,
        $description,
        $category_id,
        $dosage_form_id,
        $unit_of_issue,
        $reorder_point,
        $item_id
    );

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // 🚨 FIX 1: Removed duplicate commit()
            $conn->commit(); 
            require_once 'activity_logger.php';
    $logger = new ActivityLogger();
    $logger->log($_SESSION['user_id'], 'INVENTORY_UPDATED', 'Updated inventory item', 'inventory', $item_id);
    
    echo json_encode(['success' => true, 'message' => 'Item definition updated successfully.']);
        } else {
            // No row was affected, but the item exists and the query ran fine.
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'No changes made or item ID not found.']);
        }
    } else {
        $conn->rollback();
        error_log("SQL Error: " . $stmt->error);
        http_response_code(500); 
        echo json_encode(['success' => false, 'message' => 'Failed to update item definition: ' . $stmt->error]);
    }
    $stmt->close();

} catch (Exception $e) {
    // 🚨 FIX 2: Added rollback for transaction exceptions
    $conn->rollback();
    error_log("Transaction Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A transaction error occurred: ' . $e->getMessage()]);
}

$conn->close();

?>