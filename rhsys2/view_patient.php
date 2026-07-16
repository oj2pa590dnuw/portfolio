<?php
require 'likod/auth_guard.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <title>Patient Profile</title>
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

            <h1 id="patientNameHeader">Loading Patient...</h1>

            <div class="nav-links">
               <button class="btn btn-primary" id="addVisitBtn">➕ Add Quick Visit</button>
               <button class="btn btn-secondary" id="editBtn">✏️ Edit Patient</button>
               <button class="btn btn-danger" id="deleteBtn">🗑️ Delete Patient</button>
               <button class="btn export-btn" id="exportDocxBtn">📄 Export to DOCX</button>
               <button class="btn btn-secondary" id="addRelativeBtn">👨‍👩‍👧‍👦 Add Family Member</button>
            </div>

            <div class="tab-container">
               <div class="tab-buttons">
                  <button class="tab-button active" data-tab="patient-info">Patient Information</button>
                  <button class="tab-button" data-tab="visit-history">Visit History</button>
                  <button class="tab-button" data-tab="family-history">Family History</button>
               </div>

               <!-- Patient Information Tab -->
               <div id="patient-info" class="tab-content active">
                  <div id="patientProfile" style="display: none;">
                     <div class="data-grid">

                        <!-- Personal Information -->
                        <div class="category-panel">
                           <h3>👤 Personal Information</h3>
                           <div id="personalInfo" class="data-list">
                              <!-- Will be populated by JavaScript -->
                           </div>
                        </div>

                        <!-- Identification -->
                        <div class="category-panel">
                           <h3>🆔 Identification</h3>
                           <div id="identificationInfo" class="data-list">
                              <!-- Will be populated by JavaScript -->
                           </div>
                        </div>

                        <!-- Clinical Status -->
                        <div class="category-panel">
                           <h3>🏥 Clinical Status</h3>
                           <div id="clinicalStatus" class="data-list">
                              <!-- Will be populated by JavaScript -->
                           </div>
                        </div>

                        <!-- Vitals & Measurements -->
                        <div class="category-panel">
                           <h3>💓 Vitals & Measurements</h3>
                           <div id="vitalsInfo" class="data-list">
                              <!-- Will be populated by JavaScript -->
                           </div>
                        </div>

                        <!-- Medical Flags -->
                        <div class="category-panel">
                           <h3>🚩 Medical Flags</h3>
                           <div id="medicalFlags" class="data-list">
                              <!-- Will be populated by JavaScript -->
                           </div>
                        </div>

                        <!-- Notes & Additional Info -->
                        <div class="category-panel">
                           <h3>📝 Notes & Additional Information</h3>
                           <div id="notesInfo" class="data-list">
                              <!-- Will be populated by JavaScript -->
                           </div>
                        </div>

                     </div>
                  </div>
               </div>

               <!-- Visit History Tab -->
               <div id="visit-history" class="tab-content">
                  <div class="card">
                     <h2>Visit History</h2>
                     <div id="visitsList">
                        <p class="empty-state">No previous visits found for this patient.</p>
                     </div>
                  </div>
               </div>

               <!-- Family History Tab -->
               <div id="family-history" class="tab-content">
                  <div class="card">
                     <h2>Family Members & Relationships</h2>
                     <div id="familyMembersList">
                        <p class="empty-state">No family members found for this patient.</p>
                     </div>
                  </div>
               </div>
            </div>

            <div id="loading" class="loading">
               <img src="images/loading.gif" alt="Loading...">
            </div>
            <div id="errorMessage" class="message-box message-error" style="display: none;"></div>
         </div>
      </div>
   </div>

   <!-- Add Visit Modal -->
   <div id="visitModal" class="modal">
      <div class="modal-content">
         <span class="close-btn" onclick="closeModal('visitModal')">&times;</span>
         <h2>Record New Visit</h2>
         <p>Patient: <strong id="modalPatientName"></strong></p>
         <form id="visitForm" class="form-container">
            <input type="hidden" name="patient_id" id="modalPatientId">
            <div class="form-item">
               <label for="visit_date" class="form-label">Visit Date</label>
               <input type="date" id="visit_date" name="visit_date" class="form-input"
                  value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-item">
               <label for="chief_complaint" class="form-label">Chief Complaint / Title</label>
               <input type="text" id="chief_complaint" name="chief_complaint" class="form-input"
                  placeholder="e.g., Fever and cough" required>
            </div>
            <div class="form-grid">
               <div class="form-item">
                  <label for="blood_pressure" class="form-label">Blood Pressure</label>
                  <input type="text" id="blood_pressure" name="blood_pressure" class="form-input"
                     placeholder="e.g., 120/80">
               </div>
               <div class="form-item">
                  <label for="heart_rate" class="form-label">Heart Rate (bpm)</label>
                  <input type="number" id="heart_rate" name="heart_rate" class="form-input" placeholder="e.g., 75">
               </div>
               <div class="form-item">
                  <label for="temperature" class="form-label">Temperature (°C)</label>
                  <input type="number" step="0.1" id="temperature" name="temperature" class="form-input"
                     placeholder="e.g., 36.5">
               </div>
            </div>
            <div class="form-item">
               <label for="clinical_notes" class="form-label">Clinical Notes</label>
               <textarea id="clinical_notes" name="clinical_notes" class="form-textarea" rows="4"
                  placeholder="Examination findings"></textarea>
            </div>
            <div class="form-item">
               <label for="procedures_done" class="form-label">Procedures / Labs Done</label>
               <textarea id="procedures_done" name="procedures_done" class="form-textarea" rows="2"
                  placeholder="e.g., Blood test"></textarea>
            </div>
            <div class="action-buttons">
               <button type="submit" class="btn btn-primary">Save Visit</button>
            </div>
         </form>
      </div>
   </div>

   <!-- Add Family Member Modal -->
   <div id="familyModal" class="modal">
      <div class="modal-content">
         <span class="close-btn" onclick="closeModal('familyModal')">&times;</span>
         <h2>Add Family Member</h2>
         <form id="familyForm" class="form-container">
            <input type="hidden" name="patient_id" id="familyPatientId">

            <div class="form-item">
               <label for="relationship_type" class="form-label">Relationship Type *</label>
               <select id="relationship_type" name="relationship_type" class="form-select" required>
                  <option value="">Select Relationship</option>
                  <option value="Parent">Parent</option>
                  <option value="Child">Child</option>
                  <option value="Spouse">Spouse</option>
                  <option value="Sibling">Sibling</option>
                  <option value="Grandparent">Grandparent</option>
                  <option value="Grandchild">Grandchild</option>
                  <option value="Other">Other Relative</option>
               </select>
            </div>

            <div class="form-item">
               <label for="relative_patient_id" class="form-label">Select Existing Patient *</label>
               <select id="relative_patient_id" name="relative_patient_id" class="form-select" required>
                  <option value="">Loading patients...</option>
               </select>
            </div>

            <div class="action-buttons">
               <button type="submit" class="btn btn-primary">Add Family Member</button>
            </div>
         </form>
      </div>
   </div>

   <div id="messageBox" class="message-box"></div>

   <script src="https://unpkg.com/docx@7.8.2/build/index.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

   <script>
   const BASE_URL = 'likod/';
   let currentPatientId = null;
   let currentPatientData = null;
   let currentPatientVisits = [];
   let currentFamilyMembers = [];

   function showMessage(message, success = true) {
      const msgBox = document.getElementById('messageBox');
      msgBox.textContent = message;
      msgBox.className = success ? 'message-box message-success show' : 'message-box message-error show';
      setTimeout(() => msgBox.classList.remove('show'), 3000);
   }

   function closeModal(modalId) {
      document.getElementById(modalId).style.display = 'none';
   }

   function openModal(modalId) {
      document.getElementById(modalId).style.display = 'flex';
   }

   // Enhanced data rendering functions
   function createDataItem(label, value, isBoolean = false, isCritical = false) {
      const displayValue = value === null || value === '' || value === undefined ?
         '<span class="empty-value">Not specified</span>' :
         String(value);

      let valueClass = '';
      if (isBoolean) {
         valueClass = value === '1' || value === 1 ? 'boolean-true' : 'boolean-false';
      }
      if (isCritical) {
         valueClass = 'critical';
      }

      return `
        <div class="data-item">
            <div class="data-label">${label}</div>
            <div class="data-value ${valueClass}">${displayValue}</div>
        </div>
    `;
   }

   function createBooleanBadge(value, trueText = "Yes", falseText = "No") {
      const isTrue = value === '1' || value === 1;
      const badgeClass = isTrue ? 'badge-success' : 'badge-secondary';
      const text = isTrue ? trueText : falseText;
      return `<span class="badge ${badgeClass}">${text}</span>`;
   }

   function renderPatientDataComprehensive(patient) {
      currentPatientData = patient;

      const nameParts = [patient.fullName, patient.middle_name, patient.last_name];
      // This filters out any empty/null values and joins the rest with a single space.
      const fullHeaderName = nameParts.filter(Boolean).join(' ');

      document.getElementById('patientNameHeader').textContent = fullHeaderName || 'Patient Profile';
      // ----------------------------------------------------
      // Personal Information
      document.getElementById('personalInfo').innerHTML = `
        ${createDataItem('First Name', patient.fullName)}
        ${createDataItem('Middle Name', patient.middle_name)}
        ${createDataItem('Last Name', patient.last_name)}
        ${createDataItem('Age', patient.age)}
        ${createDataItem('Birth Date', patient.birthDate)}
        ${createDataItem('Contact Number', patient.contactNumber)}
        ${createDataItem('Location/Address', patient.location)}
        ${createDataItem('Registered By User ID', patient.registered_by_user_id)}
    `;

      // Identification
      document.getElementById('identificationInfo').innerHTML = `
        ${createDataItem('Patient ID', patient.id)}
        ${createDataItem('Patient Code', patient.patient_code)}
        ${createDataItem('PhilHealth ID', patient.philhealth_id)}
        ${createDataItem('Local Patient ID', patient.local_patient_id)}
    `;

      // Clinical Status
      document.getElementById('clinicalStatus').innerHTML = `
        ${createDataItem('Last Checkup Date', patient.lastCheckup)}
        ${createDataItem('Chief Complaint/Title', patient.title)}
        ${createDataItem('Warning', patient.warning)}
        ${createDataItem('Clinical Notes', patient.clinicalNotes)}
    `;

      // Vitals & Measurements
      document.getElementById('vitalsInfo').innerHTML = `
        ${createDataItem('Blood Pressure', patient.bloodPressure)}
        ${createDataItem('Heart Rate', patient.heartRate + (patient.heartRate ? ' bpm' : ''))}
        ${createDataItem('Respiratory Rate', patient.respiratoryRate + (patient.respiratoryRate ? ' breaths/min' : ''))}
        ${createDataItem('Temperature', patient.temperature + (patient.temperature ? ' °C' : ''))}
        ${createDataItem('Weight', patient.weight + (patient.weight ? ' kg' : ''))}
        ${createDataItem('Height', patient.height + (patient.height ? ' cm' : ''))}
        ${createDataItem('Checkup Time', patient.time)}
        ${createDataItem('Normal Ranges Reference', patient.normalRanges)}
    `;

      // Medical Flags
      document.getElementById('medicalFlags').innerHTML = `
        <div class="data-item">
            <div class="data-label">Critical Status</div>
            <div class="data-value">${createBooleanBadge(patient.isCritical, '🚨 CRITICAL', 'Stable')}</div>
        </div>
        <div class="data-item">
            <div class="data-label">Pregnancy Status</div>
            <div class="data-value">${createBooleanBadge(patient.isPregnant, '🤰 Pregnant', 'Not Pregnant')}</div>
        </div>
        <div class="data-item">
            <div class="data-label">Elderly Patient</div>
            <div class="data-value">${createBooleanBadge(patient.isElderly, '👵 Elderly', 'Not Elderly')}</div>
        </div>
        <div class="data-item">
            <div class="data-label">Warning Flag</div>
            <div class="data-value">${createBooleanBadge(patient.isWarningFlag, '⚠️ Active', 'Inactive')}</div>
        </div>
        <div class="data-item">
            <div class="data-label">Stability</div>
            <div class="data-value">${createBooleanBadge(patient.isStable, '✅ Stable', 'Unstable')}</div>
        </div>
        <div class="data-item">
            <div class="data-label">High Blood Pressure</div>
            <div class="data-value">${createBooleanBadge(patient.hasHighBP, '🩺 Has High BP', 'Normal BP')}</div>
        </div>
        <div class="data-item">
            <div class="data-label">Needs Medication</div>
            <div class="data-value">${createBooleanBadge(patient.needsMedication, '💊 Needs Meds', 'No Meds Needed')}</div>
        </div>
        <div class="data-item">
            <div class="data-label">Needs Appointment</div>
            <div class="data-value">${createBooleanBadge(patient.needsAppointment, '📅 Needs Follow-up', 'No Follow-up Needed')}</div>
        </div>
    `;

      // Notes & Additional Info
      document.getElementById('notesInfo').innerHTML = `
        ${createDataItem('Patient Description', patient.description)}
        ${createDataItem('Other Information', patient.otherInfo)}
    `;

      document.getElementById('patientProfile').style.display = 'block';
      document.getElementById('loading').style.display = 'none';
   }

   // RENDER VISITS FUNCTION
   function renderVisits(visits) {
      const container = document.getElementById('visitsList');
      container.innerHTML = '';
      currentPatientVisits = visits;

      if (visits.length === 0) {
         container.innerHTML = '<p class="empty-state">No previous visits found for this patient.</p>';
         return;
      }

      visits.forEach(visit => {
         const date = new Date(visit.visit_date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
         });

         const item = document.createElement('div');
         item.className = 'card visit-item';
         item.innerHTML = `
            <h4>${date} ${visit.visit_time ? '• ' + visit.visit_time : ''} | ${visit.chief_complaint || 'No Complaint'}</h4>
            <p><strong>Vitals:</strong> BP ${visit.blood_pressure || 'N/A'} | HR ${visit.heart_rate || 'N/A'} | Temp ${visit.temperature || 'N/A'}</p>
            <p><strong>Notes:</strong> ${visit.clinical_notes || 'No notes.'}</p>
            <p><strong>Procedures:</strong> ${visit.procedures_done || 'None'}</p>
            <p style="text-align: right; font-size: 0.8em; color: #777;">Attended by: User ${visit.attended_by_user_id || 'N/A'}</p>
        `;
         container.appendChild(item);
      });
   }

   function renderFamilyMembers(data) {
      const container = document.getElementById('familyMembersList');
      const similarComplaints = data.similarComplaints || [];
      const otherMembers = data.otherMembers || [];

      currentFamilyMembers = [...similarComplaints, ...otherMembers];

      if (currentFamilyMembers.length === 0) {
         container.innerHTML = '<p class="empty-state">No family members found for this patient.</p>';
         return;
      }

      let html = '';

      // Show similar complaints section if any exist
      if (similarComplaints.length > 0) {
         html += `
            <div class="similar-complaints-section">
                <div class="section-header">
                    <h3>👥 Family Members with Similar Medical Issues</h3>
                    <p class="section-subtitle">These family members have medical complaints similar to the patient's "<strong>${data.currentPatientComplaint || 'No complaint recorded'}</strong>"</p>
                </div>
                <div class="similar-complaints-grid">
        `;

         similarComplaints.forEach(member => {
            html += createFamilyMemberCard(member);
         });

         html += `
                </div>
            </div>
        `;
      }

      // Show other family members section
      if (otherMembers.length > 0) {
         html += `
            <div class="other-members-section">
                <div class="section-header">
                    <h3>${similarComplaints.length > 0 ? 'Other Family Members' : 'Family Members'}</h3>
                </div>
                <div class="family-members-grid">
        `;

         otherMembers.forEach(member => {
            html += createFamilyMemberCard(member);
         });

         html += `
                </div>
            </div>
        `;
      }

      container.innerHTML = html;
   }

   // Helper function to create individual family member cards
   function createFamilyMemberCard(member) {
      return `
        <div class="card family-member-card">
            <div class="card-header">
                <h4>${member.fullName}</h4>
                <span class="status-badge">${member.relationship_type}</span>
            </div>
            <div class="card-content">
                <div class="data-grid">
                    <div class="data-item">
                        <div class="data-label">Age:</div>
                        <div class="data-value">${member.age || 'N/A'}</div>
                    </div>
                    <div class="data-item">
                        <div class="data-label">Birth Date:</div>
                        <div class="data-value">${member.birthDate || 'N/A'}</div>
                    </div>
                    <div class="data-item">
                        <div class="data-label">Contact:</div>
                        <div class="data-value">${member.contactNumber || 'N/A'}</div>
                    </div>
                    <div class="data-item">
                        <div class="data-label">Location:</div>
                        <div class="data-value">${member.location || 'N/A'}</div>
                    </div>
                    ${member.chief_complaint ? `
                    <div class="data-item">
                        <div class="data-label">Chief Complaint:</div>
                        <div class="data-value"><strong>${member.chief_complaint}</strong></div>
                    </div>
                    ` : ''}
                </div>
                <div class="action-buttons">
                    <button onclick="viewFamilyMember('${member.related_patient_id}')" class="btn btn-secondary">View Profile</button>
                    <button onclick="removeFamilyMember(${member.relationship_id})" class="btn btn-danger">Remove</button>
                </div>
            </div>
        </div>
    `;
   }

   function viewFamilyMember(patientId) {
      window.open(`view_patient.php?id=${patientId}`, '_blank');
   }

   // Tab functionality
   document.querySelectorAll('.tab-button').forEach(button => {
      button.addEventListener('click', () => {
         // Remove active class from all buttons and contents
         document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
         document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

         // Add active class to clicked button and corresponding content
         button.classList.add('active');
         document.getElementById(button.dataset.tab).classList.add('active');
      });
   });

   // Load family members
   async function loadFamilyMembers() {
      try {
         const response = await fetch(`${BASE_URL}get_family_members.php?patient_id=${currentPatientId}`);
         const result = await response.json();

         if (result.error) {
            throw new Error(result.error);
         }

         renderFamilyMembers(result);
      } catch (error) {
         console.error('Error loading family members:', error);
         document.getElementById('familyMembersList').innerHTML =
            '<p class="empty-state">Error loading family members.</p>';
      }
   }

   async function loadPatientProfile() {
      const urlParams = new URLSearchParams(window.location.search);
      currentPatientId = urlParams.get('id');

      document.getElementById('loading').style.display = 'block';
      document.getElementById('errorMessage').style.display = 'none';

      if (!currentPatientId) {
         document.getElementById('errorMessage').textContent = 'Patient ID missing';
         document.getElementById('errorMessage').style.display = 'block';
         document.getElementById('loading').style.display = 'none';
         return;
      }

      // Try to load from cache first for faster display
      const cachedPatient = localStorage.getItem(`cached_patient_${currentPatientId}`);
      if (cachedPatient) {
         try {
            const patient = JSON.parse(cachedPatient);
            renderPatientDataComprehensive(patient);
         } catch (e) {
            console.error('Error parsing cached patient:', e);
         }
      }

      // Then try to load fresh data if online
      if (navigator.onLine) {
         try {
            const patientRes = await fetch(`${BASE_URL}get_single_patient.php?id=${currentPatientId}`);
            const patient = await patientRes.json();

            if (!patient.error) {
               renderPatientDataComprehensive(patient);
               // Cache the patient data
               localStorage.setItem(`cached_patient_${currentPatientId}`, JSON.stringify(patient));

               document.getElementById('modalPatientId').value = currentPatientId;
               document.getElementById('familyPatientId').value = currentPatientId;
               document.getElementById('modalPatientName').textContent = patient.fullName;

               // Load visits
               const visitsRes = await fetch(`${BASE_URL}get_visits_by_patient.php?patient_id=${currentPatientId}`);
               const visits = await visitsRes.json();
               renderVisits(visits);
               localStorage.setItem(`cached_visits_${currentPatientId}`, JSON.stringify(visits));

               // Load family members
               await loadFamilyMembers();

            } else {
               throw new Error(patient.error.message);
            }

         } catch (error) {
            console.error('Error loading fresh data:', error);
            // If online load fails, try to use cached data for visits too
            const cachedVisits = localStorage.getItem(`cached_visits_${currentPatientId}`);
            if (cachedVisits) {
               const visits = JSON.parse(cachedVisits);
               renderVisits(visits);
            }
         }
      } else {
         // Offline - use whatever cached data we have
         const cachedVisits = localStorage.getItem(`cached_visits_${currentPatientId}`);
         if (cachedVisits) {
            const visits = JSON.parse(cachedVisits);
            renderVisits(visits);
         }
         showOfflineMessage();
      }

      document.getElementById('loading').style.display = 'none';
   }

   function showOfflineMessage() {
      if (!document.getElementById('offlineViewMessage')) {
         const message = document.createElement('div');
         message.id = 'offlineViewMessage';
         message.style.cssText =
            'background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin: 10px 0;';
         message.textContent = '📱 Offline mode - showing cached data';
         document.querySelector('.main-content').insertBefore(message, document.querySelector('.tab-container'));
      }
   }

   // Load patients for family member selection
   async function loadPatientSearch() {
      try {
         const response = await fetch(`${BASE_URL}get_all_patients.php`);
         const patients = await response.json();

         const select = document.getElementById('relative_patient_id');
         select.innerHTML = '<option value="">Select a patient...</option>';

         patients.forEach(patient => {
            if (patient.id !== currentPatientId) {
               const option = document.createElement('option');
               option.value = patient.id;
               option.textContent = `${patient.fullName} (${patient.patient_code || 'No ID'})`;
               select.appendChild(option);
            }
         });
      } catch (error) {
         console.error('Error loading patients for search:', error);
         const select = document.getElementById('relative_patient_id');
         select.innerHTML = '<option value="">Error loading patients</option>';
      }
   }

   // Export to DOCX Function - COMPLETE VERSION
   async function exportPatientToDocx() {
      const exportBtn = document.getElementById('exportDocxBtn');
      const originalText = exportBtn.innerHTML;

      try {
         if (!currentPatientData) {
            showMessage('⚠️ No patient data available to export.', false);
            return;
         }

         exportBtn.disabled = true;
         exportBtn.innerHTML = '⏳ Exporting...';

         if (typeof docx === 'undefined') {
            throw new Error('DOCX library not loaded. Please check your internet connection.');
         }

         const {
            Document,
            Paragraph,
            TextRun,
            HeadingLevel,
            AlignmentType,
            Table,
            TableRow,
            TableCell,
            WidthType
         } = docx;

         const exportDate = new Date().toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
         });

         // Helper function to create section header
         function createSectionHeader(text) {
            return new Paragraph({
               text: text,
               heading: HeadingLevel.HEADING_3,
               spacing: {
                  before: 400,
                  after: 200
               }
            });
         }

         // Helper function to create data row
         function createDataRow(label, value) {
            const displayValue = value === null || value === '' || value === undefined ? 'Not specified' : String(
               value);
            return new TableRow({
               children: [
                  new TableCell({
                     children: [new Paragraph({
                        text: label,
                        bold: true
                     })]
                  }),
                  new TableCell({
                     children: [new Paragraph(displayValue)]
                  })
               ]
            });
         }

         // Helper function for boolean values
         function createBooleanRow(label, value) {
            const displayValue = (value === '1' || value === 1) ? 'YES' : 'NO';
            return new TableRow({
               children: [
                  new TableCell({
                     children: [new Paragraph({
                        text: label,
                        bold: true
                     })]
                  }),
                  new TableCell({
                     children: [new Paragraph(displayValue)]
                  })
               ]
            });
         }

         // Create document content
         const children = [
            // Header
            new Paragraph({
               text: "BARANGAY ZABALI HEALTH CENTER",
               heading: HeadingLevel.HEADING_1,
               alignment: AlignmentType.CENTER,
            }),
            new Paragraph({
               text: "PATIENT MEDICAL PROFILE",
               heading: HeadingLevel.HEADING_2,
               alignment: AlignmentType.CENTER,
               spacing: {
                  after: 400
               }
            }),
            new Paragraph({
               children: [
                  new TextRun({
                     text: "Patient Name: ",
                     bold: true
                  }),
                  new TextRun(currentPatientData.fullName || 'Not specified')
               ],
               spacing: {
                  after: 100
               }
            }),
            new Paragraph({
               children: [
                  new TextRun({
                     text: "Export Date: ",
                     bold: true
                  }),
                  new TextRun(exportDate)
               ],
               spacing: {
                  after: 400
               }
            }),

            // ===== PERSONAL INFORMATION =====
            createSectionHeader("PERSONAL INFORMATION"),
            new Table({
               width: {
                  size: 100,
                  type: WidthType.PERCENTAGE
               },
               columnWidths: [2500, 5000],
               rows: [
                  createDataRow('First Name', currentPatientData.fullName),
                  createDataRow('Middle Name', currentPatientData.middle_name),
                  createDataRow('Last Name', currentPatientData.last_name),
                  createDataRow('Age', currentPatientData.age),
                  createDataRow('Birth Date', currentPatientData.birthDate),
                  createDataRow('Contact Number', currentPatientData.contactNumber),
                  createDataRow('Location/Address', currentPatientData.location),
                  createDataRow('Registered By User ID', currentPatientData.registered_by_user_id)
               ]
            }),

            // ===== IDENTIFICATION =====
            createSectionHeader("IDENTIFICATION"),
            new Table({
               width: {
                  size: 100,
                  type: WidthType.PERCENTAGE
               },
               columnWidths: [2500, 5000],
               rows: [
                  createDataRow('Patient ID', currentPatientData.id),
                  createDataRow('Patient Code', currentPatientData.patient_code),
                  createDataRow('PhilHealth ID', currentPatientData.philhealth_id),
                  createDataRow('Local Patient ID', currentPatientData.local_patient_id)
               ]
            }),

            // ===== CLINICAL STATUS =====
            createSectionHeader("CLINICAL STATUS"),
            new Table({
               width: {
                  size: 100,
                  type: WidthType.PERCENTAGE
               },
               columnWidths: [2500, 5000],
               rows: [
                  createDataRow('Last Checkup Date', currentPatientData.lastCheckup),
                  createDataRow('Chief Complaint/Title', currentPatientData.title),
                  createDataRow('Warning', currentPatientData.warning)
               ]
            }),

            // Clinical Notes (separate paragraph for long text)
            new Paragraph({
               text: "Clinical Notes:",
               heading: HeadingLevel.HEADING_4,
               spacing: {
                  before: 200,
                  after: 100
               }
            }),
            new Paragraph({
               text: currentPatientData.clinicalNotes || 'No clinical notes provided.',
               spacing: {
                  after: 200
               }
            }),

            // ===== VITALS & MEASUREMENTS =====
            createSectionHeader("VITALS & MEASUREMENTS"),
            new Table({
               width: {
                  size: 100,
                  type: WidthType.PERCENTAGE
               },
               columnWidths: [2500, 5000],
               rows: [
                  createDataRow('Blood Pressure', currentPatientData.bloodPressure),
                  createDataRow('Heart Rate', currentPatientData.heartRate ? currentPatientData.heartRate +
                     ' bpm' : ''),
                  createDataRow('Respiratory Rate', currentPatientData.respiratoryRate ? currentPatientData
                     .respiratoryRate + ' breaths/min' : ''),
                  createDataRow('Temperature', currentPatientData.temperature ? currentPatientData
                     .temperature + ' °C' : ''),
                  createDataRow('Weight', currentPatientData.weight ? currentPatientData.weight + ' kg' :
                     ''),
                  createDataRow('Height', currentPatientData.height ? currentPatientData.height + ' cm' :
                     ''),
                  createDataRow('Checkup Time', currentPatientData.time),
                  createDataRow('Normal Ranges Reference', currentPatientData.normalRanges)
               ]
            }),

            // ===== MEDICAL STATUS FLAGS =====
            createSectionHeader("MEDICAL STATUS FLAGS"),
            new Table({
               width: {
                  size: 100,
                  type: WidthType.PERCENTAGE
               },
               columnWidths: [2500, 5000],
               rows: [
                  createBooleanRow('Critical Status', currentPatientData.isCritical),
                  createBooleanRow('Pregnancy Status', currentPatientData.isPregnant),
                  createBooleanRow('Elderly Patient', currentPatientData.isElderly),
                  createBooleanRow('Warning Flag Active', currentPatientData.isWarningFlag),
                  createBooleanRow('Stable Condition', currentPatientData.isStable),
                  createBooleanRow('Has High Blood Pressure', currentPatientData.hasHighBP),
                  createBooleanRow('Needs Medication', currentPatientData.needsMedication),
                  createBooleanRow('Needs Follow-up Appointment', currentPatientData.needsAppointment)
               ]
            }),

            // ===== NOTES & ADDITIONAL INFORMATION =====
            createSectionHeader("NOTES & ADDITIONAL INFORMATION")
         ];

         // Add description if available
         if (currentPatientData.description) {
            children.push(
               new Paragraph({
                  text: "Patient Description:",
                  heading: HeadingLevel.HEADING_4,
                  spacing: {
                     before: 200,
                     after: 100
                  }
               }),
               new Paragraph({
                  text: currentPatientData.description,
                  spacing: {
                     after: 200
                  }
               })
            );
         }

         // Add other info if available
         if (currentPatientData.otherInfo) {
            children.push(
               new Paragraph({
                  text: "Other Information:",
                  heading: HeadingLevel.HEADING_4,
                  spacing: {
                     before: 200,
                     after: 100
                  }
               }),
               new Paragraph({
                  text: currentPatientData.otherInfo,
                  spacing: {
                     after: 200
                  }
               })
            );
         }

         // ===== VISIT HISTORY =====
         if (currentPatientVisits && currentPatientVisits.length > 0) {
            children.push(createSectionHeader("VISIT HISTORY"));

            // Create visit history table
            const visitHeaderRow = new TableRow({
               children: [
                  new TableCell({
                     children: [new Paragraph({
                        text: "Date",
                        bold: true
                     })]
                  }),
                  new TableCell({
                     children: [new Paragraph({
                        text: "Chief Complaint",
                        bold: true
                     })]
                  }),
                  new TableCell({
                     children: [new Paragraph({
                        text: "Vitals",
                        bold: true
                     })]
                  }),
                  new TableCell({
                     children: [new Paragraph({
                        text: "Clinical Notes",
                        bold: true
                     })]
                  })
               ]
            });

            const visitRows = [visitHeaderRow];

            currentPatientVisits.forEach(visit => {
               const visitDate = new Date(visit.visit_date).toLocaleDateString('en-US');
               const vitals =
                  `BP: ${visit.blood_pressure || 'N/A'} | HR: ${visit.heart_rate || 'N/A'} | Temp: ${visit.temperature || 'N/A'}`;
               const clinicalNotes = visit.clinical_notes ? (visit.clinical_notes.length > 100 ? visit
                  .clinical_notes.substring(0, 100) + '...' : visit.clinical_notes) : 'None';

               visitRows.push(
                  new TableRow({
                     children: [
                        new TableCell({
                           children: [new Paragraph(visitDate)]
                        }),
                        new TableCell({
                           children: [new Paragraph(visit.chief_complaint || 'No complaint')]
                        }),
                        new TableCell({
                           children: [new Paragraph(vitals)]
                        }),
                        new TableCell({
                           children: [new Paragraph(clinicalNotes)]
                        })
                     ]
                  })
               );
            });

            children.push(
               new Table({
                  width: {
                     size: 100,
                     type: WidthType.PERCENTAGE
                  },
                  columnWidths: [1500, 2000, 2000, 3000],
                  rows: visitRows
               })
            );
         }

         // ===== FAMILY HISTORY =====
         if (currentFamilyMembers && currentFamilyMembers.length > 0) {
            children.push(createSectionHeader("FAMILY HISTORY"));

            const familyHeaderRow = new TableRow({
               children: [
                  new TableCell({
                     children: [new Paragraph({
                        text: "Relationship",
                        bold: true
                     })]
                  }),
                  new TableCell({
                     children: [new Paragraph({
                        text: "Name",
                        bold: true
                     })]
                  }),
                  new TableCell({
                     children: [new Paragraph({
                        text: "Age",
                        bold: true
                     })]
                  }),
                  new TableCell({
                     children: [new Paragraph({
                        text: "Contact",
                        bold: true
                     })]
                  })
               ]
            });

            const familyRows = [familyHeaderRow];

            currentFamilyMembers.forEach(member => {
               familyRows.push(
                  new TableRow({
                     children: [
                        new TableCell({
                           children: [new Paragraph(member.relationship_type)]
                        }),
                        new TableCell({
                           children: [new Paragraph(member.fullName)]
                        }),
                        new TableCell({
                           children: [new Paragraph(member.age || 'N/A')]
                        }),
                        new TableCell({
                           children: [new Paragraph(member.contactNumber || 'N/A')]
                        })
                     ]
                  })
               );
            });

            children.push(
               new Table({
                  width: {
                     size: 100,
                     type: WidthType.PERCENTAGE
                  },
                  columnWidths: [1500, 3000, 1000, 1500],
                  rows: familyRows
               })
            );
         }

         // Footer
         children.push(
            new Paragraph({
               text: " ",
               spacing: {
                  before: 400
               }
            }),
            new Paragraph({
               text: "--- END OF PATIENT PROFILE ---",
               alignment: AlignmentType.CENTER,
               spacing: {
                  before: 200
               }
            })
         );

         // Create final document
         const doc = new Document({
            sections: [{
               properties: {},
               children: children
            }]
         });

         const blob = await docx.Packer.toBlob(doc);

         if (typeof saveAs === 'undefined') {
            throw new Error('FileSaver library not loaded. Please check your internet connection.');
         }

         const fileName =
            `Patient_Profile_${currentPatientData.fullName.replace(/[^a-zA-Z0-9]/g, '_')}_${new Date().toISOString().split('T')[0]}.docx`;
         saveAs(blob, fileName);
         showMessage('✅ Patient profile exported to DOCX successfully!', true);

      } catch (error) {
         console.error('Error generating DOCX:', error);
         showMessage(`❌ Error exporting DOCX: ${error.message}`, false);
      } finally {
         exportBtn.disabled = false;
         exportBtn.innerHTML = originalText;
      }
   }

   // Family form submission
   document.getElementById('familyForm').onsubmit = async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);

      try {
         const res = await fetch(`${BASE_URL}add_family_relationship.php`, {
            method: 'POST',
            body: formData
         });
         const result = await res.json();

         if (result.success) {
            showMessage('✅ Family relationship added!');
            closeModal('familyModal');
            document.getElementById('familyForm').reset();
            loadFamilyMembers();
         } else {
            throw new Error(result.message || 'Failed to add relationship');
         }
      } catch (err) {
         showMessage('❌ Failed to add relationship: ' + err.message, false);
      }
   };

   // Visit form submission
   document.getElementById('visitForm').onsubmit = async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const data = Object.fromEntries(formData.entries());

      try {
         const res = await fetch(`${BASE_URL}add_visit.php`, {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
         });
         const result = await res.json();

         if (result.success) {
            showMessage('✅ Visit added!');
            closeModal('visitModal');
            document.getElementById('visitForm').reset();
            loadPatientProfile();
         } else {
            throw new Error(result.message || 'Failed to add visit');
         }
      } catch (err) {
         showMessage('❌ Failed to add visit: ' + err.message, false);
      }
   };

   // Remove family member
   async function removeFamilyMember(relationshipId) {
      if (!confirm(
            'Are you sure you want to remove this family relationship? This will also remove the reciprocal relationship.'
         )) {
         return;
      }

      try {
         const formData = new URLSearchParams();
         formData.append('relationship_id', relationshipId);

         const res = await fetch(`${BASE_URL}remove_family_relationship.php`, {
            method: 'POST',
            headers: {
               'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData
         });
         const result = await res.json();

         if (result.success) {
            showMessage('✅ Family relationship removed!');
            loadFamilyMembers();
         } else {
            throw new Error(result.message || 'Failed to remove relationship');
         }
      } catch (err) {
         showMessage('❌ Failed to remove relationship: ' + err.message, false);
      }
   }

   // DELETE PATIENT FUNCTION - FIXED
   async function deletePatient() {
      if (!currentPatientId) {
         showMessage('❌ No patient selected for deletion', false);
         return;
      }

      const patientName = currentPatientData?.fullName || 'this patient';

      if (!confirm(
            `⚠️ WARNING: This will permanently delete patient "${patientName}" and ALL their associated visits, family relationships, and medical history!\n\nThis action cannot be undone!\n\nAre you sure you want to proceed?`
         )) {
         return;
      }

      const deleteBtn = document.getElementById('deleteBtn');
      const originalText = deleteBtn.innerHTML;
      deleteBtn.disabled = true;
      deleteBtn.innerHTML = '🗑️ Deleting...';

      try {
         const response = await fetch(`${BASE_URL}delete_patients.php`, {
            method: 'POST',
            headers: {
               'Content-Type': 'application/json',
            },
            body: JSON.stringify({
               id: currentPatientId
            })
         });

         const result = await response.json();

         if (result.success) {
            showMessage('✅ Patient deleted successfully!');
            // Redirect to patient list after a short delay
            setTimeout(() => {
               window.location.href = 'patients_list.php';
            }, 1500);
         } else {
            throw new Error(result.message || 'Failed to delete patient');
         }

      } catch (error) {
         console.error('Delete error:', error);
         showMessage(`❌ Error deleting patient: ${error.message}`, false);
      } finally {
         deleteBtn.disabled = false;
         deleteBtn.innerHTML = originalText;
      }
   }

   // Event Listeners
   document.getElementById('addVisitBtn').onclick = () => {
      openModal('visitModal');
   };

   document.getElementById('editBtn').onclick = () => {
      if (currentPatientId) window.location.href = `edit_patient.php?id=${currentPatientId}`;
   };

   document.getElementById('deleteBtn').onclick = deletePatient;

   document.getElementById('exportDocxBtn').onclick = exportPatientToDocx;

   document.getElementById('addRelativeBtn').onclick = () => {
      openModal('familyModal');
      loadPatientSearch();
   };

   // Close modal when clicking outside
   window.onclick = function(event) {
      if (event.target.classList.contains('modal')) {
         event.target.style.display = 'none';
      }
   }

   // Initialize
   loadPatientProfile();
   </script>
</body>

</html>