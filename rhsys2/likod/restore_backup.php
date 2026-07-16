<?php
require 'session_utils.php';
enforce_role(['is_midwife', 'is_admin'], true); // Use your role system

header('Content-Type: application/json');
require 'backup_manager.php';

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

try {
    $backupManager = new BackupManager();
    $user_id = $_SESSION['user_id'];
    
    $result = $backupManager->restoreBackup($filename, $user_id);
    
    if ($result['success']) {
        // Log the restore action
        require 'activity_logger.php';
        $logger = new ActivityLogger();
        $logger->log($user_id, 'BACKUP_RESTORE', 'Restored database from backup: ' . $filename);
        
        echo json_encode($result);
    } else {
        http_response_code(500);
        echo json_encode($result);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>