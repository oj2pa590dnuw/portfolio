<?php
require 'session_utils.php';
enforce_role(['is_midwife', 'is_admin'], true);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$filename = $_POST['filename'] ?? '';

if (empty($filename)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Filename is required']);
    exit;
}

// Security check: ensure filename is safe
if (preg_match('/\.\.|[\/\\\\]/', $filename)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid filename']);
    exit;
}

// Use the same path as backup_manager.php
$backup_dir = realpath(__DIR__ . '/../') . '/backups/';
$filepath = $backup_dir . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Backup file not found']);
    exit;
}

try {
    if (unlink($filepath)) {
        // Log the delete action
        require 'activity_logger.php';
        $logger = new ActivityLogger();
        $logger->log($_SESSION['user_id'], 'BACKUP_DELETE', 'Deleted backup file: ' . $filename);
        
        echo json_encode(['success' => true, 'message' => 'Backup file deleted successfully']);
    } else {
        throw new Exception('Failed to delete file');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>