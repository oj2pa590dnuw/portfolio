<?php
// activity_logger.php - Improved version
require 'db_con.php';

class ActivityLogger {
    private $conn;
    
    public function __construct() {
        global $conn;
        
        if (!$conn) {
            throw new Exception("Database connection is not available.");
        }
        
        $this->conn = $conn;
    }
    
    public function log($user_id, $action, $description = '', $table_name = null, $record_id = null) {
        try {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // Check if activity_logs table exists
            $table_exists = $this->conn->query("SHOW TABLES LIKE 'activity_logs'");
            if (!$table_exists || $table_exists->num_rows == 0) {
                error_log("Activity logs table doesn't exist");
                return true; // Don't fail if table doesn't exist
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO activity_logs (user_id, action, description, table_name, record_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                error_log("Failed to prepare activity log statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("isssiss", $user_id, $action, $description, $table_name, $record_id, $ip_address, $user_agent);
            $result = $stmt->execute();
            
            if (!$result) {
                error_log("Failed to log activity: " . $stmt->error);
            }
            
            $stmt->close();
            return $result;
            
        } catch (Exception $e) {
            error_log("Activity logging error: " . $e->getMessage());
            return false;
        }
    }
    
    // Helper method for common operations
    public function logAction($action, $description = '', $table_name = null, $record_id = null) {
        $user_id = $_SESSION['user_id'] ?? 0;
        return $this->log($user_id, $action, $description, $table_name, $record_id);
    }
}
?>