<?php
require 'session_utils.php';
require 'db_con.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get current user's profile
    $stmt = $conn->prepare("
        SELECT id, first_name, last_name, email, is_bns, is_bhw, is_midwife, is_admin, approved_by_admin, created_at 
        FROM users 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Get role text
    $role_text = 'Unknown';
    if ($user['is_admin']) $role_text = 'Admin';
    elseif ($user['is_midwife']) $role_text = 'Midwife';
    elseif ($user['is_bns']) $role_text = 'BNS';
    elseif ($user['is_bhw']) $role_text = 'BHW';
    
    $user['role_text'] = $role_text;
    $user['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
    
    echo json_encode([
        'success' => true, 
        'user' => $user
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>