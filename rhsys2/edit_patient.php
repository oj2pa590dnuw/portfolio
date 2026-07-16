<?php
require 'likod/auth_guard.php';
require 'likod/session_utils.php';
enforce_role(['is_bhw', 'is_midwife', 'is_admin']);
require 'likod/activity_logger.php';
$logger = new ActivityLogger();
?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <title>Edit Patient</title>
   <script src="offline-helper.js"></script>
   <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
</head>

<body>
   <div class="app-container">
      <?php include 'navbar.php'; ?>

      <div class="main-content">
         <div class="large-container">
            <div class="nav-links">
               <a href="patients_list.php" class="btn btn-secondary">← Back to Patient List</a>
            </div>

            <h1 id="pageTitle">Edit Patient</h1>

            <div id="loading" class="loading">
               <img src="images/loading.gif" alt="Loading...">
            </div>

            <form id="editForm" class="form-container" style="display:none;">
               <input type="hidden" id="id" name="id">

               <div class="data-grid">
                  <!-- Personal Information -->
                  <div class="category-panel">
                     <h3>👤 Personal Information</h3>

                     <div class="form-item">
                        <label class="form-label required">Age</label>
                        <input type="number" id="age" name="age" class="form-input" min="0" max="150" required>
                     </div>
                     <div class="form-item">
                        <label class="form-label required">Location/Address</label>
                        <input type="text" id="location" name="location" class="form-input" required>
                     </div>
                     <div class="form-item">
                        <label class="form-label">Contact Number</label>
                        <input type="text" id="contactNumber" name="contactNumber" class="form-input">
                     </div>
                     <div class="form-item">
                        <label class="form-label">Birth Date</label>
                        <input type="date" id="birthDate" name="birthDate" class="form-input">
                     </div>
                  </div>

                  <!-- Identification -->
                  <div class="category-panel">
                     <h3>🆔 Identification</h3>
                     <div class="form-item">
                        <label class="form-label">Patient ID</label>
                        <input type="text" id="patientIdDisplay" class="form-input" readonly
                           style="background: #f8f9fa;">
                     </div>
                     <div class="form-item">
                        <label class="form-label">PhilHealth ID</label>
                        <input type="text" id="philhealth_id" name="philhealth_id" class="form-input">
                     </div>
                     <div class="form-item">
                        <label class="form-label">Local Patient ID</label>
                        <input type="text" id="local_patient_id" name="local_patient_id" class="form-input">
                     </div>
                  </div>

                  <!-- Vitals & Measurements -->
                  <div class="category-panel">
                     <h3>💓 Vitals & Measurements</h3>
                     <div class="form-item">
                        <label class="form-label">Blood Pressure</label>
                        <input type="text" id="bloodPressure" name="bloodPressure" class="form-input"
                           placeholder="120/80">
                     </div>
                     <div class="form-item">
                        <label class="form-label">Heart Rate</label>
                        <input type="text" id="heartRate" name="heartRate" class="form-input" placeholder="75 bpm">
                     </div>
                     <div class="form-item">
                        <label class="form-label">Respiratory Rate</label>
                        <input type="text" id="respiratoryRate" name="respiratoryRate" class="form-input"
                           placeholder="16 breaths/min">
                     </div>
                     <div class="form-item">
                        <label class="form-label">Temperature</label>
                        <input type="text" id="temperature" name="temperature" class="form-input" placeholder="36.5 °C">
                     </div>
                     <div class="form-item">
                        <label class="form-label">Weight</label>
                        <input type="text" id="weight" name="weight" class="form-input" placeholder="kg">
                     </div>
                     <div class="form-item">
                        <label class="form-label">Height</label>
                        <input type="text" id="height" name="height" class="form-input" placeholder="cm">
                     </div>
                  </div>

                  <!-- Medical Flags -->
                  <div class="category-panel">
                     <h3>🚩 Medical Status Flags</h3>
                     <div class="checkbox-grid">
                        <div class="checkbox-item">
                           <input type="checkbox" id="isPregnant" name="isPregnant" value="1">
                           <label for="isPregnant">🤰 Pregnant</label>
                        </div>
                        <div class="checkbox-item">
                           <input type="checkbox" id="isElderly" name="isElderly" value="1">
                           <label for="isElderly">👵 Elderly</label>
                        </div>
                        <div class="checkbox-item">
                           <input type="checkbox" id="hasHighBP" name="hasHighBP" value="1">
                           <label for="hasHighBP">🩺 High BP</label>
                        </div>
                        <div class="checkbox-item">
                           <input type="checkbox" id="needsMedication" name="needsMedication" value="1">
                           <label for="needsMedication">💊 Needs Meds</label>
                        </div>
                        <div class="checkbox-item">
                           <input type="checkbox" id="isCritical" name="isCritical" value="1">
                           <label for="isCritical">🚨 Critical</label>
                        </div>
                        <div class="checkbox-item">
                           <input type="checkbox" id="isWarningFlag" name="isWarningFlag" value="1">
                           <label for="isWarningFlag">⚠️ Warning Flag</label>
                        </div>
                        <div class="checkbox-item">
                           <input type="checkbox" id="isStable" name="isStable" value="1" checked>
                           <label for="isStable">✅ Stable</label>
                        </div>
                        <div class="checkbox-item">
                           <input type="checkbox" id="needsAppointment" name="needsAppointment" value="1">
                           <label for="needsAppointment">📅 Needs Follow-up</label>
                        </div>
                     </div>
                  </div>

                  <!-- Clinical Information -->
                  <div class="category-panel">
                     <h3>🏥 Clinical Information</h3>
                     <div class="form-item">
                        <label class="form-label">Case Title</label>
                        <input type="text" id="title" name="title" class="form-input">
                     </div>
                     <div class="form-item">
                        <label class="form-label">Last Checkup Date</label>
                        <input type="date" id="lastCheckup" name="lastCheckup" class="form-input">
                     </div>
                     <div class="form-item">
                        <label class="form-label">Checkup Time</label>
                        <input type="time" id="time" name="time" class="form-input">
                     </div>
                  </div>

                  <!-- Notes & Additional Info -->
                  <div class="category-panel">
                     <h3>📝 Notes & Additional Information</h3>
                     <div class="form-item">
                        <label class="form-label">Patient Description</label>
                        <textarea id="description" name="description" class="form-textarea" rows="3"></textarea>
                     </div>
                     <div class="form-item">
                        <label class="form-label">Clinical Notes</label>
                        <textarea id="clinicalNotes" name="clinicalNotes" class="form-textarea" rows="4"></textarea>
                     </div>
                     <div class="form-item">
                        <label class="form-label">Warning Details</label>
                        <input type="text" id="warning" name="warning" class="form-input">
                     </div>
                     <div class="form-item">
                        <label class="form-label">Other Information</label>
                        <textarea id="otherInfo" name="otherInfo" class="form-textarea" rows="3"></textarea>
                     </div>
                     <div class="form-item">
                        <label class="form-label">Normal Ranges Reference</label>
                        <textarea id="normalRanges" name="normalRanges" class="form-textarea" rows="2"></textarea>
                     </div>
                  </div>
               </div>

               <div class="action-buttons">
                  <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                  <a href="#" id="cancelBtn" class="btn btn-secondary">↩ Cancel</a>
               </div>
            </form>

            <div id="error" class="message-box message-error" style="display:none;"></div>
         </div>
      </div>
   </div>

   <div id="messageBox" class="message-box"></div>

   <script>
      const BASE_URL = 'likod/';
      let patientId = null;

      function showMessage(message, success = true) {
         const msgBox = document.getElementById('messageBox');
         msgBox.textContent = message;
         msgBox.className = success ? 'message-box message-success show' : 'message-box message-error show';
         setTimeout(() => msgBox.classList.remove('show'), 3000);
      }

      async function loadPatient() {
         const params = new URLSearchParams(window.location.search);
         patientId = params.get('id');

         if (!patientId) {
            document.getElementById('error').textContent = 'No patient ID provided';
            document.getElementById('error').style.display = 'block';
            document.getElementById('loading').style.display = 'none';
            return;
         }

         try {
            const res = await fetch(`${BASE_URL}get_single_patient.php?id=${patientId}`);
            const data = await res.json();

            if (data.error) throw new Error(data.error.message);

            // Fill form with patient data
            document.getElementById('id').value = data.id;
            document.getElementById('patientIdDisplay').value = data.id;
            //document.getElementById('fullName').value = data.fullName || '';
            document.getElementById('age').value = data.age || '';
            document.getElementById('location').value = data.location || '';
            document.getElementById('contactNumber').value = data.contactNumber || '';
            document.getElementById('birthDate').value = data.birthDate || '';
            document.getElementById('philhealth_id').value = data.philhealth_id || '';
            document.getElementById('local_patient_id').value = data.local_patient_id || '';
            document.getElementById('bloodPressure').value = data.bloodPressure || '';
            document.getElementById('heartRate').value = data.heartRate || '';
            document.getElementById('respiratoryRate').value = data.respiratoryRate || '';
            document.getElementById('temperature').value = data.temperature || '';
            document.getElementById('weight').value = data.weight || '';
            document.getElementById('height').value = data.height || '';
            document.getElementById('title').value = data.title || '';
            document.getElementById('lastCheckup').value = data.lastCheckup || '';
            document.getElementById('time').value = data.time || '';
            document.getElementById('description').value = data.description || '';
            document.getElementById('clinicalNotes').value = data.clinicalNotes || '';
            document.getElementById('warning').value = data.warning || '';
            document.getElementById('otherInfo').value = data.otherInfo || '';
            document.getElementById('normalRanges').value = data.normalRanges || '';

            // Checkboxes
            document.getElementById('isPregnant').checked = data.isPregnant == 1;
            document.getElementById('isElderly').checked = data.isElderly == 1;
            document.getElementById('hasHighBP').checked = data.hasHighBP == 1;
            document.getElementById('needsMedication').checked = data.needsMedication == 1;
            document.getElementById('isCritical').checked = data.isCritical == 1;
            document.getElementById('isWarningFlag').checked = data.isWarningFlag == 1;
            document.getElementById('isStable').checked = data.isStable == 1;
            document.getElementById('needsAppointment').checked = data.needsAppointment == 1;

            document.getElementById('pageTitle').textContent = `Edit Patient: ${data.fullName}`;
            document.getElementById('loading').style.display = 'none';
            document.getElementById('editForm').style.display = 'block';

         } catch (err) {
            document.getElementById('error').textContent = 'Error loading patient: ' + err.message;
            document.getElementById('error').style.display = 'block';
            document.getElementById('loading').style.display = 'none';
            console.error(err);
         }
      }

      document.getElementById('editForm').onsubmit = async (e) => {
         e.preventDefault();

         const formData = new FormData(e.target);
         const data = {};

         // Get all form values
         for (let [key, value] of formData.entries()) {
            data[key] = value;
         }

         // Convert checkboxes to 1/0
         const booleanFields = [
            'isPregnant', 'isElderly', 'hasHighBP', 'needsMedication',
            'isCritical', 'isWarningFlag', 'isStable', 'needsAppointment'
         ];

         booleanFields.forEach(key => {
            data[key] = document.getElementById(key).checked ? '1' : '0';
         });

         try {
            const res = await fetch(`${BASE_URL}update_patient.php`, {
               method: 'POST',
               headers: {
                  'Content-Type': 'application/json'
               },
               body: JSON.stringify(data)
            });

            const result = await res.json();

            if (result.success) {
               showMessage('✅ Patient updated successfully!');
               setTimeout(() => {
                  window.location.href = `view_patient.php?id=${patientId}`;
               }, 1000);
            } else {
               throw new Error(result.message || 'Failed to update patient');
            }
         } catch (err) {
            showMessage('❌ Error: ' + err.message, false);
            console.error(err);
         }
      };

      // Cancel button handler
      document.getElementById('cancelBtn').addEventListener('click', (e) => {
         e.preventDefault();
         if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
            window.location.href = `view_patient.php?id=${patientId}`;
         }
      });

      loadPatient();
   </script>
</body>

</html>