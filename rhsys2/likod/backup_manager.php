<?php
require 'db_con.php'; // This already creates $conn

class BackupManager {
    private $conn;
    private $backup_dir;
    
    public function __construct() {
        global $conn; // Use the existing connection from db_con.php
        
        if (!$conn) {
            throw new Exception("Database connection is not available.");
        }
        
        $this->conn = $conn;
        
        // Use the correct path - backups folder in the web root
        // __DIR__ is the directory of this file (likod folder)
        // So we go up one level to the web root, then into backups
        $this->backup_dir = realpath(__DIR__ . '/../') . '/backups/';
        
        // Create backup directory if it doesn't exist
        if (!file_exists($this->backup_dir)) {
            if (!mkdir($this->backup_dir, 0755, true)) {
                throw new Exception("Failed to create backup directory: " . $this->backup_dir);
            }
        }
        
        // Debug: log the backup directory path
        error_log("Backup directory: " . $this->backup_dir);
    }
    
    public function getBackupFiles() {
        try {
            $files = array();
            
            error_log("Looking for backup files in: " . $this->backup_dir);
            
            if (!is_dir($this->backup_dir)) {
                error_log("Backup directory does not exist: " . $this->backup_dir);
                return $files;
            }
            
            $file_list = scandir($this->backup_dir);
            if ($file_list === false) {
                error_log("Failed to scan backup directory: " . $this->backup_dir);
                return $files;
            }
            
            error_log("Found files in directory: " . implode(", ", $file_list));
            
            foreach ($file_list as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $filepath = $this->backup_dir . $file;
                    if (file_exists($filepath)) {
                        $files[] = array(
                            'filename' => $file,
                            'file_size' => filesize($filepath),
                            'created_at' => date('Y-m-d H:i:s', filemtime($filepath))
                        );
                        error_log("Found backup file: " . $file);
                    }
                }
            }
            
            // Sort by creation time, newest first
            usort($files, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            error_log("Total backup files found: " . count($files));
            
            return $files;
            
        } catch (Exception $e) {
            error_log("Error in getBackupFiles: " . $e->getMessage());
            return array();
        }
    }
    
    public function createBackup($user_id) {
        try {
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $this->backup_dir . $filename;
            
            error_log("Creating backup file: " . $filepath);
            
            // Get all tables
            $tables = array();
            $result = $this->conn->query("SHOW TABLES");
            if (!$result) {
                throw new Exception("Failed to get tables: " . $this->conn->error);
            }
            
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            error_log("Found tables to backup: " . implode(", ", $tables));
            
            $sql = "-- RHSYS Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Database: " . $this->conn->query("SELECT DATABASE()")->fetch_row()[0] . "\n\n";
            
            foreach ($tables as $table) {
                // Drop table if exists
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                
                // Create table structure
                $create_result = $this->conn->query("SHOW CREATE TABLE `$table`");
                if (!$create_result) {
                    throw new Exception("Failed to get structure for table $table: " . $this->conn->error);
                }
                $create_table = $create_result->fetch_row();
                $sql .= $create_table[1] . ";\n\n";
                
                // Insert data
                $data_result = $this->conn->query("SELECT * FROM `$table`");
                if ($data_result && $data_result->num_rows > 0) {
                    $sql .= "INSERT INTO `$table` VALUES ";
                    $rows = array();
                    while ($row = $data_result->fetch_row()) {
                        $values = array_map(function($value) {
                            if ($value === null) return 'NULL';
                            return "'" . $this->conn->real_escape_string($value) . "'";
                        }, $row);
                        $rows[] = "(" . implode(", ", $values) . ")";
                    }
                    $sql .= implode(",\n", $rows) . ";\n\n";
                }
            }
            
            // Write to file
            if (file_put_contents($filepath, $sql)) {
                $file_size = filesize($filepath);
                error_log("Backup file created successfully: " . $filename . " (" . $file_size . " bytes)");
                $this->logBackup($user_id, $filename, $file_size);
                return array('success' => true, 'filename' => $filename, 'file_size' => $file_size);
            } else {
                throw new Exception('Failed to write backup file to: ' . $filepath);
            }
            
        } catch (Exception $e) {
            error_log("Backup creation error: " . $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    public function restoreBackup($filename, $user_id) {
        try {
            $filepath = $this->backup_dir . $filename;
            
            error_log("Attempting to restore backup: " . $filepath);
            
            if (!file_exists($filepath)) {
                throw new Exception('Backup file not found at: ' . $filepath);
            }
            
            $sql = file_get_contents($filepath);
            $queries = array_filter(array_map('trim', explode(';', $sql)));
            
            // Disable foreign key checks
            $this->conn->query("SET FOREIGN_KEY_CHECKS = 0");
            
            foreach ($queries as $query) {
                if (!empty($query)) {
                    if (!$this->conn->query($query)) {
                        // Re-enable foreign key checks
                        $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
                        throw new Exception('Query failed: ' . $this->conn->error . " in query: " . substr($query, 0, 100));
                    }
                }
            }
            
            // Re-enable foreign key checks
            $this->conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            error_log("Backup restored successfully: " . $filename);
            return array('success' => true, 'message' => 'Database restored successfully');
            
        } catch (Exception $e) {
            error_log("Backup restore error: " . $e->getMessage());
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    private function logBackup($user_id, $filename, $file_size) {
        // For now, just log to error log - we'll implement proper activity logging later
        error_log("Backup created by user $user_id: $filename ($file_size bytes)");
        return true;
    }
    
    // Debug method to get the backup directory path
    public function getBackupDir() {
        return $this->backup_dir;
    }
}
?>