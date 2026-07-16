<?php
require 'likod/auth_guard.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Overview</title>
    <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="app-container">
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="large-container">
            <h1>Barangay Zabali Health Center</h1>
            <h2>🏥 Patient Dashboard</h2>

            <div class="stats-container">
                <div class="stat-box total" onclick="window.location.href='patients_list.php'">
                    <div class="stat-title">Total Patients</div>
                    <div id="totalPatients" class="stat-number">0</div>
                </div>
                <div class="stat-box critical" onclick="window.location.href='patients_list.php?filter=critical'">
                    <div class="stat-title">Critical Patients</div>
                    <div id="criticalCount" class="stat-number">0</div>
                </div>
                <div class="stat-box pregnant" onclick="window.location.href='patients_list.php?filter=pregnant'">
                    <div class="stat-title">Pregnant Patients</div>
                    <div id="pregnantCount" class="stat-number">0</div>
                </div>
                <div class="stat-box warning" onclick="window.location.href='patients_list.php?filter=warning'">
                    <div class="stat-title">Warning Flags</div>
                    <div id="warningFlagCount" class="stat-number">0</div>
                </div>
                <div class="stat-box senior" onclick="window.location.href='patients_list.php?filter=senior'">
                    <div class="stat-title">Senior Citizens (60+)</div>
                    <div id="seniorCount" class="stat-number">0</div>
                </div>
                <div class="stat-box pediatric" onclick="window.location.href='patients_list.php?filter=pediatric'">
                    <div class="stat-title">Pediatric Patients (&lt;18)</div>
                    <div id="pediatricCount" class="stat-number">0</div>
                </div>
                <div class="stat-box meds" onclick="window.location.href='patients_list.php?filter=meds'">
                    <div class="stat-title">Needs Medication</div>
                    <div id="medicationCount" class="stat-number">0</div>
                </div>
            </div>

            <h2>🚨 Urgent Alerts (Critical, Warning, Medication Needed)</h2>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Status</th>
                            <th>Details</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="alertsTableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #555;">
                                Loading alerts...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// The only remaining JavaScript logic: fetching and displaying live data.
function loadDashboardStats() {
    const API_PATH = 'likod/get_counts.php'; 
    fetch(API_PATH)
        .then(response => {
            // Check if response is 200 OK and JSON
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            document.getElementById("totalPatients").textContent = data.totalPatients;
            document.getElementById("criticalCount").textContent = data.criticalCount;
            document.getElementById("pregnantCount").textContent = data.pregnantCount;
            document.getElementById("warningFlagCount").textContent = data.warningFlagCount;
            document.getElementById("medicationCount").textContent = data.medicationCount;
            document.getElementById("seniorCount").textContent = data.seniorCount;
            document.getElementById("pediatricCount").textContent = data.pediatricCount;
        })
        .catch(error => console.error("Error loading dashboard stats:", error));
}

function loadAlerts() {
    const API_PATH = 'likod/get_alerts.php';
    const tableBody = document.getElementById('alertsTableBody');
    
    fetch(API_PATH)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Check if the API returned a "No alerts" message
            if (data.message) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #555;">
                            ✅ ${data.message}
                        </td>
                    </tr>
                `;
                return;
            }

            // Also check for an empty array if the API successfully returns an empty list
             if (!Array.isArray(data) || data.length === 0) {
                 tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #555;">
                            ✅ No patients currently require urgent attention.
                        </td>
                    </tr>
                `;
                return;
            }
            
            // Proceed to render the actual alerts
            tableBody.innerHTML = '';
            data.forEach(patient => {
                let statusClass = '';
                let statusText = '';
                let alertDetails = '';

                if (patient.isCritical === '1') {
                    statusClass = 'status-critical';
                    statusText = 'CRITICAL';
                    alertDetails = patient.title || 'N/A';
                } else if (patient.isWarningFlag === '1') {
                    statusClass = 'status-warning';
                    statusText = 'WARNING';
                    alertDetails = patient.title || 'N/A';
                } else if (patient.needsMedication === '1') {
                    statusClass = 'status-meds';
                    statusText = 'NEEDS MEDS';
                    alertDetails = patient.medication || 'Check Records';
                }

                const row = document.createElement('tr');
                row.className = 'alert-row';
                row.innerHTML = `
                	<td>
                        <strong>${patient.fullName}</strong>
                    </td>
                    <td>
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </td>
                    <td>${alertDetails}</td>
                    <td>${patient.location || 'Unknown'}</td>
                    <td>
                        <a href="view_patient.php?id=${patient.id}" class="action-link">View</a>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error fetching alerts:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--crit-color);">
                        ⚠️ Failed to load alert data. Please check the console for details.
                    </td>
                </tr>
            `;
        });
}

// Run both functions on load
document.addEventListener('DOMContentLoaded', () => {
    loadDashboardStats();
    loadAlerts();
});

</script>
</body>
</html>