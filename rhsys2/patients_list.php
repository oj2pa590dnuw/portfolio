<?php
require_once __DIR__ . '/likod/auth_guard.php';
// Removed the 'offline-helper.js' script inclusion since caching is gone
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Patient List and Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="app-container">
        <?php include 'navbar.php'; ?>

        <div class="main-content">
            <div class="large-container">
                <div class="nav-links">
                    <a href="dashboard.php" class="btn btn-secondary">← Go to Dashboard</a>
                    <a href="add_patients.php" class="btn btn-primary" style="margin-left: auto;">+ Add New Patient</a>
                </div>

                <h1>Patient List</h1>

                <div id="filterTag" class="status-summary" style="display: none;"></div>

                <div class="controls-bar">
                    <div class="search-box">
                        <label for="searchInput">Search:</label>
                        <input type="text" id="searchInput" class="form-input"
                            placeholder="Search by name or location...">
                    </div>
                    <div class="sort-filter">
                        <label for="sortSelect">Sort by:</label>
                        <select id="sortSelect" class="form-select">
                            <option value="name_asc">Name (A-Z)</option>
                            <option value="name_desc">Name (Z-A)</option>
                            <option value="status">Status</option>
                            <option value="location_asc">Location (A-Z)</option>
                            <option value="location_desc">Location (Z-A)</option>
                            <option value="last_checkup_desc">Last Checkup (Newest)</option>
                            <option value="last_checkup_asc">Last Checkup (Oldest)</option>
                        </select>
                    </div>
                </div>

                <div class="status-summary">
                    Showing: <span id="resultsInfo">Loading...</span>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Last Checkup</th>
                                <th>Chief Complaint</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="patientTableBody">
                        </tbody>
                    </table>
                </div>

                <div id="loadingMessage" class="loading">
                    <p>Loading patient data...</p>
                </div>

                <div id="noResultsMessage" class="empty-state" style="display: none;">
                    <p>No patients found matching your criteria.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let allPatients = [];
        let filteredPatients = [];

        function loadPatientList() {
            const api_url = "likod/get_patients.php";
            const params = new URLSearchParams(window.location.search);
            const filter = params.get("filter");

            const loadingMessage = document.getElementById('loadingMessage');
            const tableBody = document.getElementById('patientTableBody');
            const resultsInfo = document.getElementById('resultsInfo');
            const noResultsMessage = document.getElementById('noResultsMessage');

            // Show loading indicator
            loadingMessage.style.display = 'block';
            tableBody.innerHTML = '';
            noResultsMessage.style.display = 'none';
            resultsInfo.textContent = 'Loading...';

            // *** Straight-up load everything from the server ***
            fetch(api_url)
                .then(response => {
                    if (!response.ok) {
                        // Throw an error if the server response is not okay (e.g., 404, 500)
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Hide loading indicator
                    loadingMessage.style.display = 'none';

                    if (data && Array.isArray(data) && data.length > 0) {
                        processPatientData(data, filter);
                    } else {
                        // Handle empty but successful response
                        showNoResults('No patient data found on the server.');
                        allPatients = []; // Clear patient data if none is returned
                        filterAndDisplayPatients(); // Update the display
                    }
                })
                .catch(error => {
                    // Handle network or server errors
                    loadingMessage.style.display = 'none';
                    console.error('Failed to load patient data from server:', error);
                    showNoResults('Could not load patient data. Check server connection.');
                    // On a failed load, we clear out the table so users don't see old data
                    allPatients = [];
                    filterAndDisplayPatients();
                });
        }

        // *** Removed loadFromCache and loadFromCache functions ***

        function showNoResults(message) {
            const tableBody = document.getElementById('patientTableBody');
            const noResultsMessage = document.getElementById('noResultsMessage');
            const resultsInfo = document.getElementById('resultsInfo');

            tableBody.innerHTML = '';
            tableBody.style.display = 'none';
            noResultsMessage.style.display = 'block';
            noResultsMessage.querySelector('p').textContent = message; // Use the message parameter
            resultsInfo.textContent = 'No patients found';
        }


        function processPatientData(data, filter) {
            const loadingMessage = document.getElementById('loadingMessage');
            loadingMessage.style.display = 'none';

            allPatients = Array.isArray(data) ? data : [];

            // Apply filter if any
            if (filter) {
                // ... (Your filter logic remains the same)
                allPatients = allPatients.filter(patient => {
                    const age = parseInt(patient.age) || 0;
                    switch (filter) {
                        case "critical":
                            return patient.isCritical === "1" || patient.isCritical === 1;
                        case "pregnant":
                            return patient.isPregnant === "1" || patient.isPregnant === 1;
                        case "warning":
                            return patient.isWarningFlag === "1" || patient.isWarningFlag === 1;
                        case "senior":
                            return age >= 60;
                        case "pediatric":
                            return age < 18;
                        case "meds":
                            return patient.needsMedication === "1" || patient.needsMedication === 1;
                        default:
                            return true;
                    }
                });
            }

            applySort();
            filterAndDisplayPatients();
        }

        function getStatusInfo(patient) {
            if (patient.isCritical === '1' || patient.isCritical === 1) return {
                text: 'Critical',
                class: 'status-critical'
            };
            if (patient.isPregnant === '1' || patient.isPregnant === 1) return {
                text: 'Pregnant',
                class: 'status-pregnant'
            };
            if (patient.isWarningFlag === '1' || patient.isWarningFlag === 1) return {
                text: 'Warning',
                class: 'status-warning'
            };
            if (patient.needsMedication === '1' || patient.needsMedication === 1) return {
                text: 'Needs Medication',
                class: 'status-meds'
            };
            if (parseInt(patient.age) >= 60) return {
                text: 'Senior',
                class: 'status-senior'
            };
            if (parseInt(patient.age) < 18) return {
                text: 'Pediatric',
                class: 'status-pediatric'
            };
            return {
                text: 'Stable',
                class: 'status-stable'
            };
        }

        function applySort() {
            const sortValue = document.getElementById('sortSelect').value;
            allPatients.sort((a, b) => {
                // Your existing sort logic
                switch (sortValue) {
                    case 'name_asc':
                        return (a.fullName || '').localeCompare(b.fullName || '');
                    case 'name_desc':
                        return (b.fullName || '').localeCompare(a.fullName || '');
                    case 'status': {
                        const statusA = getStatusInfo(a).text;
                        const statusB = getStatusInfo(b).text;
                        return statusA.localeCompare(statusB);
                    }
                    case 'location_asc':
                        return (a.location || '').localeCompare(b.location || '');
                    case 'location_desc':
                        return (b.location || '').localeCompare(a.location || '');
                    case 'last_checkup_desc':
                        return (b.lastCheckup || '').localeCompare(a.lastCheckup || '');
                    case 'last_checkup_asc':
                        return (a.lastCheckup || '').localeCompare(b.lastCheckup || '');
                    default:
                        return (a.fullName || '').localeCompare(b.fullName || '');
                }
            });
        }

        function filterAndDisplayPatients() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const tableBody = document.getElementById('patientTableBody');
            const noResultsMessage = document.getElementById('noResultsMessage');
            const resultsInfo = document.getElementById('resultsInfo');

            filteredPatients = allPatients.filter(patient => {
                const nameMatch = (patient.fullName || '').toLowerCase().includes(searchTerm);
                const locationMatch = (patient.location || '').toLowerCase().includes(searchTerm);
                // Also check Chief Complaint for a more robust search
                const complaintMatch = (patient.title || '').toLowerCase().includes(searchTerm);
                return nameMatch || locationMatch || complaintMatch;
            });

            tableBody.innerHTML = '';

            if (filteredPatients.length === 0) {
                // If there are patients in allPatients but no matching filter/search results
                if (allPatients.length > 0) {
                    showNoResults('No patients found matching your search or filter criteria.');
                } else {
                    // If allPatients is empty (e.g., failed server load or empty response)
                    // The showNoResults call in loadPatientList should handle this, 
                    // but this is a fallback for the search/filter logic.
                    showNoResults('No patients found.');
                }
            } else {
                tableBody.style.display = '';
                noResultsMessage.style.display = 'none';

                // This updates the info based on the *filtered* list against the *full* list
                resultsInfo.textContent = `Showing ${filteredPatients.length} of ${allPatients.length} patients`;

                filteredPatients.forEach(patient => {
                    const statusInfo = getStatusInfo(patient);
                    const row = document.createElement('tr');

                    row.innerHTML = `
                <td><strong>${patient.fullName || 'Unnamed Patient'}</strong>${patient.age ? `<br><small>Age: ${patient.age}</small>` : ''}</td>
                <td><span class="status-badge ${statusInfo.class}">${statusInfo.text}</span></td>
                <td>${patient.location || 'Unknown'}</td>
                <td>${patient.lastCheckup || 'N/A'}</td>
                <td>${patient.title || 'None'}</td>
                <td><a href="view_patient.php?id=${patient.id}" class="action-link">View/Edit</a></td>
            `;

                    tableBody.appendChild(row);
                });
            }
        }

        // *** Removed simple sync function as it's not needed without offline data ***
        // function syncOfflineData() { offlineHelper.syncAll(); }

        // Event listeners
        document.getElementById('searchInput').addEventListener('input', filterAndDisplayPatients);
        document.getElementById('sortSelect').addEventListener('change', function () {
            applySort();
            filterAndDisplayPatients();
        });

        // *** Removed auto-refresh when coming online (window.addEventListener('online', ...)) ***

        // Initial load
        loadPatientList();
    </script>
</body>

</html>