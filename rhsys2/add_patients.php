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
    <title>Add New Patient</title>
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

                <h1>Register New Patient</h1>

                <form id="addPatientForm" class="form-container">
                    <div class="data-grid">

                        <div class="category-panel">
                            <h3>👤 Personal Information</h3>
                            <div class="form-item">
                                <label class="form-label">First Name</label>
                                <input type="text" name="firstName" class="form-input">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middleName" class="form-input">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="lastName" class="form-input">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Age</label>
                                <input type="number" name="age" class="form-input" min="0" max="150">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Location/Address</label>
                                <input type="text" name="location" class="form-input">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contactNumber" class="form-input" maxlength="11">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Birth Date</label>
                                <input type="date" name="birthDate" class="form-input">
                            </div>
                        </div>

                        <!-- Identification -->
                        <div class="category-panel">
                            <h3>🆔 Identification</h3>
                            <div class="form-item">
                                <label class="form-label">PhilHealth ID</label>
                                <input type="text" name="philhealth_id" class="form-input" placeholder="02-123456789-1">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Local Patient ID</label>
                                <input type="text" name="local_patient_id" class="form-input"
                                    placeholder="MUN-2024-001">
                            </div>
                        </div>

                        <!-- Vitals & Measurements -->
                        <div class="category-panel">
                            <h3>💓 Vitals & Measurements</h3>
                            <div class="form-item">
                                <label class="form-label">Blood Pressure</label>
                                <input type="text" name="bloodPressure" class="form-input" placeholder="120/80">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Heart Rate</label>
                                <input type="text" name="heartRate" class="form-input" placeholder="75 bpm">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Respiratory Rate</label>
                                <input type="text" name="respiratoryRate" class="form-input"
                                    placeholder="16 breaths/min">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Temperature</label>
                                <input type="text" name="temperature" class="form-input" placeholder="36.5 °C">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Weight</label>
                                <input type="text" name="weight" class="form-input" placeholder="kg">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Height</label>
                                <input type="text" name="height" class="form-input" placeholder="cm">
                            </div>
                        </div>

                        <!-- Medical Flags -->
                        <div class="category-panel">
                            <h3>🚩 Medical Status Flags</h3>
                            <div class="checkbox-grid">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="isPregnant" value="1">
                                    <label>🤰 Pregnant</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="isElderly" value="1">
                                    <label>👵 Elderly</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="hasHighBP" value="1">
                                    <label>🩺 High BP</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="needsMedication" value="1">
                                    <label>💊 Needs Meds</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="isCritical" value="1">
                                    <label>🚨 Critical</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="isWarningFlag" value="1">
                                    <label>⚠️ Warning Flag</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="isStable" value="1" checked>
                                    <label>✅ Stable</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="needsAppointment" value="1">
                                    <label>📅 Needs Follow-up</label>
                                </div>
                            </div>
                        </div>

                        <!-- Clinical Information -->
                        <div class="category-panel">
                            <h3>🏥 Clinical Information</h3>
                            <div class="form-item">
                                <label class="form-label">Case Title</label>
                                <input type="text" name="title" class="form-input">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Last Checkup Date</label>
                                <input type="date" name="lastCheckup" class="form-input">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Checkup Time</label>
                                <input type="time" name="time" class="form-input">
                            </div>
                        </div>

                        <!-- Notes & Additional Info -->
                        <div class="category-panel">
                            <h3>📝 Notes & Additional Information</h3>
                            <div class="form-item">
                                <label class="form-label">Patient Description</label>
                                <textarea name="description" class="form-textarea" rows="3"></textarea>
                            </div>
                            <div class="form-item">
                                <label class="form-label">Clinical Notes</label>
                                <textarea name="clinicalNotes" class="form-textarea" rows="4"></textarea>
                            </div>
                            <div class="form-item">
                                <label class="form-label">Warning Details</label>
                                <input type="text" name="warning" class="form-input"
                                    placeholder="E.g., High Risk Pregnancy">
                            </div>
                            <div class="form-item">
                                <label class="form-label">Other Information</label>
                                <textarea name="otherInfo" class="form-textarea" rows="3"></textarea>
                            </div>
                            <div class="form-item">
                                <label class="form-label">Normal Ranges Reference</label>
                                <textarea name="normalRanges" class="form-textarea" rows="2"
                                    placeholder="e.g., BP: 120/80, HR: 60-100"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">👤 Register Patient</button>
                        <a href="patients_list.php" class="btn btn-secondary">↩ Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="messageBox" class="message-box"></div>

    <script>
        function showMessage(message, success = true) {
            const msgBox = document.getElementById('messageBox');
            msgBox.textContent = message;
            msgBox.className = success ? 'message-box message-success show' : 'message-box message-error show';
            setTimeout(() => msgBox.classList.remove('show'), 3000);
        }

        // Simple form submission - send as FormData instead of JSON
        document.getElementById('addPatientForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Registering...';
            submitBtn.disabled = true;

            try {
                // Get form data as FormData
                const formData = new FormData(e.target);

                // Convert FormData to URL-encoded string (like normal form submission)
                const urlEncodedData = new URLSearchParams(formData).toString();

                // Submit to backend
                const response = await fetch('likod/add_patient_backend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: urlEncodedData
                });

                const result = await response.json();

                if (result.success) {
                    showMessage(`✅ Patient registered successfully!`);
                    setTimeout(() => {
                        window.location.href = `view_patient.php?id=${result.patient_id}`;
                    }, 1500);
                } else {
                    throw new Error(result.message || 'Registration failed');
                }

            } catch (error) {
                console.error('Error:', error);
                showMessage(`❌ Error: ${error.message}`, false);
            } finally {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    </script>
</body>

</html>