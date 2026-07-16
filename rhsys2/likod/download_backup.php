<?php
require 'session_utils.php';
enforce_role(['is_midwife', 'is_admin']);

$filename = $_GET['filename'] ?? '';

if (empty($filename)) {
    http_response_code(400);
    die('Filename is required');
}

// Security check: ensure filename is safe
if (preg_match('/\.\.|[\/\\\\]/', $filename)) {
    http_response_code(400);
    die('Invalid filename');
}

// Use the same path as backup_manager.php
$backup_dir = realpath(__DIR__ . '/../') . '/backups/';
$filepath = $backup_dir . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    die('Backup file not found');
}

// Log the download action
require 'activity_logger.php';
$logger = new ActivityLogger();
$logger->log($_SESSION['user_id'], 'BACKUP_DOWNLOAD', 'Downloaded backup file: ' . $filename);

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Pragma: no-cache');
header('Expires: 0');

readfile($filepath);
exit;
?>