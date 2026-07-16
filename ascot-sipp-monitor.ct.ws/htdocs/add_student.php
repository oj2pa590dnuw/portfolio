<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['advisor_id'])) {
    header("Location: login.php");
    exit;
}

$is_superadmin = ($_SESSION['role'] == 'superadmin');
$advisor_department = $_SESSION['department'];

$pre_selected_hte = isset($_GET['hte_id']) ? (int) $_GET['hte_id'] : 0;
$current_year = (int) date('Y');
$default_academic_year = $current_year;

// Fetch HTEs for the autocomplete list
if ($is_superadmin) {
    $sql_hte = "SELECT hte_id, hte_name FROM host_training_establishment ORDER BY hte_name";
    $stmt_hte = mysqli_prepare($conn, $sql_hte);
} else {
    $sql_hte = "SELECT hte_id, hte_name FROM host_training_establishment 
                WHERE moa_specify = 'ASCOT (Generalized)' OR moa_specify = ? 
                ORDER BY hte_name";
    $stmt_hte = mysqli_prepare($conn, $sql_hte);
    mysqli_stmt_bind_param($stmt_hte, "s", $advisor_department);
}
mysqli_stmt_execute($stmt_hte);
$htes_result = mysqli_stmt_get_result($stmt_hte);
$hte_list = [];
while ($row = mysqli_fetch_assoc($htes_result)) {
    $hte_list[] = $row;
}

// Fetch advisors for superadmin
$advisors = [];
if ($is_superadmin) {
    $adv_sql = "SELECT advisor_id, advisor_name, department FROM users WHERE role = 'advisor' ORDER BY advisor_name";
    $adv_stmt = mysqli_prepare($conn, $adv_sql);
    mysqli_stmt_execute($adv_stmt);
    $adv_res = mysqli_stmt_get_result($adv_stmt);
    while ($row = mysqli_fetch_assoc($adv_res)) {
        $advisors[] = $row;
    }
    mysqli_stmt_close($adv_stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>Batch Add Students</title>
   <link rel="stylesheet" href="style.css">
</head>
<body>
   <div class="container">
      <?php include 'navbar.php'; ?>

      <div class="card">
         <div class="page-header">
            <div class="page-icon">+</div>
            <h2>Batch Add Students</h2>
         </div>

         <form method="POST" action="save_student.php" id="batch-form">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <div class="form-group">
               <label>Host Training Establishment</label>
               <div class="hte-autocomplete-container">
                  <input type="text" id="hte-display-input" class="search-input" placeholder="Click or type to search HTE..." autocomplete="off" required>
                  <input type="hidden" name="hte_id" id="hte-id-hidden" value="<?= $pre_selected_hte ?>">
                  <div id="hte-results-list" class="autocomplete-results"></div>
               </div>
               <span class="hte-hint">Search and select the establishment where these students will be deployed.</span>
            </div>

            <div class="settings-panel">
               <div class="settings-panel-title">Batch Settings — applies to all students</div>
               <div class="form-grid">
                  <div class="form-group">
                     <label>School</label>
                     <?php if ($is_superadmin): ?>
                        <select id="global-school" name="batch_school" onchange="updateGlobalPrograms(); updateAdvisorFilter();" required>
                           <option value="">— Select School —</option>
                           <?php foreach ($schools as $code => $name): ?>
                              <option value="<?= $code ?>"><?= $name ?></option>
                           <?php endforeach; ?>
                        </select>
                     <?php else: ?>
                        <input type="text" value="<?= htmlspecialchars($schools[$advisor_department] ?? $advisor_department) ?>" disabled>
                        <input type="hidden" name="batch_school" value="<?= $advisor_department ?>">
                     <?php endif; ?>
                  </div>
                  <div class="form-group">
                     <label>Program</label>
                     <select id="global-program" name="batch_program" required>
                        <option value="">— Select Program —</option>
                     </select>
                  </div>
                  <div class="form-group">
                     <label>Academic Year</label>
                     <select name="batch_academic_year" id="global-ay" required>
                        <?php for ($i = -5; $i <= 5; $i++):
                           $start = $current_year + $i;
                           $sel = ($start == $default_academic_year) ? 'selected' : '';
                        ?>
                           <option value="<?= $start ?>" <?= $sel ?>><?= $start . '-' . ($start+1) ?></option>
                        <?php endfor; ?>
                     </select>
                  </div>
                  <div class="form-group">
                     <label>Semester</label>
                     <select name="batch_semester" required>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Midyear">Midyear</option>
                     </select>
                  </div>
                  <?php if ($is_superadmin): ?>
                  <div class="form-group">
                     <label>Advisor</label>
                     <select name="batch_advisor_id" id="global-advisor" required>
                        <option value="">— Select Advisor —</option>
                        <?php foreach ($advisors as $adv): ?>
                           <?php
                           $dept_label = isset($schools[$adv['department']]) ? $schools[$adv['department']] : $adv['department'];
                           ?>
                           <option value="<?= $adv['advisor_id'] ?>" data-department="<?= $adv['department'] ?>">
                              <?= htmlspecialchars($adv['advisor_name'] . ' (' . $dept_label . ')') ?>
                           </option>
                        <?php endforeach; ?>
                     </select>
                  </div>
                  <?php endif; ?>
                  <div class="form-group">
                     <label>Internship Start Date</label>
                     <input type="date" name="batch_internship_start">
                  </div>
                  <div class="form-group">
                     <label>Internship End Date</label>
                     <input type="date" name="batch_internship_end">
                  </div>
               </div>
            </div>

            <div class="table-actions-bar">
               <button type="button" class="add-row-btn" onclick="addRow()">+ Add Student</button>
            </div>

            <div class="students-table-wrap">
               <table id="students-table">
                  <thead>
                     <tr>
                        <th class="col-name">Student Name</th>
                        <th class="col-gender">Gender</th>
                        <th class="col-notes">Notes</th>
                        <?php if ($is_superadmin): ?>
                           <th class="col-check">RF</th><th class="col-check">MC</th><th class="col-check">PT</th>
                           <th class="col-check">PC</th><th class="col-check">TA</th><th class="col-check">SIPP</th>
                        <?php endif; ?>
                        <th class="col-action"></th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td><input type="text" name="students[0][student_name]" placeholder="Full name" required></td>
                        <td>
                           <select name="students[0][gender]" required>
                              <option value="Male">Male</option>
                              <option value="Female">Female</option>
                           </select>
                        </td>
                        <td><textarea name="students[0][extra_notes]" rows="1" placeholder="Optional…"></textarea></td>
                        <?php if ($is_superadmin): ?>
                           <td><input type="checkbox" name="students[0][has_registration_form]" value="1"></td>
                           <td><input type="checkbox" name="students[0][has_medical_certificate]" value="1"></td>
                           <td><input type="checkbox" name="students[0][has_psycho_test]" value="1"></td>
                           <td><input type="checkbox" name="students[0][has_notary_p_c]" value="1"></td>
                           <td><input type="checkbox" name="students[0][has_notary_t_a]" value="1"></td>
                           <td><input type="checkbox" name="students[0][has_sipp]" value="1"></td>
                        <?php endif; ?>
                        <td><button type="button" class="remove-btn" onclick="removeRow(this)">✕</button></td>
                     </tr>
                  </tbody>
               </table>
            </div>

            <div class="form-actions">
               <button type="submit" class="btn">Save All Students</button>
               <a href="index.php" class="btn btn-secondary">Cancel</a>
               <span class="row-count-badge"><span id="row-count">1</span> student(s)</span>
            </div>
         </form>
      </div>
   </div>

   <script>
      const hteData = <?= json_encode($hte_list, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      const displayInput = document.getElementById('hte-display-input');
      const hiddenInput = document.getElementById('hte-id-hidden');
      const resultsDiv = document.getElementById('hte-results-list');

      if (hiddenInput.value !== "0") {
         const found = hteData.find(h => h.hte_id == hiddenInput.value);
         if (found) displayInput.value = found.hte_name;
      }

      function renderResults(filter = '') {
         resultsDiv.innerHTML = '';
         const matches = hteData.filter(h => h.hte_name.toLowerCase().includes(filter.toLowerCase()));
         if (matches.length > 0) {
            resultsDiv.style.display = 'block';
            displayInput.classList.add('active');
            matches.forEach(m => {
               const item = document.createElement('div');
               item.className = 'result-item';
               item.textContent = m.hte_name;
               item.onclick = () => {
                  displayInput.value = m.hte_name;
                  hiddenInput.value = m.hte_id;
                  resultsDiv.style.display = 'none';
                  displayInput.classList.remove('active');
               };
               resultsDiv.appendChild(item);
            });
         } else {
            resultsDiv.style.display = 'none';
         }
      }

      displayInput.addEventListener('input', function() { renderResults(this.value); });
      displayInput.addEventListener('focus', function() { renderResults(this.value); });
      document.addEventListener('click', (e) => {
         if (!e.target.closest('.hte-autocomplete-container')) {
            resultsDiv.style.display = 'none';
            displayInput.classList.remove('active');
         }
      });

      const programsBySchool = <?= json_encode($programs_by_school, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      const isSuperadmin = <?= json_encode($is_superadmin) ?>;
      const advisorDept = <?= json_encode($advisor_department, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

      function updateGlobalPrograms() {
         const schoolSelect = document.getElementById('global-school');
         const school = isSuperadmin ? (schoolSelect ? schoolSelect.value : null) : advisorDept;
         const progSelect = document.getElementById('global-program');
         progSelect.innerHTML = '<option value="">— Select Program —</option>';
         if (school && programsBySchool[school]) {
            programsBySchool[school].forEach(p => {
               const opt = document.createElement('option');
               opt.value = opt.text = p;
               progSelect.appendChild(opt);
            });
         }
      }

      function updateAdvisorFilter() {
         if (!isSuperadmin) return;
         const schoolSelect = document.getElementById('global-school');
         const advisorSelect = document.getElementById('global-advisor');
         if (!schoolSelect || !advisorSelect) return;

         const selectedSchool = schoolSelect.value;
         const options = advisorSelect.querySelectorAll('option');
         let firstMatched = null;

         // Show only advisors whose data-department matches the selected school
         options.forEach(opt => {
            if (opt.value === '') return; // skip placeholder
            const dept = opt.getAttribute('data-department');
            if (selectedSchool === '' || dept === selectedSchool) {
               opt.style.display = '';
               if (!firstMatched) firstMatched = opt;
            } else {
               opt.style.display = 'none';
            }
         });

         // Auto-select the first matching advisor
         advisorSelect.value = firstMatched ? firstMatched.value : '';
      }

      // Initialise on page load
      window.onload = () => {
         updateGlobalPrograms();
         if (isSuperadmin) updateAdvisorFilter();
      };

      let rowCount = 1;
      function addRow() {
         const tbody = document.querySelector('#students-table tbody');
         const tr = document.createElement('tr');
         let html = `<td><input type="text" name="students[${rowCount}][student_name]" placeholder="Full name" required></td>
                     <td><select name="students[${rowCount}][gender]" required>
                           <option value="Male">Male</option>
                           <option value="Female">Female</option>
                        </select></td>
                     <td><textarea name="students[${rowCount}][extra_notes]" rows="1" placeholder="Optional…"></textarea></td>`;
         <?php if ($is_superadmin): ?>
         html += `<td><input type="checkbox" name="students[${rowCount}][has_registration_form]" value="1"></td>
                  <td><input type="checkbox" name="students[${rowCount}][has_medical_certificate]" value="1"></td>
                  <td><input type="checkbox" name="students[${rowCount}][has_psycho_test]" value="1"></td>
                  <td><input type="checkbox" name="students[${rowCount}][has_notary_p_c]" value="1"></td>
                  <td><input type="checkbox" name="students[${rowCount}][has_notary_t_a]" value="1"></td>
                  <td><input type="checkbox" name="students[${rowCount}][has_sipp]" value="1"></td>`;
         <?php endif; ?>
         html += `<td><button type="button" class="remove-btn" onclick="removeRow(this)">✕</button></td>`;
         tr.innerHTML = html;
         tbody.appendChild(tr);
         rowCount++;
         document.getElementById('row-count').textContent = document.querySelectorAll('#students-table tbody tr').length;
      }

      function removeRow(btn) {
         if (document.querySelectorAll('#students-table tbody tr').length > 1) {
            btn.closest('tr').remove();
            document.getElementById('row-count').textContent = document.querySelectorAll('#students-table tbody tr').length;
         }
      }
   </script>
   <?php include 'superadmin_modal.php'; ?>
</body>
</html>