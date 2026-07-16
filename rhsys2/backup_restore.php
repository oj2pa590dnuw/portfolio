<?php
require 'likod/session_utils.php';
enforce_role(['is_midwife', 'is_admin']);
require 'likod/backup_manager.php';

$backupManager = new BackupManager();
$backupFiles = $backupManager->getBackupFiles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backup & Restore - RHSYS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="app-container">
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="large-container">
            <div class="nav-links">
                <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>

            <h1>Backup & Restore</h1>

            <!-- Create Backup Section -->
            <div class="backup-section">
                <h2>📦 Create Backup</h2>
                <p>Create a complete backup of the database. This will include all patients, visits, users, and system data.</p>
                
                <div class="action-buttons">
                    <button onclick="createBackup()" class="btn backup-btn" id="backupBtn">
                        💾 Create Backup Now
                    </button>
                </div>
                
                <div id="backupResult"></div>
            </div>

            <!-- Restore Backup Section -->
            <div class="backup-section">
                <h2>🔄 Restore Backup</h2>
                
                <div class="alert-card warning">
                    <strong>⚠️ Warning:</strong> Restoring a backup will overwrite all current data. This action cannot be undone.
                    Make sure to create a backup before proceeding.
                </div>

                <div class="form-container">
                    <div class="form-item">
                        <label for="backupFile" class="form-label">Select Backup File:</label>
                        <select id="backupFile" class="form-select">
                            <option value="">Select a backup file...</option>
                            <?php foreach ($backupFiles as $file): ?>
                                <option value="<?= htmlspecialchars($file['filename']) ?>">
                                    <?= htmlspecialchars($file['filename']) ?> (<?= formatFileSize($file['file_size']) ?>) - <?= htmlspecialchars($file['created_at']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="action-buttons">
                        <button onclick="restoreBackup()" class="btn restore-btn" id="restoreBtn">
                            🔄 Restore Selected Backup
                        </button>
                    </div>
                    
                    <div id="restoreResult"></div>
                </div>
            </div>

            <!-- Available Backups Section -->
            <div class="backup-section">
                <h2>📁 Available Backups</h2>
                
                <div class="backup-list" id="backupList">
                    <?php if (empty($backupFiles)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📂</div>
                            <h3>No backup files found</h3>
                            <p>Create your first backup to get started.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($backupFiles as $file): ?>
                            <div class="backup-item">
                                <div class="backup-info">
                                    <strong><?= htmlspecialchars($file['filename']) ?></strong>
                                    <div class="file-details">
                                        Size: <?= formatFileSize($file['file_size']) ?> | 
                                        Created: <?= htmlspecialchars($file['created_at']) ?>
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <button onclick="downloadBackup('<?= htmlspecialchars($file['filename']) ?>')" class="btn restore-btn">
                                        📥 Download
                                    </button>
                                    <button onclick="deleteBackup('<?= htmlspecialchars($file['filename']) ?>')" class="btn btn-danger">
                                        🗑️ Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="messageBox" class="message-box"></div>

<script>
async function createBackup() {
    const backupBtn = document.getElementById('backupBtn');
    const resultDiv = document.getElementById('backupResult');
    
    backupBtn.disabled = true;
    backupBtn.innerHTML = '⏳ Creating Backup...';
    resultDiv.innerHTML = '<div class="loading">Creating backup, please wait. This may take a few minutes...</div>';
    
    try {
        const response = await fetch('likod/create_backup.php');
        const result = await response.json();
        
        if (result.success) {
            resultDiv.innerHTML = `
                <div class="message-box message-success">
                    ✅ <strong>Backup created successfully!</strong><br>
                    File: ${result.filename}<br>
                    Size: ${formatFileSize(result.file_size)}<br>
                    <small>Page will refresh in 2 seconds...</small>
                </div>
            `;
            setTimeout(() => location.reload(), 2000);
        } else {
            resultDiv.innerHTML = `
                <div class="message-box message-error">
                    ❌ <strong>Backup failed:</strong> ${result.error}
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="message-box message-error">
                ❌ <strong>Backup failed:</strong> ${error.message}
            </div>
        `;
    } finally {
        backupBtn.disabled = false;
        backupBtn.innerHTML = '💾 Create Backup Now';
    }
}

async function restoreBackup() {
    const backupFile = document.getElementById('backupFile').value;
    const restoreBtn = document.getElementById('restoreBtn');
    const resultDiv = document.getElementById('restoreResult');
    
    if (!backupFile) {
        resultDiv.innerHTML = `
            <div class="message-box message-error">
                ❌ Please select a backup file to restore.
            </div>
        `;
        return;
    }
    
    if (!confirm('⚠️ WARNING: This will overwrite ALL current data. This action cannot be undone. Are you absolutely sure you want to continue?')) {
        return;
    }
    
    if (!confirm('🚨 FINAL WARNING: This will DELETE all current data and replace it with the backup. This is your last chance to cancel.')) {
        return;
    }
    
    restoreBtn.disabled = true;
    restoreBtn.innerHTML = '⏳ Restoring...';
    resultDiv.innerHTML = '<div class="loading">Restoring backup, please wait. This may take a few minutes. Do not close this page.</div>';
    
    try {
        const response = await fetch('likod/restore_backup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `filename=${encodeURIComponent(backupFile)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            resultDiv.innerHTML = `
                <div class="message-box message-success">
                    ✅ <strong>Backup restored successfully!</strong><br>
                    The system will redirect to the dashboard in 3 seconds.
                </div>
            `;
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 3000);
        } else {
            resultDiv.innerHTML = `
                <div class="message-box message-error">
                    ❌ <strong>Restore failed:</strong> ${result.error}
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="message-box message-error">
                ❌ <strong>Restore failed:</strong> ${error.message}
            </div>
        `;
    } finally {
        restoreBtn.disabled = false;
        restoreBtn.innerHTML = '🔄 Restore Selected Backup';
    }
}

function downloadBackup(filename) {
    window.open(`likod/download_backup.php?filename=${encodeURIComponent(filename)}`, '_blank');
}

async function deleteBackup(filename) {
    if (!confirm('Are you sure you want to delete this backup file? This action cannot be undone.')) {
        return;
    }
    
    if (!confirm('This backup file will be permanently deleted. Are you absolutely sure?')) {
        return;
    }
    
    try {
        const response = await fetch('likod/delete_backup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `filename=${encodeURIComponent(filename)}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('✅ Backup file deleted successfully!');
            location.reload();
        } else {
            showMessage('❌ Failed to delete backup: ' + result.error, false);
        }
    } catch (error) {
        showMessage('❌ Failed to delete backup: ' + error.message, false);
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function showMessage(message, success = true) {
    const msgBox = document.getElementById('messageBox');
    msgBox.textContent = message;
    msgBox.className = success ? 'message-box message-success show' : 'message-box message-error show';
    setTimeout(() => msgBox.classList.remove('show'), 3000);
}
</script>
</body>
</html>

<?php
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>