<?php
// add_inventory.php - Adds a new inventory item definition (no initial stock/expiry).

// 1. START SESSION AND ENFORCE LOGIN
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

// 2. Collect and sanitize input data
$item_id = intval($data['item_id'] ?? 0); 
$item_name = trim($data['item_name'] ?? '');
$category_name = trim($data['item_category'] ?? '');
$dosage_form_name = trim($data['dosage_form'] ?? '');
$unit_of_issue = trim($data['unit_of_issue'] ?? '');
$reorder_point = intval($data['reorder_point'] ?? 0);
$description = trim($data['description'] ?? null); 

// Basic validation
if (empty($item_name) || empty($category_name) || empty($dosage_form_name) || empty($unit_of_issue)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing item name, category, dosage form, or unit of issue.']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // 3. Get Category and Dosage Form IDs
    // Get Category ID
    $cat_stmt = $conn->prepare("SELECT category_id FROM item_categories WHERE category_name = ?");
    if (!$cat_stmt) throw new Exception("Category prepare failed: " . $conn->error);
    $cat_stmt->bind_param("s", $category_name);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    $category_id = $cat_result->fetch_assoc()['category_id'] ?? null;
    $cat_stmt->close();
    
    // Get Dosage Form ID
    $form_stmt = $conn->prepare("SELECT dosage_form_id FROM dosage_forms WHERE form_name = ?");
    if (!$form_stmt) throw new Exception("Dosage form prepare failed: " . $conn->error);
    $form_stmt->bind_param("s", $dosage_form_name);
    $form_stmt->execute();
    $form_result = $form_stmt->get_result();
    $dosage_form_id = $form_result->fetch_assoc()['dosage_form_id'] ?? null;
    $form_stmt->close();

    // The item definition cannot be added without valid foreign key IDs
    if (!$category_id) {
        throw new Exception("Error: Category '$category_name' not found. Please ensure it exists in the database.");
    }
    if (!$dosage_form_id) {
        throw new Exception("Error: Dosage Form '$dosage_form_name' not found. Please ensure it exists in the database.");
    }

    // 4. Insert New Item Definition
    $sql = "INSERT INTO inventory (item_name, description, category_id, dosage_form_id, unit_of_issue, reorder_point) VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param(
        "ssiisi",
        $item_name,
        $description,
        $category_id,
        $dosage_form_id,
        $unit_of_issue,
        $reorder_point
    );

    if ($stmt->execute()) {
        // 🛑 CRITICAL FIX: Get the insert ID BEFORE activity logging
        $new_item_id = $conn->insert_id;
        
        // 🛑 CRITICAL FIX: Check commit result BEFORE activity logging
        if ($conn->commit()) {
            // Now log the activity after successful commit
            require_once 'activity_logger.php';
            $logger = new ActivityLogger();
            $logger->log($_SESSION['user_id'], 'INVENTORY_ADDED', 'Added new inventory item', 'inventory', $new_item_id);
            
            echo json_encode(['success' => true, 'message' => 'Item definition added. Restock separately to add stock.']);
        } else {
            throw new Exception("Database commit failed: " . $conn->error);
        }
        
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    $stmt->close();

} catch (Exception $e) {
    // Rollback the transaction on ANY failure
    $conn->rollback(); 
    error_log("Transaction failed: " . $e->getMessage());
    http_response_code(500); 
    // Send the detailed error message back to the client
    echo json_encode(['success' => false, 'message' => 'Failed to add item: ' . $e->getMessage()]);
}

$conn->close();
?>