<?php
require 'session_utils.php'; // <--- NEW UTILITY
enforce_login(true);

// Set CORS headers for API access
header('Content-Type: application/json');

// PATH CORRECTION: Assuming db_con.php is in the same directory
require 'db_con.php'; 

// SQL query to join inventory with aggregated batch data
// 1. Calculate the SUM of current_stock for each item.
// 2. Find the MIN (soonest) non-expired expiration_date for each item.
$sql = "SELECT 
            i.item_id, 
            i.item_name, 
            i.description,
            i.unit_of_issue,
            i.reorder_point,
            ic.category_name AS item_category,
            df.form_name AS dosage_form,
            
            -- Aggregated Stock: Sum of current stock from all non-expired batches
            COALESCE(SUM(CASE WHEN b.expiration_date > CURDATE() THEN b.current_stock ELSE 0 END), 0) AS total_stock,
            
            -- Aggregated Expiry: Soonest expiration date from all non-expired batches
            COALESCE(MIN(CASE WHEN b.expiration_date > CURDATE() THEN b.expiration_date ELSE NULL END), 'N/A') AS latest_expiry_date
        FROM 
            inventory i
        LEFT JOIN 
            item_categories ic ON i.category_id = ic.category_id
        LEFT JOIN 
            dosage_forms df ON i.dosage_form_id = df.dosage_form_id
        LEFT JOIN 
            inventory_batches b ON i.item_id = b.item_id
        GROUP BY 
            i.item_id, i.item_name, i.description, i.unit_of_issue, i.reorder_point, ic.category_name, df.form_name
        ORDER BY 
            i.item_name ASC";

$result = $conn->query($sql);

$inventory = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $inventory[] = $row;
    }
}

echo json_encode($inventory);

$conn->close();
?>