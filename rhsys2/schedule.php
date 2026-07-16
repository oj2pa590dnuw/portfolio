<?php
require 'likod/auth_guard.php';
require 'likod/session_utils.php';
enforce_role(['is_bhw', 'is_bns', 'is_midwife', 'is_admin']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Health Schedules</title>
    <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="app-container">
        <?php include 'navbar.php'; ?>

        <div class="main-content">
            <div class="large-container">
                <div class="nav-links">
                    <h1>🗓️ Health Center Schedules</h1>
                    <div class="schedule-view-toggles">
                        <button id="showUpcomingBtn" class="btn btn-primary active-view">Upcoming & Pending</button>
                        <button id="showCompletedBtn" class="btn btn-secondary">Completed Schedules</button>
                        <button id="addScheduleBtn" class="btn btn-primary">
                            + Add New Schedule
                        </button>
                    </div>
                </div>

                <div id="pageAlertContainer"></div>

                <div class="controls-bar">
                    <div class="search-box">
                        <label for="scheduleSearch">Search Schedule/Patient</label>
                        <input type="text" id="scheduleSearch" class="form-input"
                            placeholder="E.g., Maria Santos, Immunization">
                    </div>
                    <div class="sort-filter">
                        <label for="typeFilter">Filter by Type</label>
                        <select id="typeFilter" class="form-select">
                            <option value="all">All Types</option>
                            <option value="Prenatal Checkup">Prenatal Checkup</option>
                            <option value="Immunization Drive">Immunization Drive</option>
                            <option value="General Checkup">General Checkup</option>
                            <option value="Infant Follow-up">Infant Follow-up</option>
                            <option value="Family Planning">Family Planning</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="action-buttons">
                        <button id="applyFilterBtn" class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Patient/Group Name</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleTableBody">
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #555;">Loading
                                    schedules...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('scheduleModal')">&times;</span>
            <h2 id="modalTitleText">Add New Schedule</h2>
            <form id="scheduleForm" class="form-container">
                <input type="hidden" id="scheduleId" value="">

                <div class="form-item">
                    <label for="patientSelect" class="form-label">Tag to Patient ID (Optional)</label>
                    <select id="patientSelect" class="form-select">
                        <option value="">-- General Event / Not Tagged --</option>
                    </select>
                </div>
                <div class="form-item">
                    <label for="patientName" class="form-label">Event Name / Untagged Patient</label>
                    <input type="text" id="patientName" class="form-input" required>
                </div>

                <div class="form-grid">
                    <div class="form-item">
                        <label for="scheduleDate" class="form-label">Date</label>
                        <input type="date" id="scheduleDate" class="form-input" required>
                    </div>
                    <div class="form-item">
                        <label for="scheduleTime" class="form-label">Time</label>
                        <input type="time" id="scheduleTime" class="form-input" required>
                    </div>
                </div>

                <div class="form-item">
                    <label for="scheduleType" class="form-label">Schedule Type</label>
                    <select id="scheduleType" class="form-select" required>
                        <option value="">-- Select Type --</option>
                        <option value="Prenatal Checkup">Prenatal Checkup</option>
                        <option value="Immunization Drive">Immunization Drive</option>
                        <option value="General Checkup">General Checkup</option>
                        <option value="Infant Follow-up">Infant Follow-up</option>
                        <option value="Family Planning">Family Planning</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-item">
                    <label for="scheduleNotes" class="form-label">Notes / Description</label>
                    <textarea id="scheduleNotes" class="form-textarea" rows="3"
                        placeholder="Brief description of the appointment or event..."></textarea>
                </div>

                <div class="action-buttons">
                    <button type="button" id="cancelModalBtn" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <div id="actionModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('actionModal')">&times;</span>
            <h2 id="actionModalTitle"></h2>
            <p id="actionModalMessage"></p>
            <div class="action-buttons">
                <button id="actionModalCancel" class="btn btn-secondary">Cancel</button>
                <button id="actionModalConfirm" class="btn btn-danger">Confirm</button>
            </div>
        </div>
    </div>

    <div id="messageBox" class="message-box"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const API_BASE_URL = "likod/";
            let allSchedules = [];
            let currentAction = {};
            let currentView = 'upcoming';

            function showMessage(message, success = true) {
                const msgBox = document.getElementById('messageBox');
                msgBox.textContent = message;
                msgBox.className = success ? 'message-box message-success show' : 'message-box message-error show';
                setTimeout(() => msgBox.classList.remove('show'), 3000);
            }

            function showPageAlert(message, type = 'error') {
                const alertContainer = document.getElementById('pageAlertContainer');
                const alertClass = type === 'error' ? 'message-error' : 'message-success';
                alertContainer.innerHTML = `<div class="message-box ${alertClass} show">${message}</div>`;
                setTimeout(() => alertContainer.innerHTML = '', 5000);
            }

            function closeModal(modalId) {
                document.getElementById(modalId).style.display = 'none';
            }

            function openModal(modalId) {
                document.getElementById(modalId).style.display = 'flex';
            }

            async function fetchAPI(url, options = {}) {
                try {
                    const response = await fetch(API_BASE_URL + url, options);
                    const data = await response.json();
                    return data;
                } catch (error) {
                    console.error("Fetch API Error:", error.message);
                    throw error;
                }
            }

            async function loadPatients() {
                const patientSelect = document.getElementById('patientSelect');
                patientSelect.innerHTML = '<option value="">-- Loading Patients --</option>';
                try {
                    // NOTE: get_patients.php returns an array directly, not {data: []}
                    const data = await fetchAPI('get_patients.php');
                    patientSelect.innerHTML = '<option value="">-- General Event / Not Tagged --</option>';

                    // Check if data is an array or object with data property
                    // Adjusted for how get_patients.php returns its data
                    const patients = Array.isArray(data) ? data : data.data || [];

                    patients.forEach(p => {
                        const option = document.createElement('option');
                        // Use patient_id from the database
                        option.value = p.patient_id;
                        // Use fullName from the database or construct a fallback name
                        option.textContent = p.fullName || p.name || `Patient #${p.patient_id}`;
                        patientSelect.appendChild(option);
                    });
                } catch (error) {
                    console.error("Failed to load patients:", error);
                    patientSelect.innerHTML = '<option value="">-- Failed to Load Patients --</option>';
                }
            }

            // Function to setup action modal
            function setupActionModal(id, patientName, action) {
                currentAction = {
                    id,
                    patient: patientName,
                    action
                };
                const modalTitle = document.getElementById('actionModalTitle');
                const modalMessage = document.getElementById('actionModalMessage');

                if (action === 'Delete') {
                    modalTitle.textContent = 'Delete Schedule';
                    modalMessage.textContent =
                        `Are you sure you want to delete the schedule for "${patientName}"? This action cannot be undone.`;
                } else if (action === 'Complete') {
                    modalTitle.textContent = 'Mark as Completed';
                    modalMessage.textContent =
                        `Are you sure you want to mark the schedule for "${patientName}" as completed?`;
                }

                openModal('actionModal');
            }

            async function fetchSchedules() {
                try {
                    // FIX #1: Renamed the file to match the provided file (get_schedule.php)
                    const response = await fetchAPI('get_schedule.php');
                    return response.data || response || [];
                } catch (error) {
                    console.error("Failed to fetch schedules:", error);
                    showPageAlert(`Failed to load schedules: ${error.message}`, 'error');
                    return [];
                }
            }

            async function submitSchedule(scheduleData) {
                const isUpdate = !!scheduleData.id;
                // NOTE: update_schedule.php handles both partial and full updates now
                const endpoint = isUpdate ? 'update_schedule.php' : 'add_schedule.php';

                // Prepare payload based on endpoint
                let payload;
                if (isUpdate) {
                    payload = {
                        id: scheduleData.id,
                        date: scheduleData.date,
                        time: scheduleData.time,
                        patient: scheduleData.patient, // maps to 'title'
                        notes: scheduleData.notes, // maps to 'description'
                        type: scheduleData.type,
                        patient_id: scheduleData.patient_id || ''
                    };
                } else {
                    payload = {
                        // Backends expects schedule_date for ADD
                        schedule_date: scheduleData.date,
                        schedule_time: scheduleData.time,
                        title: scheduleData.patient,
                        description: scheduleData.notes,
                        schedule_type: scheduleData.type,
                        patient_id: scheduleData.patient_id || ''
                    };
                }

                const response = await fetchAPI(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                if (!response.success) {
                    throw new Error(response.message || 'Operation failed');
                }

                return response;
            }

            async function deleteSchedule(id) {
                const response = await fetchAPI('delete_schedule.php', {
                    method: 'POST', // Use POST for deletion as per your PHP file
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id
                    })
                });

                if (!response.success) {
                    throw new Error(response.message || 'Delete failed');
                }

                return response;
            }

            async function updateScheduleStatus(id, status) {
                const response = await fetchAPI('update_schedule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: id,
                        status: status
                    })
                });

                if (!response.success) {
                    throw new Error(response.message || 'Update failed');
                }

                return response;
            }

            function createScheduleRow(schedule) {
                const status = schedule.status || 'Pending';
                const statusClass = `status-${status.toLowerCase()}`;
                const isCompleted = status === 'Completed';
                const isCancelled = status === 'Cancelled';
                const isCompletedOrCancelled = isCompleted || isCancelled;

                // FIX #3: Use the aliased properties (date, time, patient, notes)
                let displayTime = schedule.time || '';
                try {
                    if (displayTime) {
                        const [hour, minute] = displayTime.split(':');
                        const h = parseInt(hour);
                        const ampm = h >= 12 ? 'PM' : 'AM';
                        const displayH = h % 12 || 12;
                        displayTime = `${displayH}:${minute} ${ampm}`;
                    }
                } catch (e) {
                    displayTime = schedule.time || 'N/A';
                }

                // Get display date
                const displayDate = schedule.date || '';
                const patientName = schedule.patient || 'No Name'; // Use aliased 'patient'

                // Show complete button only for upcoming view and not completed/cancelled
                const showCompleteBtn = currentView === 'upcoming' && !isCompletedOrCancelled;

                return `
            <tr class="${isCompletedOrCancelled ? 'completed' : ''}">
                <td>
                    <strong>${patientName}</strong>
                    ${schedule.patient_id ? `<div style="font-size: 0.8em; color: green;">(ID: ${schedule.patient_id})</div>` : ''}
                    ${schedule.notes ? `<div style="font-size: 0.8em; color: #666;">${schedule.notes || ''}</div>` : ''}
                </td>
                <td>${displayDate}</td>
                <td>${displayTime}</td>
                <td>${schedule.type || 'N/A'}</td>
                <td><span class="status-badge ${statusClass}">${status}</span></td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-primary edit-btn" data-id="${schedule.id}">Edit</button>
                        ${showCompleteBtn ? `<button class="btn btn-primary complete-btn" data-id="${schedule.id}">Complete</button>` : ''}
                        <button class="btn btn-danger delete-btn" data-id="${schedule.id}">Delete</button>
                    </div>
                </td>
            </tr>
        `;
            }

            function displaySchedules(schedules) {
                const tableBody = document.getElementById('scheduleTableBody');
                tableBody.innerHTML = '';

                if (schedules.length === 0) {
                    tableBody.innerHTML =
                        '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #555;">No schedules found for this view.</td></tr>';
                    return;
                }

                schedules.forEach(schedule => {
                    tableBody.innerHTML += createScheduleRow(schedule);
                });
            }

            async function loadSchedules() {
                const tableBody = document.getElementById('scheduleTableBody');
                tableBody.innerHTML =
                    '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #555;">Loading schedules...</td></tr>';

                try {
                    allSchedules = await fetchSchedules();

                    // Filter based on current view
                    let filteredByView = allSchedules;
                    if (currentView === 'upcoming') {
                        // Show pending and upcoming schedules (not completed or cancelled)
                        filteredByView = allSchedules.filter(s =>
                            s.status !== 'Completed' && s.status !== 'Cancelled'
                        );
                    } else if (currentView === 'completed') {
                        // Show completed and cancelled schedules
                        filteredByView = allSchedules.filter(s =>
                            s.status === 'Completed' || s.status === 'Cancelled'
                        );
                    }

                    // Apply search and type filters
                    const searchTerm = document.getElementById('scheduleSearch').value.toLowerCase();
                    const typeFilterValue = document.getElementById('typeFilter').value;

                    const filteredSchedules = filteredByView.filter(schedule => {
                        // Use the aliased properties from get_schedule.php
                        const patientName = schedule.patient || '';
                        const notes = schedule.notes || '';
                        const type = schedule.type || '';
                        const patientId = schedule.patient_id || ''; // Now available!

                        const matchesSearch = !searchTerm ||
                            patientName.toLowerCase().includes(searchTerm) ||
                            (patientId && String(patientId).toLowerCase().includes(searchTerm)) ||
                            notes.toLowerCase().includes(searchTerm) ||
                            type.toLowerCase().includes(searchTerm);

                        const matchesType = typeFilterValue === 'all' ||
                            (type && type === typeFilterValue);

                        return matchesSearch && matchesType;
                    });

                    displaySchedules(filteredSchedules);
                } catch (error) {
                    showPageAlert(`Failed to load schedules. Details: ${error.message}`, 'error');
                    tableBody.innerHTML =
                        '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #555;">Schedule loading failed. See the error alert above.</td></tr>';
                }
            }

            function setupScheduleModal(schedule = null) {
                document.getElementById('scheduleForm').reset();

                // --- FIX: Set Minimum Date for Input ---
                const today = new Date();
                const yyyy = today.getFullYear();
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const dd = String(today.getDate()).padStart(2, '0');
                const minDate = `${yyyy}-${mm}-${dd}`;

                const scheduleDateInput = document.getElementById('scheduleDate');
                scheduleDateInput.min = minDate;
                // -------------------------------------

                if (schedule) {
                    document.getElementById('modalTitleText').textContent = `Edit Schedule`;
                    // Use aliased properties
                    document.getElementById('scheduleId').value = schedule.id || '';
                    document.getElementById('scheduleDate').value = schedule.date || '';
                    document.getElementById('scheduleTime').value = schedule.time || '';
                    document.getElementById('scheduleType').value = schedule.type || '';
                    document.getElementById('scheduleNotes').value = schedule.notes || '';

                    // Set patient ID dropdown and patient name field
                    const patientId = schedule.patient_id || '';
                    document.getElementById('patientSelect').value = patientId;

                    // If there's a patient ID, clear the patient name field (it comes from patient record)
                    // Otherwise, use the title/patient name
                    if (patientId) {
                        document.getElementById('patientName').value = '';
                    } else {
                        // Use aliased 'patient'
                        document.getElementById('patientName').value = schedule.patient || '';
                    }
                } else {
                    document.getElementById('modalTitleText').textContent = 'Add New Schedule';
                    document.getElementById('scheduleId').value = '';
                    document.getElementById('patientName').value = '';
                    document.getElementById('patientSelect').value = '';
                    // Default new date to today's date, which is the minimum allowed
                    document.getElementById('scheduleDate').value = minDate;
                    document.getElementById('scheduleTime').value = '';
                    document.getElementById('scheduleType').value = '';
                    document.getElementById('scheduleNotes').value = '';
                }
                openModal('scheduleModal');
            }

            async function handleFormSubmission(e) {
                e.preventDefault();

                const patientId = document.getElementById('patientSelect').value;
                let patientName = document.getElementById('patientName').value.trim();
                const scheduleDate = document.getElementById('scheduleDate').value;

                // Validation: If no patient ID is selected, patient name is required
                if (!patientId && !patientName) {
                    showMessage("Please enter an Event Name or select a Patient ID.", false);
                    return;
                }

                // --- FIX: Client-side Past Date Validation ---
                const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
                if (scheduleDate < today) {
                    showMessage(
                        "Cannot set schedule to a date that has already passed. Please select a future or current date.",
                        false);
                    return;
                }
                // ---------------------------------------------


                // FIX #4: If a patient ID is selected, ensure we clear patientName 
                // so the backend sets the 'title' field to an empty string.
                if (patientId) {
                    patientName = '';
                }

                const scheduleData = {
                    id: document.getElementById('scheduleId').value,
                    date: scheduleDate, // Use the validated date
                    time: document.getElementById('scheduleTime').value,
                    patient: patientName, // Will be empty if patientId is set
                    notes: document.getElementById('scheduleNotes').value,
                    type: document.getElementById('scheduleType').value,
                    patient_id: patientId || '' // Send the ID or an empty string
                };

                try {
                    const data = await submitSchedule(scheduleData);
                    showMessage(data.message, true);
                    closeModal('scheduleModal');
                    loadSchedules();
                } catch (error) {
                    // Check if error is due to backend date validation
                    const message = error.message.includes('date that has already passed') ?
                        error.message : `Action Failed: ${error.message}`;
                    showMessage(message, false);
                }
            }

            async function handleConfirmAction() {
                const {
                    id,
                    patient,
                    action
                } = currentAction;
                if (!id) return;
                closeModal('actionModal');
                try {
                    let data;
                    if (action === 'Delete') {
                        data = await deleteSchedule(id);
                    } else if (action === 'Complete') {
                        data = await updateScheduleStatus(id, 'Completed');
                    }
                    showMessage(data.message, true);
                    loadSchedules();
                } catch (error) {
                    showMessage(`Action Failed: ${error.message}`, false);
                } finally {
                    currentAction = {};
                }
            }

            // Event Listeners
            document.getElementById('addScheduleBtn').addEventListener('click', () => setupScheduleModal());
            document.getElementById('cancelModalBtn').addEventListener('click', () => closeModal('scheduleModal'));
            document.getElementById('actionModalCancel').addEventListener('click', () => closeModal('actionModal'));
            document.getElementById('actionModalConfirm').addEventListener('click', handleConfirmAction);
            document.getElementById('scheduleForm').addEventListener('submit', handleFormSubmission);
            document.getElementById('applyFilterBtn').addEventListener('click', loadSchedules);
            document.getElementById('scheduleSearch').addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    loadSchedules();
                }
            });

            // View Toggle Listeners
            document.getElementById('showUpcomingBtn').addEventListener('click', () => {
                currentView = 'upcoming';
                document.getElementById('showUpcomingBtn').classList.add('active-view');
                document.getElementById('showCompletedBtn').classList.remove('active-view');
                loadSchedules();
            });

            document.getElementById('showCompletedBtn').addEventListener('click', () => {
                currentView = 'completed';
                document.getElementById('showCompletedBtn').classList.add('active-view');
                document.getElementById('showUpcomingBtn').classList.remove('active-view');
                loadSchedules();
            });

            document.getElementById('scheduleTableBody').addEventListener('click', (e) => {
                const target = e.target;
                const scheduleId = target.dataset.id;
                if (!scheduleId) return;

                const schedule = allSchedules.find(s =>
                    // Check against the aliased 'id' property
                    String(s.id) === String(scheduleId)
                );
                if (!schedule) return;

                const patientName = schedule.patient || 'Unknown';
                if (target.classList.contains('delete-btn')) {
                    setupActionModal(scheduleId, patientName, 'Delete');
                } else if (target.classList.contains('edit-btn')) {
                    setupScheduleModal(schedule);
                } else if (target.classList.contains('complete-btn')) {
                    setupActionModal(scheduleId, patientName, 'Complete');
                }
            });

            window.addEventListener('click', (event) => {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            });

            // Initial load
            loadPatients();
            loadSchedules();
        });
    </script>
</body>

</html>