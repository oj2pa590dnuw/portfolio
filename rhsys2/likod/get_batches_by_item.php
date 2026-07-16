<?php
// get_batches_by_item.php - Gets all available batches for a specific inventory item.

require 'session_utils.php'; 
enforce_login(true);

header('Content-Type: application/json');

// PATH CORRECTION: Assuming db_con.php is in the same directory
require 'db_con.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);

// 🚨 CRUCIAL FIX: Database Connection Check
if ($conn->connect_error) {
    http_response_code(500); 
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// 1. Get item_id from the URL query (e.g., /get_batches_by_item.php?item_id=5)
$item_id = intval($_GET['item_id'] ?? 0);

if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
    $conn->close(); 
    exit;
}

// 2. Select all batches for that item
// ✅ PURE FIX: Selecting the correct column without any aliases.
$sql = "SELECT 
            ib.batch_id, 
            ib.batch_number, 
            ib.quantity_in_batch,  
            ib.current_stock, 
            ib.expiration_date,
            ib.date_restocked,
            COALESCE(u.fullName, 'System/Unknown') AS restocked_by_name
        FROM 
            inventory_batches ib
        LEFT JOIN
            users u ON ib.restocked_by_user_id = u.id
        WHERE 
            ib.item_id = ? 
        ORDER BY 
            ib.expiration_date ASC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error preparing statement: ' . $conn->error]);
    $conn->close();
    exit;
}

$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$batches = [];

while ($row = $result->fetch_assoc()) {
    $batches[] = $row;
}

// Success response
echo json_encode(['success' => true, 'batches' => $batches]);

$stmt->close();
$conn->close();
?>