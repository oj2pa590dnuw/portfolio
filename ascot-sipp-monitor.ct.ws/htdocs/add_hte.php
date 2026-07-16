<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['advisor_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <title>Add HTE</title>
   <link rel="stylesheet" href="style.css">
   <style>
       .date-fields { transition: opacity 0.3s; }
       .date-fields.disabled { opacity: 0.5; pointer-events: none; }
   </style>
</head>
<body>
   <div class="container">
      <?php include 'navbar.php'; ?>

      <div class="card">
         <h2>Add New Host Training Establishment</h2>
         <form method="POST" action="save_hte.php" id="hteForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="form-grid">
               <div class="form-group">
                  <label>HTE Name: <span style="color:red;">*</span></label>
                  <input type="text" name="hte_name" required>
               </div>
               <div class="form-group">
                  <label>Representative: <span style="color:red;">*</span></label>
                  <input type="text" name="hte_representative" required>
               </div>
               <div class="form-group">
                  <label>Address:</label>
                  <input type="text" name="address">
               </div>
               <div class="form-group">
                  <label>Contact Number:</label>
                  <input type="text" name="contact_number">
               </div>
               <div class="form-group">
                  <label>MOA Specific School:</label>
                  <select name="moa_specify">
                     <option value="ASCOT (Generalized)">ASCOT (Generalized)</option>
                     <?php foreach ($schools as $code => $name): ?>
                        <option value="<?= $code ?>"><?= $name ?></option>
                     <?php endforeach; ?>
                  </select>
               </div>
               <div class="form-group">
                  <label>MOA Document (Optional):</label>
                  <input type="file" name="moa_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                  <small>Upload scanned MOA (PDF, DOC, DOCX, JPG, PNG). Max 10 MB.</small>
               </div>
               <div class="form-group">
                  <label>HTE Type:</label>
                  <select name="hte_type" id="hte_type" onchange="toggleDateFields()">
                     <option value="Local">Local</option>
                     <option value="Private">Private</option>
                     <option value="Public">Public</option>
                  </select>
                  <small>Local HTEs are automatically active with eternal dates.</small>
               </div>
               <div id="dateFields" class="date-fields">
                  <div class="form-group">
                     <label>MOA Start Date:</label>
                     <input type="date" name="start_memo_of_agreement" id="start_date">
                  </div>
                  <div class="form-group">
                     <label>MOA End Date:</label>
                     <input type="date" name="end_memo_of_agreement" id="end_date">
                  </div>
               </div>
               <div class="form-group" id="activeField">
                  <label>Active MOA Flag:</label>
                  <select name="active_moa">
                     <option value="1">Yes (Active)</option>
                     <option value="0">No (Inactive)</option>
                  </select>
               </div>
               <?php if ($_SESSION['role'] == 'superadmin'): ?>
               <div class="form-group">
                  <label>Verified (Approved):</label>
                  <select name="verified">
                     <option value="1" selected>Yes</option>
                     <option value="0">No</option>
                  </select>
                  <small>Unverified HTEs are hidden from advisors until verified.</small>
               </div>
               <?php endif; ?>
            </div>
            <div style="margin-top: 1rem;">
               <button type="submit" class="btn">Save HTE</button>
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
               startInput.value = '';
               endInput.value = '';
               activeSelect.disabled = false;
           }
       }
       window.addEventListener('DOMContentLoaded', toggleDateFields);
   </script>
   <?php include 'superadmin_modal.php'; ?>
</body>
</html>