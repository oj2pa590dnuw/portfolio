<?php
require 'likod/auth_guard.php';
require 'likod/session_utils.php'; 
enforce_role(['is_bns','is_midwife', 'is_admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Maternal & Child Health Module</title>
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

            <h1>Maternal & Child Health Records</h1>
            <p class="description">Filtered list of pregnant women and infants for focused care monitoring.</p>

            <!-- Controls Bar -->
            <div class="controls-bar">
                <div class="search-box">
                    <label for="searchInput">Search:</label>
                    <input type="text" id="searchInput" placeholder="Search by name or location..." class="form-input">
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
                <div class="action-buttons">
                    <button onclick="exportToDocx()" class="btn export-btn" id="exportBtn">
                        📄 Export to DOCX
                    </button>
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
                            <th>Age</th>
                            <th>Location</th>
                            <th>Last Checkup</th>
                            <th>Chief Complaint</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="mchTableBody">
                        <!-- MCH data will be loaded here -->
                    </tbody>
                </table>
            </div>

            <div id="loadingMessage" class="loading">
                <p>Loading Maternal & Child Health records...</p>
            </div>

            <div id="noResultsMessage" class="empty-state" style="display: none;">
                <p>No MCH records found matching your criteria.</p>
            </div>
        </div>
    </div>
</div>

<div id="messageBox" class="message-box"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/docx/7.8.2/docx.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
let allMCHPatients = [];
let filteredMCHPatients = [];
const API_BASE_URL = "likod/";

function loadMCHRecords() {
    const loadingMessage = document.getElementById('loadingMessage');
    const tableBody = document.getElementById('mchTableBody');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const resultsInfo = document.getElementById('resultsInfo');

    loadingMessage.style.display = 'block';
    tableBody.innerHTML = '';
    noResultsMessage.style.display = 'none';
    resultsInfo.textContent = 'Loading...';

    fetch(API_BASE_URL + 'get_mch_patients.php')
        .then(response => response.json())
        .then(data => {
            loadingMessage.style.display = 'none';

            if (!data || data.length === 0) {
                showNoResults();
                return;
            }

            allMCHPatients = data;
            applySort();
            filterAndDisplayMCHPatients();
        })
        .catch(error => {
            console.error('Error fetching MCH records:', error);
            loadingMessage.style.display = 'none';
            showNoResults('Failed to load MCH records. Please check your connection.');
        });
}

function getStatusInfo(patient) {
    if (patient.isPregnant === '1' || patient.isPregnant === 1) {
        return { text: 'Pregnant', class: 'status-pregnant' };
    } else if (patient.isCritical === '1' || patient.isCritical === 1) {
        return { text: 'Critical', class: 'status-critical' };
    } else if (patient.age <= 5) {
        return { text: 'Child/Infant', class: 'status-pediatric' };
    }
    return { text: 'Stable', class: 'status-stable' };
}

function applySort() {
    const sortValue = document.getElementById('sortSelect').value;

    allMCHPatients.sort((a, b) => {
        switch(sortValue) {
            case 'name_asc':
                return a.fullName.localeCompare(b.fullName);
            case 'name_desc':
                return b.fullName.localeCompare(a.fullName);
            case 'status':
                const statusOrder = { 'Pregnant': 0, 'Critical': 1, 'Child/Infant': 2, 'Stable': 3 };
                const statusA = getStatusInfo(a).text;
                const statusB = getStatusInfo(b).text;
                return statusOrder[statusA] - statusOrder[statusB] || a.fullName.localeCompare(b.fullName);
            case 'location_asc':
                return (a.location || '').localeCompare(b.location || '');
            case 'location_desc':
                return (b.location || '').localeCompare(a.location || '');
            case 'last_checkup_desc':
                return new Date(b.lastCheckup || 0) - new Date(a.lastCheckup || 0);
            case 'last_checkup_asc':
                return new Date(a.lastCheckup || 0) - new Date(b.lastCheckup || 0);
            default:
                return a.fullName.localeCompare(b.fullName);
        }
    });
}

function filterAndDisplayMCHPatients() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const tableBody = document.getElementById('mchTableBody');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const resultsInfo = document.getElementById('resultsInfo');

    filteredMCHPatients = allMCHPatients.filter(patient => {
        const nameMatch = patient.fullName.toLowerCase().includes(searchTerm);
        const locationMatch = (patient.location || '').toLowerCase().includes(searchTerm);
        const complaintMatch = (patient.title || '').toLowerCase().includes(searchTerm);
        return nameMatch || locationMatch || complaintMatch;
    });

    tableBody.innerHTML = '';

    if (filteredMCHPatients.length === 0) {
        tableBody.style.display = 'none';
        noResultsMessage.style.display = 'block';
        resultsInfo.textContent = 'No MCH records found';
    } else {
        tableBody.style.display = '';
        noResultsMessage.style.display = 'none';
        resultsInfo.textContent = `Showing ${filteredMCHPatients.length} of ${allMCHPatients.length} MCH records`;

        filteredMCHPatients.forEach((patient, index) => {
            const statusInfo = getStatusInfo(patient);
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td><strong>${patient.fullName}</strong></td>
                <td><span class="status-badge ${statusInfo.class}">${statusInfo.text}</span></td>
                <td>${patient.age || 'N/A'}</td>
                <td>${patient.location || 'Unknown'}</td>
                <td>${patient.lastCheckup || 'N/A'}</td>
                <td>${patient.title || 'None'}</td>
                <td>
                    <a href="view_patient.php?id=${patient.id}" class="action-link">View/Edit</a>
                </td>
            `;
            
            tableBody.appendChild(row);
        });
    }
}

function showNoResults(message = 'No MCH records found.') {
    const tableBody = document.getElementById('mchTableBody');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const resultsInfo = document.getElementById('resultsInfo');
    
    tableBody.innerHTML = '';
    tableBody.style.display = 'none';
    noResultsMessage.innerHTML = `<p>${message}</p>`;
    noResultsMessage.style.display = 'block';
    resultsInfo.textContent = '0 MCH records found';
}

async function exportToDocx() {
    const exportBtn = document.getElementById('exportBtn');
    const originalText = exportBtn.innerHTML;
    
    try {
        if (filteredMCHPatients.length === 0) {
            showMessage('⚠️ No MCH data available to export.', false);
            return;
        }

        exportBtn.disabled = true;
        exportBtn.innerHTML = '⏳ Exporting...';

        if (typeof docx === 'undefined') {
            throw new Error('DOCX library not loaded. Please check your internet connection.');
        }

        const { Document, Paragraph, TextRun, HeadingLevel, AlignmentType, Table, TableRow, TableCell, WidthType } = docx;
        
        const exportDate = new Date().toLocaleDateString('en-US', {
            year: 'numeric', month: 'long', day: 'numeric'
        });

        const children = [
            new Paragraph({
                text: "Barangay Zabali Health Center",
                heading: HeadingLevel.HEADING_1,
                alignment: AlignmentType.CENTER,
            }),
            new Paragraph({
                text: "Maternal & Child Health Records",
                heading: HeadingLevel.HEADING_2,
                alignment: AlignmentType.CENTER,
                spacing: { after: 400 }
            }),
            new Paragraph({
                text: `Exported on: ${exportDate} | Total Records: ${filteredMCHPatients.length}`,
                spacing: { after: 200 }
            })
        ];

        const tableRows = [
            new TableRow({
                children: [
                    'Patient Name', 'Status', 'Age', 'Location', 'Last Checkup', 'Chief Complaint'
                ].map(text => new TableCell({ 
                    children: [new Paragraph({ text, bold: true })] 
                }))
            })
        ];

        filteredMCHPatients.forEach(patient => {
            const statusInfo = getStatusInfo(patient);
            tableRows.push(
                new TableRow({
                    children: [
                        patient.fullName || 'Unnamed',
                        statusInfo.text,
                        String(patient.age || 'N/A'),
                        patient.location || 'Unknown',
                        patient.lastCheckup || 'N/A',
                        patient.title || 'None'
                    ].map(text => new TableCell({ 
                        children: [new Paragraph(text)] 
                    }))
                })
            );
        });

        children.push(
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                columnWidths: [2500, 1500, 800, 2000, 1500, 2500],
                rows: tableRows
            })
        );

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
        
        const fileName = `MCH_Records_${new Date().toISOString().split('T')[0]}.docx`;
        saveAs(blob, fileName);
        showMessage('✅ MCH records exported to DOCX successfully!', true);
        
    } catch (error) {
        console.error('Error generating DOCX:', error);
        showMessage(`❌ Error exporting DOCX: ${error.message}`, false);
    } finally {
        exportBtn.disabled = false;
        exportBtn.innerHTML = originalText;
    }
}

function showMessage(message, success = true) {
    const msgBox = document.getElementById('messageBox');
    msgBox.textContent = message;
    msgBox.className = success ? 'message-box message-success show' : 'message-box message-error show';
    setTimeout(() => {
        msgBox.classList.remove('show');
    }, 3000);
}

document.getElementById('searchInput').addEventListener('input', filterAndDisplayMCHPatients);
document.getElementById('sortSelect').addEventListener('change', function() {
    applySort();
    filterAndDisplayMCHPatients();
});

loadMCHRecords();
</script>
</body>
</html>