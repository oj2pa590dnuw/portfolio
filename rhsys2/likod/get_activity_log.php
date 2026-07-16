<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

try {
    require_once 'session_utils.php';
    enforce_login(true);
    require 'db_con.php';

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get filter parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $actionFilter = isset($_GET['action']) ? $_GET['action'] : '';
    $userFilter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $dateFilter = isset($_GET['date']) ? $_GET['date'] : '';

    $offset = ($page - 1) * $limit;

    // Build query based on your actual database schema
    $sql = "SELECT al.*, u.first_name, u.last_name 
            FROM activity_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            WHERE 1=1";

    $params = [];
    $types = '';

    if (!empty($actionFilter)) {
        $sql .= " AND al.action = ?";
        $params[] = $actionFilter;
        $types .= 's';
    }

    if (!empty($userFilter)) {
        $sql .= " AND al.user_id = ?";
        $params[] = $userFilter;
        $types .= 'i';
    }

    if (!empty($dateFilter)) {
        $sql .= " AND DATE(al.created_at) = ?";
        $params[] = $dateFilter;
        $types .= 's';
    }

    $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    // Prepare and execute main query
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $logs = [];
    
    while ($row = $result->fetch_assoc()) {
        // Create full name from first_name and last_name
        $row['full_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
        $logs[] = $row;
    }

    // Count total records
    $countSql = "SELECT COUNT(*) as total FROM activity_logs al WHERE 1=1";
    $countParams = [];
    $countTypes = '';

    if (!empty($actionFilter)) {
        $countSql .= " AND al.action = ?";
        $countParams[] = $actionFilter;
        $countTypes .= 's';
    }

    if (!empty($userFilter)) {
        $countSql .= " AND al.user_id = ?";
        $countParams[] = $userFilter;
        $countTypes .= 'i';
    }

    if (!empty($dateFilter)) {
        $countSql .= " AND DATE(al.created_at) = ?";
        $countParams[] = $dateFilter;
        $countTypes .= 's';
    }

    $stmt = $conn->prepare($countSql);
    if (!empty($countParams)) {
        $stmt->bind_param($countTypes, ...$countParams);
    }
    $stmt->execute();
    $totalResult = $stmt->get_result();
    $totalRow = $totalResult->fetch_assoc();
    $totalRecords = $totalRow['total'];
    $totalPages = ceil($totalRecords / $limit);

    $buffer = ob_get_clean();
    if (!empty($buffer)) {
        throw new Exception('Unexpected output: ' . $buffer);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'totalRecords' => $totalRecords,
        'debug' => [
            'query' => $sql,
            'params' => $params,
            'found_records' => count($logs)
        ]
    ]);

} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
exit;
?>