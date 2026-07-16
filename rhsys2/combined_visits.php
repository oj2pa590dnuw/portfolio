<?php
require 'likod/auth_guard.php';
require 'likod/activity_logger.php';
$logger = new ActivityLogger();
$today = date('F j, Y');
?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <title>Clinic Visits Management</title>
   <script src="offline-helper.js"></script>
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

            <!-- Add New Visit Section (Top Part) -->
            <div class="card">
               <h1>Record New Patient Visit</h1>
               <p>Step 1: Select the patient for the new visit.</p>

               <div class="patient-list-container">
                  <div class="patient-search-box">
                     <input type="text" id="patientSearch" class="form-input"
                        placeholder="🔍 Search patients by name or location...">
                  </div>
                  <div class="patient-list" id="patientList">
                     <div class="empty-state">Loading patients...</div>
                  </div>
               </div>

               <div id="selectedPatientInfo" style="display: none;" class="status-summary">
                  <strong>Selected Patient:</strong>
                  <span id="selectedPatientName"></span>
                  <span id="selectedPatientDetails"></span>
                  <button type="button" onclick="clearSelection()" class="btn btn-primary" style="float: right;">
                     Continue to Visit Details →
                  </button>
               </div>
            </div>

            <!-- Daily Visits Log Section (Bottom Part) -->
            <div class="card">
               <h2>Daily Visits Log</h2>

               <div class="controls-bar">
                  <div class="date-filter">
                     <label for="visitDate">View visits for:</label>
                     <input type="date" id="visitDate" value="<?php echo date('Y-m-d'); ?>" class="form-input">
                  </div>

                  <div class="period-filter">
                     <label for="periodType">Export by:</label>
                     <select id="periodType" class="form-input">
                        <option value="month">Month</option>
                        <option value="year">Year</option>
                     </select>
                     <input type="month" id="monthInput" class="form-input" value="<?php echo date('Y-m'); ?>">
                     <input type="number" id="yearInput" class="form-input" value="<?php echo date('Y'); ?>" min="2020"
                        max="2030" style="display: none;">
                  </div>

                  <div class="action-buttons">
                     <button id="refreshBtn" class="btn refresh-btn">
                        🔄 Refresh Data
                     </button>
                     <button id="exportBtn" class="btn export-btn">
                        📄 Export Daily to DOCX
                     </button>
                     <button id="exportPeriodBtn" class="btn export-btn">
                        📊 Export Period to DOCX
                     </button>
                  </div>
               </div>

               <div class="status-summary">
                  Daily Log for: <span id="displayDate"><?php echo $today; ?></span> |
                  Total Visits: <span id="visitCount">0</span>
               </div>

               <div id="dailyLog">
                  <div class="loading">
                     <img src="images/loading.gif" alt="Loading...">
                     <p>Loading daily log...</p>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <!-- Visit Details Modal -->
   <div id="visitDetailsModal" class="modal" style="display: none;">
      <div class="modal-content" style="max-width: 700px; max-height: 90vh; overflow-y: auto;">
         <div class="modal-header">
            <h2>Visit Details</h2>

         </div>

         <div id="modalPatientInfo" class="status-summary" style="margin-bottom: 20px;">
            <strong>Patient:</strong>
            <span id="modalPatientName"></span>
            <span id="modalPatientDetails"></span>
         </div>

         <form id="visitForm" class="form-container">
            <input type="hidden" name="patient_id" id="formPatientId" value="">

            <h3>Step 2: Visit Details</h3>

            <div class="form-item">
               <label for="visit_date" class="form-label required">Visit Date</label>
               <input type="date" id="visit_date" name="visit_date" class="form-input"
                  value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-item">
               <label for="chief_complaint" class="form-label required">Chief Complaint / Title</label>
               <input type="text" id="chief_complaint" name="chief_complaint" class="form-input"
                  placeholder="e.g., Fever and cough for 2 days" required>
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
               <textarea id="clinical_notes" name="clinical_notes" class="form-textarea" rows="6"
                  placeholder="Detailed examination findings, diagnosis, and plan."></textarea>
            </div>

            <div class="form-item">
               <label for="procedures_done" class="form-label">Procedures / Labs Done</label>
               <textarea id="procedures_done" name="procedures_done" class="form-textarea" rows="3"
                  placeholder="e.g., FBS, CBC, Nebulization"></textarea>
            </div>

            <div class="action-buttons" style="margin-top: 20px;">

               <button type="submit" class="btn btn-primary">Save Visit Record</button>
            </div>
         </form>

         <div id="modalLoading" class="loading" style="display: none;">
            <img src="images/loading.gif" alt="Loading...">
         </div>
         <div id="modalErrorMessage" class="message-box message-error" style="display: none; margin-top: 15px;"></div>
      </div>
   </div>

   <div id="messageBox" class="message-box"></div>

   <script src="https://cdnjs.cloudflare.com/ajax/libs/docx/7.8.2/docx.min.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

   <script>
      document.addEventListener('DOMContentLoaded', () => {
         const BASE_URL = 'likod/';

         // Add Visit Elements
         const patientList = document.getElementById('patientList');
         const patientSearch = document.getElementById('patientSearch');
         const selectedPatientInfo = document.getElementById('selectedPatientInfo');
         const selectedPatientName = document.getElementById('selectedPatientName');
         const selectedPatientDetails = document.getElementById('selectedPatientDetails');

         // Modal Elements
         const visitDetailsModal = document.getElementById('visitDetailsModal');
         const visitForm = document.getElementById('visitForm');
         const formPatientId = document.getElementById('formPatientId');
         const modalPatientInfo = document.getElementById('modalPatientInfo');
         const modalPatientName = document.getElementById('modalPatientName');
         const modalPatientDetails = document.getElementById('modalPatientDetails');
         const modalLoading = document.getElementById('modalLoading');
         const modalErrorMessage = document.getElementById('modalErrorMessage');

         // Daily Visits Elements
         const dateInput = document.getElementById('visitDate');
         const dailyLogDiv = document.getElementById('dailyLog');
         const refreshBtn = document.getElementById('refreshBtn');
         const exportBtn = document.getElementById('exportBtn');
         const displayDateSpan = document.getElementById('displayDate');
         const visitCountSpan = document.getElementById('visitCount');
         const periodTypeSelect = document.getElementById('periodType');
         const monthInput = document.getElementById('monthInput');
         const yearInput = document.getElementById('yearInput');
         const exportPeriodBtn = document.getElementById('exportPeriodBtn');

         let allPatients = [];
         let selectedPatient = null;
         let currentVisits = [];

         // Modal Functions
         function openVisitModal() {
            if (!selectedPatient) return;

            // Update modal with patient info
            modalPatientName.textContent = selectedPatient.fullName;
            modalPatientDetails.textContent =
               ` • Age: ${selectedPatient.age || 'N/A'} • Location: ${selectedPatient.location || 'Not specified'}`;
            modalPatientInfo.style.display = 'block';

            // Set patient ID in form
            formPatientId.value = selectedPatient.id;

            // Reset form
            visitForm.reset();
            document.getElementById('visit_date').value = '<?php echo date('Y-m-d'); ?>';
            modalErrorMessage.style.display = 'none';

            // Show modal
            visitDetailsModal.style.display = 'flex';
            visitDetailsModal.style.alignItems = 'center';
            visitDetailsModal.style.justifyContent = 'center';
            document.body.style.overflow = 'hidden';

            // Focus on first field
            setTimeout(() => {
               document.getElementById('chief_complaint').focus();
            }, 100);
         }

         function closeVisitModal() {
            visitDetailsModal.style.display = 'none';
            document.body.style.overflow = 'auto';
         }

         function showMessage(message, success = true) {
            const msgBox = document.getElementById('messageBox');
            msgBox.textContent = message;
            msgBox.className = success ? 'message-box message-success show' : 'message-box message-error show';
            setTimeout(() => {
               msgBox.classList.remove('show');
            }, 3000);
         }

         // Add Visit Functions
         function loadPatients() {
            patientList.innerHTML = '<div class="empty-state">Loading patients...</div>';

            fetch(`${BASE_URL}get_patients.php`)
               .then(r => r.json())
               .then(patients => {
                  if (!patients || patients.length === 0 || patients.error) {
                     patientList.innerHTML = '<div class="empty-state">No patients found in the system.</div>';
                     return;
                  }

                  allPatients = patients;
                  displayPatients(allPatients);
               })
               .catch(error => {
                  patientList.innerHTML =
                     '<div class="empty-state">Error loading patients. Please try again.</div>';
                  console.error('Error fetching patients:', error);
               });
         }

         function displayPatients(patients) {
            if (patients.length === 0) {
               patientList.innerHTML = '<div class="empty-state">No patients match your search.</div>';
               return;
            }

            patientList.innerHTML = '';
            patients.forEach(patient => {
               const patientItem = document.createElement('div');
               patientItem.className = 'patient-item';
               if (selectedPatient && selectedPatient.id === patient.id) {
                  patientItem.classList.add('selected');
               }

               patientItem.innerHTML = `
                <div class="patient-info">
                    <div class="patient-name">${patient.fullName || 'Unnamed Patient'}</div>
                    <div class="patient-details">
                        ${patient.lastCheckup ? `Last visit: ${patient.lastCheckup}` : 'No previous visits'}
                        ${patient.title ? ` • ${patient.title}` : ''}
                    </div>
                </div>
                <div class="patient-age-location">
                    <div>Age: ${patient.age || 'N/A'}</div>
                    <div>${patient.location || 'No location'}</div>
                </div>
            `;

               patientItem.addEventListener('click', () => {
                  selectPatient(patient);
               });

               patientList.appendChild(patientItem);
            });
         }

         function selectPatient(patient) {
            selectedPatient = patient;

            // Update selected patient info
            selectedPatientName.textContent = patient.fullName;
            selectedPatientDetails.textContent =
               ` • Age: ${patient.age} • Location: ${patient.location || 'Not specified'}`;
            selectedPatientInfo.style.display = 'block';

            // Update patient list to show selection
            displayPatients(allPatients.filter(p =>
               p.fullName.toLowerCase().includes(patientSearch.value.toLowerCase()) ||
               (p.location && p.location.toLowerCase().includes(patientSearch.value.toLowerCase()))
            ));
         }

         function clearSelection() {
            selectedPatient = null;
            selectedPatientInfo.style.display = 'none';
            displayPatients(allPatients.filter(p =>
               p.fullName.toLowerCase().includes(patientSearch.value.toLowerCase()) ||
               (p.location && p.location.toLowerCase().includes(patientSearch.value.toLowerCase()))
            ));
         }

         // Search functionality
         patientSearch.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filteredPatients = allPatients.filter(patient =>
               patient.fullName.toLowerCase().includes(searchTerm) ||
               (patient.location && patient.location.toLowerCase().includes(searchTerm))
            );
            displayPatients(filteredPatients);
         });

         // Event listener for form submission
         visitForm.onsubmit = async e => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(visitForm).entries());

            if (!data.patient_id) {
               showMessage("Please select a patient first.", false);
               return;
            }

            if (!data.chief_complaint.trim()) {
               showMessage("Chief complaint is required.", false);
               return;
            }

            modalLoading.style.display = 'block';
            modalErrorMessage.style.display = 'none';

            try {
               // Try online first
               if (navigator.onLine) {
                  const response = await fetch(`${BASE_URL}add_visit.php`, {
                     method: 'POST',
                     headers: {
                        'Content-Type': 'application/json'
                     },
                     body: JSON.stringify(data)
                  });

                  if (response.ok) {
                     const result = await response.json();
                     if (result.success) {
                        showMessage("✅ Visit added successfully!", true);
                        closeVisitModal();
                        clearSelection();
                        // Refresh the daily log to show new visit
                        loadDailyLog();
                        return;
                     } else {
                        throw new Error(result.message || 'Failed to add visit');
                     }
                  }
               }

               // If online failed or offline, save locally
               if (window.offlineHelper) {
                  await window.offlineHelper.saveForSync('visit', data, `${BASE_URL}add_visit.php`);
                  showMessage("✅ Visit saved locally! Will sync when online.", true);
                  closeVisitModal();
                  clearSelection();
                  loadDailyLog(); // Refresh to show locally saved visit
               } else {
                  throw new Error('Offline helper not available');
               }

            } catch (e) {
               console.error(e);
               modalErrorMessage.textContent = "❌ Failed to add visit: " + e.message;
               modalErrorMessage.style.display = 'block';
            } finally {
               modalLoading.style.display = 'none';
            }
         };

         // Daily Visits Functions
         function togglePeriodInputs() {
            if (periodTypeSelect.value === 'month') {
               monthInput.style.display = 'inline-block';
               yearInput.style.display = 'none';
            } else {
               monthInput.style.display = 'none';
               yearInput.style.display = 'inline-block';
            }
         }

         async function loadDailyLog() {
            const date = dateInput.value;

            try {
               dailyLogDiv.innerHTML =
                  '<div class="loading"><img src="images/loading.gif" alt="Loading..."><p>Loading daily log...</p></div>';
               currentVisits = [];

               const displayDate = new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric'
               });
               displayDateSpan.textContent = displayDate;

               // Try online first
               if (navigator.onLine) {
                  try {
                     const visitsResponse = await fetch(`${BASE_URL}get_daily_visits.php?date=${date}`);

                     if (visitsResponse.ok) {
                        const visits = await visitsResponse.json();
                        currentVisits = Array.isArray(visits) ? visits : [];
                        localStorage.setItem(`cached_daily_${date}`, JSON.stringify(currentVisits));
                     } else {
                        throw new Error(`Failed to load visits: ${visitsResponse.status}`);
                     }
                  } catch (error) {
                     console.error('Error loading visits:', error);
                     loadCachedDailyLog(date);
                  }
               } else {
                  loadCachedDailyLog(date);
               }

               visitCountSpan.textContent = currentVisits.length;
               displayDailyResults();

            } catch (error) {
               console.error('Error loading daily log:', error);
               dailyLogDiv.innerHTML = `
                <div class="message-box message-error">
                    Failed to load daily log. ${navigator.onLine ? 'Please check your connection.' : 'You are offline.'}
                </div>
            `;
            }
         }

         function loadCachedDailyLog(date) {
            const cached = localStorage.getItem(`cached_daily_${date}`);
            if (cached) {
               currentVisits = JSON.parse(cached);
               if (currentVisits.length > 0) {
                  dailyLogDiv.innerHTML =
                     '<div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0;">📱 Showing cached data from previous session</div>';
               }
            } else {
               currentVisits = [];
            }
         }

         function displayDailyResults() {
            dailyLogDiv.innerHTML = '';

            if (currentVisits.length === 0) {
               const emptyState = document.createElement('div');
               emptyState.className = 'empty-state';
               emptyState.innerHTML = `
                <div class="empty-state-icon">📅</div>
                <h3>No visit</h3>
                <p>There are no clinic visits for ${displayDateSpan.textContent}.</p>
            `;
               dailyLogDiv.appendChild(emptyState);
               return;
            }

            const visitsHeader = document.createElement('h3');
            visitsHeader.textContent = `📋 Clinic Visits (${currentVisits.length})`;
            dailyLogDiv.appendChild(visitsHeader);

            const visitsTableContainer = document.createElement('div');
            visitsTableContainer.className = 'table-container';

            const visitsTable = document.createElement('table');
            visitsTable.className = 'data-table';

            const thead = document.createElement('thead');
            thead.innerHTML = `
            <tr>
                <th>Time</th>
                <th>Patient Name</th>
                <th>Age</th>
                <th>Location</th>
                <th>Chief Complaint</th>
                <th>Vitals</th>
                <th>Action</th>
            </tr>
        `;
            visitsTable.appendChild(thead);

            const tbody = document.createElement('tbody');
            currentVisits.forEach(visit => {
               const row = document.createElement('tr');
               row.innerHTML = `
                <td>${visit.visit_time || 'N/A'}</td>
                <td><strong>${visit.patient_name || 'N/A'}</strong></td>
                <td>${visit.age || 'N/A'}</td>
                <td>${visit.location || 'N/A'}</td>
                <td>${visit.chief_complaint || 'N/A'}</td>
                <td>
                    ${visit.blood_pressure ? `BP: ${visit.blood_pressure}<br>` : ''}
                    ${visit.heart_rate ? `HR: ${visit.heart_rate}<br>` : ''}
                    ${visit.temperature ? `Temp: ${visit.temperature}°C` : ''}
                    ${!visit.blood_pressure && !visit.heart_rate && !visit.temperature ? 'N/A' : ''}
                </td>
                <td>
                    <a href="view_patient.php?id=${visit.patient_id}" class="action-link">View Profile</a>
                </td>
            `;
               tbody.appendChild(row);
            });
            visitsTable.appendChild(tbody);

            visitsTableContainer.appendChild(visitsTable);
            dailyLogDiv.appendChild(visitsTableContainer);
         }

         async function exportToDocx() {
            const date = dateInput.value;
            const dateFormatted = new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
               year: 'numeric',
               month: 'long',
               day: 'numeric'
            });

            try {
               let htmlContent = `
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                        h1, h2 { color: #2c3e50; text-align: center; }
                        h3 { color: #2c3e50; margin-top: 30px; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        th { color: #27ae60; background-color: white; padding: 12px 8px; text-align: left; border-bottom: 2px solid #27ae60; font-weight: bold; }
                        td { padding: 10px 8px; border-bottom: 1px solid #ecf0f1; }
                        .section { margin-bottom: 40px; }
                        .page-break { page-break-after: always; }
                        .summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    </style>
                </head>
                <body>
                    <h1>Barangay Zabali Health Center</h1>
                    <h2>Daily Log - ${dateFormatted}</h2>
                    <div class="summary">
                        <p><strong>Total Visits:</strong> ${currentVisits.length}</p>
                        <p><strong>Date:</strong> ${dateFormatted}</p>
                    </div>
            `;

               if (currentVisits.length > 0) {
                  htmlContent += `
                    <div class="section">
                        <h3>Clinic Visits (${currentVisits.length})</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient Name</th>
                                    <th>Age</th>
                                    <th>Location</th>
                                    <th>Chief Complaint</th>
                                    <th>Vitals</th>
                                    <th>Clinical Notes</th>
                                    <th>Procedures</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${currentVisits.map(visit => `
                                    <tr>
                                        <td>${visit.visit_time || 'N/A'}</td>
                                        <td>${visit.patient_name || 'N/A'}</td>
                                        <td>${visit.age || 'N/A'}</td>
                                        <td>${visit.location || 'N/A'}</td>
                                        <td>${visit.chief_complaint || 'N/A'}</td>
                                        <td>
                                            ${visit.blood_pressure ? `BP: ${visit.blood_pressure}` : ''}
                                            ${visit.heart_rate ? ` HR: ${visit.heart_rate}` : ''}
                                            ${visit.temperature ? ` Temp: ${visit.temperature}°C` : ''}
                                            ${!visit.blood_pressure && !visit.heart_rate && !visit.temperature ? 'N/A' : ''}
                                        </td>
                                        <td>${visit.clinical_notes || 'None'}</td>
                                        <td>${visit.procedures_done || 'None'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
               } else {
                  htmlContent += `
                    <div class="section">
                        <p style="text-align: center; font-style: italic; margin: 40px 0;">No clinic visits for ${dateFormatted}.</p>
                    </div>
                `;
               }

               htmlContent += `</body></html>`;

               const blob = new Blob([htmlContent], {
                  type: 'application/msword'
               });
               const url = URL.createObjectURL(blob);
               const a = document.createElement('a');
               a.href = url;
               a.download = `Daily_Log_${date}.doc`;
               document.body.appendChild(a);
               a.click();
               document.body.removeChild(a);
               URL.revokeObjectURL(url);

               showMessage('✅ DOC file generated successfully!', true);

            } catch (error) {
               console.error('Error exporting document:', error);
               showMessage('❌ Error generating document. Please try again.', false);
            }
         }

         async function exportPeriodToDocx() {
            const periodType = periodTypeSelect.value;
            const period = periodType === 'month' ? monthInput.value : yearInput.value;

            if (!period) {
               showMessage('Please select a valid period', false);
               return;
            }

            try {
               exportPeriodBtn.disabled = true;
               exportPeriodBtn.textContent = '⏳ Generating...';

               const response = await fetch(
                  `${BASE_URL}get_monthly_visits.php?period=${period}&type=${periodType}`);

               if (!response.ok) {
                  throw new Error(`Failed to load visits: ${response.status}`);
               }

               const visits = await response.json();
               const visitsArray = Array.isArray(visits) ? visits : [];

               const groupedVisits = {};

               if (periodType === 'month') {
                  visitsArray.forEach(visit => {
                     const date = visit.visit_date;
                     if (!groupedVisits[date]) {
                        groupedVisits[date] = [];
                     }
                     groupedVisits[date].push(visit);
                  });
               } else {
                  visitsArray.forEach(visit => {
                     const month = visit.visit_date.substring(0, 7);
                     if (!groupedVisits[month]) {
                        groupedVisits[month] = [];
                     }
                     groupedVisits[month].push(visit);
                  });
               }

               let periodFormatted;
               let fileName;

               if (periodType === 'month') {
                  const date = new Date(period + '-01');
                  periodFormatted = date.toLocaleDateString('en-US', {
                     year: 'numeric',
                     month: 'long'
                  });
                  fileName = `Monthly_Log_${period}.doc`;
               } else {
                  periodFormatted = period;
                  fileName = `Yearly_Log_${period}.doc`;
               }

               let htmlContent = `
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                        h1, h2 { color: #2c3e50; text-align: center; }
                        h3 { color: #2c3e50; margin: 30px 0 15px 0; padding-bottom: 8px; border-bottom: 2px solid #3498db; }
                        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                        th { color: #27ae60; background-color: white; padding: 12px 8px; text-align: left; border-bottom: 2px solid #27ae60; font-weight: bold; }
                        td { padding: 10px 8px; border-bottom: 1px solid #ecf0f1; }
                        .section { margin-bottom: 40px; }
                        .page-break { page-break-after: always; }
                        .summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                        .date-section { margin: 30px 0; }
                        .month-section { margin: 40px 0; }
                    </style>
                </head>
                <body>
                    <h1>Barangay Zabali Health Center</h1>
                    <h2>${periodType === 'month' ? 'Monthly' : 'Yearly'} Log - ${periodFormatted}</h2>
                    
                    <div class="summary">
                        <p><strong>Period:</strong> ${periodFormatted}</p>
                        <p><strong>Total Visits:</strong> ${visitsArray.length}</p>
                        <p><strong>${periodType === 'month' ? 'Days with Visits' : 'Months with Visits'}:</strong> ${Object.keys(groupedVisits).length}</p>
                        <p><strong>Report Generated:</strong> ${new Date().toLocaleDateString('en-US', {
                  year: 'numeric', month: 'long', day: 'numeric',
                  hour: '2-digit', minute: '2-digit'
               })}</p>
                    </div>
            `;

               const sortedGroups = Object.keys(groupedVisits).sort();

               if (sortedGroups.length > 0) {
                  sortedGroups.forEach((group, index) => {
                     const groupVisits = groupedVisits[group];

                     let groupHeader;
                     if (periodType === 'month') {
                        const date = new Date(group + 'T00:00:00');
                        groupHeader = date.toLocaleDateString('en-US', {
                           year: 'numeric',
                           month: 'long',
                           day: 'numeric',
                           weekday: 'long'
                        });
                     } else {
                        const date = new Date(group + '-01T00:00:00');
                        groupHeader = date.toLocaleDateString('en-US', {
                           year: 'numeric',
                           month: 'long'
                        });
                     }

                     htmlContent += `
                        <div class="${periodType === 'month' ? 'date-section' : 'month-section'}">
                            <h3>${groupHeader} (${groupVisits.length} visits)</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>${periodType === 'month' ? 'Time' : 'Date'}</th>
                                        <th>Patient Name</th>
                                        <th>Age</th>
                                        <th>Location</th>
                                        <th>Chief Complaint</th>
                                        <th>Vitals</th>
                                        <th>Clinical Notes</th>
                                        <th>Procedures</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${groupVisits.map(visit => `
                                        <tr>
                                            <td>${periodType === 'month' ? (visit.visit_time || 'N/A') : (visit.visit_date || 'N/A')}</td>
                                            <td>${visit.patient_name || 'N/A'}</td>
                                            <td>${visit.age || 'N/A'}</td>
                                            <td>${visit.location || 'N/A'}</td>
                                            <td>${visit.chief_complaint || 'N/A'}</td>
                                            <td>
                                                ${visit.blood_pressure ? `BP: ${visit.blood_pressure}` : ''}
                                                ${visit.heart_rate ? ` HR: ${visit.heart_rate}` : ''}
                                                ${visit.temperature ? ` Temp: ${visit.temperature}°C` : ''}
                                                ${!visit.blood_pressure && !visit.heart_rate && !visit.temperature ? 'N/A' : ''}
                                            </td>
                                            <td>${visit.clinical_notes || 'None'}</td>
                                            <td>${visit.procedures_done || 'None'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;

                     if (index < sortedGroups.length - 1) {
                        htmlContent += `<div class="page-break"></div>`;
                     }
                  });
               } else {
                  htmlContent += `
                    <div style="text-align: center; padding: 40px;">
                        <h3>No Visits Found</h3>
                        <p>There are no clinic visits for ${periodFormatted}.</p>
                    </div>
                `;
               }

               htmlContent += `</body></html>`;

               const blob = new Blob([htmlContent], {
                  type: 'application/msword'
               });
               const url = URL.createObjectURL(blob);
               const a = document.createElement('a');
               a.href = url;
               a.download = fileName;
               document.body.appendChild(a);
               a.click();
               document.body.removeChild(a);
               URL.revokeObjectURL(url);

               showMessage(`✅ ${periodType === 'month' ? 'Monthly' : 'Yearly'} DOC file generated successfully!`,
                  true);

            } catch (error) {
               console.error('Error exporting period document:', error);
               showMessage('❌ Error generating document. Please try again.', false);
            } finally {
               exportPeriodBtn.disabled = false;
               exportPeriodBtn.textContent = '📊 Export Period to DOCX';
            }
         }

         // Initialize everything
         loadPatients();
         loadDailyLog();

         // Event listeners for daily visits
         dateInput.addEventListener('change', loadDailyLog);
         periodTypeSelect.addEventListener('change', togglePeriodInputs);
         refreshBtn.addEventListener('click', loadDailyLog);
         exportBtn.addEventListener('click', exportToDocx);
         exportPeriodBtn.addEventListener('click', exportPeriodToDocx);

         // Initialize period inputs
         togglePeriodInputs();

         // Button to open modal (attached to the Continue button)
         document.querySelector('#selectedPatientInfo button').addEventListener('click', openVisitModal);

         // Close modal when clicking outside
         window.addEventListener('click', (e) => {
            if (e.target === visitDetailsModal) {
               closeVisitModal();
            }
         });
      });
   </script>
</body>

</html>