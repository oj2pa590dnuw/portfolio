<?php
require 'session_utils.php';
enforce_role(['is_midwife', 'is_admin'], true);

header('Content-Type: application/json');
require 'backup_manager.php';

try {
    $backupManager = new BackupManager();
    $user_id = $_SESSION['user_id'];
    
    $result = $backupManager->createBackup($user_id);
    
    if ($result['success']) {
        // Log the backup action
        require 'activity_logger.php';
        $logger = new ActivityLogger();
        $logger->log($user_id, 'BACKUP_CREATE', 'Created database backup: ' . $result['filename']);
        
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