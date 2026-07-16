<?php
require 'likod/session_utils.php';
enforce_role(['is_midwife', 'is_admin']);
require 'likod/backup_manager.php';

$backupManager = new BackupManager();
$backupFiles = $backupManager->getBackupFiles();

// Helper function from the original file (moved here for completeness)
function formatFileSize($bytes)
{
   if ($bytes == 0)
      return '0 Bytes';
   $k = 1024;
   $sizes = ['Bytes', 'KB', 'MB', 'GB'];
   $i = floor(log($bytes) / log($k));
   return number_format($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <title>Restore Backup - RHSYS</title>
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
               <a href="create_backup.php" class="btn backup-btn" style="margin-left: auto;">
                  📦 Go to Create Backup
               </a>
            </div>

            <h1>Restore Database Backup</h1>

            <div class="backup-section">
               <h2>🚨 System Restoration</h2>

               <div class="alert-card warning">
                  <strong>⚠️ CRITICAL WARNING:</strong> Restoring a backup will **PERMANENTLY OVERWRITE ALL CURRENT
                  DATA** in the database. This action cannot be undone.
                  <br>Please **ensure you have a recent backup** before proceeding.
               </div>

               <div class="form-container">
                  <div class="form-item">
                     <label for="backupFile" class="form-label">Select Backup File to Restore:</label>
                     <select id="backupFile" class="form-select">
                        <option value="">Select a backup file...</option>
                        <?php foreach ($backupFiles as $file): ?>
                           <option value="<?= htmlspecialchars($file['filename']) ?>">
                              <?= htmlspecialchars($file['filename']) ?> (<?= formatFileSize($file['file_size']) ?>) -
                              <?= htmlspecialchars($file['created_at']) ?>
                           </option>
                        <?php endforeach; ?>
                     </select>
                  </div>

                  <div class="action-buttons">
                     <button onclick="handleRestoreClick()" class="btn restore-btn" id="restoreBtn">
                        🔄 Restore Selected Backup
                     </button>
                  </div>

                  <div id="restoreResult"></div>
               </div>
            </div>

            <div class="backup-section">
               <h2>📁 Available Backups (<?= count($backupFiles); ?>)</h2>
               <p>Files listed below are available for restoration.</p>

               <div class="backup-list" id="backupList">
                  <?php if (empty($backupFiles)): ?>
                     <div class="empty-state">
                        <div class="empty-state-icon">📂</div>
                        <h3>No backup files found</h3>
                        <p>You cannot restore without a file. Please create one first.</p>
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
                              <button onclick="openPasswordModal('download', '<?= htmlspecialchars($file['filename']) ?>')"
                                 class="btn restore-btn">
                                 📥 Download
                              </button>
                              <button onclick="openPasswordModal('delete', '<?= htmlspecialchars($file['filename']) ?>')"
                                 class="btn btn-danger">
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

   <div id="passwordModal" class="modal"
      style="display:none; position:fixed; z-index:100; left:0; top:0; width:100%; height:100%; overflow:auto; background-color: rgba(0,0,0,0.4);">
      <div class="modal-content"
         style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 400px; border-radius: 8px;">
         <span class="close-btn" onclick="closeModal()"
            style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
         <h2>Confirm Your Identity</h2>
         <p>Please enter your account password to confirm this sensitive action.</p>
         <input type="password" id="modalPassword" placeholder="Enter Password" class="form-control"
            style="width: 95%; padding: 10px; margin: 8px 0; border: 1px solid #ccc; border-radius: 4px;">
         <div class="action-buttons" style="margin-top: 15px; display: flex; justify-content: space-between;">
            <button id="modalConfirmBtn" class="btn btn-danger" style="flex: 1; margin-right: 5px;">Confirm
               Action</button>
            <button id="modalCancelBtn" class="btn btn-secondary" onclick="closeModal()"
               style="flex: 1; margin-left: 5px;">Cancel</button>
         </div>
         <div id="modalError" class="message-box message-error"
            style="display:none; margin-top: 10px; padding: 10px; border: 1px solid #f5c6cb; background-color: #f8d7da; color: #721c24; border-radius: 4px;">
         </div>
      </div>
   </div>
   <script>
      // Global state to track what we're confirming
      let pendingAction = null;
      let pendingFilename = null;

      // --- MODAL CONTROL FUNCTIONS ---
      function openPasswordModal(action, filename = null) {
         pendingAction = action; // 'restore', 'delete', 'download'
         pendingFilename = filename;
         document.getElementById('modalPassword').value = ''; // Clear password field
         document.getElementById('modalError').style.display = 'none';
         document.getElementById('passwordModal').style.display = 'block';
         document.getElementById('modalPassword').focus(); // Focus on the input
      }

      function closeModal() {
         document.getElementById('passwordModal').style.display = 'none';
      }

      // Special handler for the restore button, which needs to check the dropdown first
      function handleRestoreClick() {
         const backupFile = document.getElementById('backupFile').value;
         const resultDiv = document.getElementById('restoreResult');

         if (!backupFile) {
            resultDiv.innerHTML = `
            <div class="message-box message-error">
                ❌ Please select a backup file to restore.
            </div>
        `;
            return;
         }
         // If a file is selected, open the password modal, passing the filename
         openPasswordModal('restore', backupFile);
      }

      // Attach the main confirm logic to the modal button
      document.getElementById('modalConfirmBtn').onclick = async () => {
         const password = document.getElementById('modalPassword').value;
         const errorDiv = document.getElementById('modalError');
         errorDiv.style.display = 'none';

         if (!password) {
            errorDiv.textContent = "Password cannot be empty.";
            errorDiv.style.display = 'block';
            return;
         }

         // Disable button while checking
         const confirmBtn = document.getElementById('modalConfirmBtn');
         confirmBtn.disabled = true;
         confirmBtn.innerHTML = 'Verifying...';

         const isPasswordValid = await verifyPassword(password);

         confirmBtn.disabled = false;
         confirmBtn.innerHTML = 'Confirm Action';

         if (!isPasswordValid) {
            errorDiv.textContent = "❌ Invalid password. Access denied.";
            errorDiv.style.display = 'block';
            return;
         }

         // Password verified! Execute the action based on the saved state.
         switch (pendingAction) {
            case 'restore':
               await executeRestoreBackup(pendingFilename);
               break;
            case 'delete':
               await executeDeleteBackup(pendingFilename);
               break;
            case 'download':
               await executeDownloadBackup(pendingFilename);
               break;
            default:
               showMessage('❌ Unknown action specified.', false);
         }

         // Only hide the modal and reset state *after* the action has been executed
         closeModal();
         pendingAction = null;
         pendingFilename = null;
      };

      // --- NEW BACKEND VERIFICATION FUNCTION (CRITICAL FOR SECURITY) ---
      async function verifyPassword(password) {
         try {
            const response = await fetch('likod/verify_password.php', {
               method: 'POST',
               headers: {
                  'Content-Type': 'application/json',
               },
               body: JSON.stringify({
                  password: password
               })
            });
            const result = await response.json();
            return result.success === true;
         } catch (e) {
            console.error("Password verification error:", e);
            return false;
         }
      }
      // --- END NEW FUNCTION ---

      function formatFileSize(bytes) {
         if (bytes === 0) return '0 Bytes';
         const k = 1024;
         const sizes = ['Bytes', 'KB', 'MB', 'GB'];
         const i = Math.floor(Math.log(bytes) / Math.log(k));
         return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
      }

      // --- EXECUTION FUNCTIONS (called AFTER password verification) ---

      // Renamed function, takes the filename from the global state
      async function executeRestoreBackup(backupFile) {
         const restoreBtn = document.getElementById('restoreBtn');
         const resultDiv = document.getElementById('restoreResult');

         // REMOVED multiple confirm dialogs since we have a password check now

         restoreBtn.disabled = true;
         restoreBtn.innerHTML = '⏳ Restoring...';
         resultDiv.innerHTML =
            '<div class="loading">Restoring backup, please wait. This may take a few minutes. Do not close this page.</div>';

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

      // NEW: Execution function for download
      function executeDownloadBackup(filename) {
         showMessage(`📥 Starting download of ${filename}...`);
         window.open(`likod/download_backup.php?filename=${encodeURIComponent(filename)}`, '_blank');
      }

      // Renamed function, takes the filename from the global state
      async function executeDeleteBackup(filename) {
         // No need for a separate JS confirm since password was entered

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

      function showMessage(message, success = true) {
         const msgBox = document.getElementById('messageBox');
         msgBox.textContent = message;
         msgBox.className = success ? 'message-box message-success show' : 'message-box message-error show';
         setTimeout(() => msgBox.classList.remove('show'), 3000);
      }
   </script>
</body>

</html>