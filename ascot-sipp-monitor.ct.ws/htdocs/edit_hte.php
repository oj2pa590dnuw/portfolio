<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['advisor_id']) || $_SESSION['role'] != 'superadmin') {
    header("Location: index.php");
    exit;
}

$hte_id = (int)$_GET['id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM host_training_establishment WHERE hte_id = ?");
mysqli_stmt_bind_param($stmt, "i", $hte_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$hte = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
if (!$hte) die("HTE not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <title>Edit HTE</title>
   <link rel="stylesheet" href="style.css">
   <style>
       .date-fields { transition: opacity 0.3s; }
       .date-fields.disabled { opacity: 0.5; pointer-events: none; }
       .current-file { background: #f8faf7; padding: 0.5rem; border-radius: 4px; margin-top: 0.25rem; }
   </style>
</head>
<body>
   <div class="container">
      <?php include 'navbar.php'; ?>
      <div class="card">
         <h2>Edit Host Training Establishment</h2>
         <form method="POST" action="save_hte.php" id="hteForm" class="superadmin-action" data-confirm="Update this HTE?" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="hte_id" value="<?= $hte['hte_id'] ?>">
            <div class="form-grid">
               <div class="form-group">
                  <label>HTE Name: <span style="color:red;">*</span></label>
                  <input type="text" name="hte_name" value="<?= htmlspecialchars($hte['hte_name']) ?>" required>
               </div>
               <div class="form-group">
                  <label>Representative: <span style="color:red;">*</span></label>
                  <input type="text" name="hte_representative" value="<?= htmlspecialchars($hte['hte_representative']) ?>" required>
               </div>
               <div class="form-group">
                  <label>Address:</label>
                  <input type="text" name="address" value="<?= htmlspecialchars($hte['address']) ?>">
               </div>
               <div class="form-group">
                  <label>Contact Number:</label>
                  <input type="text" name="contact_number" value="<?= htmlspecialchars($hte['contact_number']) ?>">
               </div>
               <div class="form-group">
                  <label>MOA Specific School:</label>
                  <select name="moa_specify">
                     <option value="ASCOT (Generalized)" <?= $hte['moa_specify'] == 'ASCOT (Generalized)' ? 'selected' : '' ?>>ASCOT (Generalized)</option>
                     <?php foreach ($schools as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $hte['moa_specify'] == $code ? 'selected' : '' ?>><?= $name ?></option>
                     <?php endforeach; ?>
                  </select>
               </div>
               <div class="form-group">
                  <label>MOA Document:</label>
                  <?php if (!empty($hte['moa_file'])): ?>
                     <div class="current-file">
                        Current file: <a href="uploads/moa/<?= urlencode($hte['moa_file']) ?>" target="_blank"><?= htmlspecialchars($hte['moa_file']) ?></a>
                     </div>
                  <?php endif; ?>
                  <input type="file" name="moa_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                  <small>Upload new file to replace. Max 10 MB.</small>
               </div>
               <div class="form-group">
                  <label>HTE Type:</label>
                  <select name="hte_type" id="hte_type" onchange="toggleDateFields()">
                     <option value="Local" <?= $hte['hte_type'] == 'Local' ? 'selected' : '' ?>>Local</option>
                     <option value="Private" <?= $hte['hte_type'] == 'Private' ? 'selected' : '' ?>>Private</option>
                     <option value="Public" <?= $hte['hte_type'] == 'Public' ? 'selected' : '' ?>>Public</option>
                  </select>
                  <small>Local HTEs are automatically active with eternal dates.</small>
               </div>
               <div id="dateFields" class="date-fields">
                  <div class="form-group">
                     <label>MOA Start Date:</label>
                     <input type="date" name="start_memo_of_agreement" id="start_date" value="<?= $hte['start_memo_of_agreement'] ?>">
                  </div>
                  <div class="form-group">
                     <label>MOA End Date:</label>
                     <input type="date" name="end_memo_of_agreement" id="end_date" value="<?= $hte['end_memo_of_agreement'] ?>">
                  </div>
               </div>
               <div class="form-group" id="activeField">
                  <label>Active MOA Flag:</label>
                  <select name="active_moa">
                     <option value="1" <?= $hte['active_moa'] ? 'selected' : '' ?>>Yes (Active)</option>
                     <option value="0" <?= !$hte['active_moa'] ? 'selected' : '' ?>>No (Inactive)</option>
                  </select>
               </div>
               <div class="form-group">
                  <label>Verified (Approved):</label>
                  <select name="verified">
                     <option value="1" <?= $hte['verified'] ? 'selected' : '' ?>>Yes</option>
                     <option value="0" <?= !$hte['verified'] ? 'selected' : '' ?>>No</option>
                  </select>
               </div>
            </div>
            <div style="margin-top: 1rem;">
               <button type="submit" class="btn">Update HTE</button>
               <a href="hte_list.php" class="btn btn-secondary">Cancel</a>
            </div>
         </form>
      </div>
   </div>
   <script>
       function toggleDateFields() {
           const hteType = document.getElementById('hte_type').value;
           const dateDiv = document.getElementById('dateFields');
           const activeSelect = document.querySelector('#activeField select');
           const startInput = document.getElementById('start_date');
           const endInput = document.getElementById('end_date');
           if (hteType === 'Local') {
               dateDiv.classList.add('disabled');
               startInput.disabled = true;
               endInput.disabled = true;
               startInput.value = '2000-01-01';
               endInput.value = '2099-12-31';
               activeSelect.value = '1';
               activeSelect.disabled = true;
           } else {
               dateDiv.classList.remove('disabled');
               startInput.disabled = false;
               endInput.disabled = false;
               activeSelect.disabled = false;
           }
       }
       window.addEventListener('DOMContentLoaded', toggleDateFields);
   </script>
   <?php include 'superadmin_modal.php'; ?>
</body>
</html>