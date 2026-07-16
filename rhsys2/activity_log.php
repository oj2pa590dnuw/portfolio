<?php
require 'likod/session_utils.php';
enforce_role(['is_midwife', 'is_admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Log - RHSYS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="app-container">
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="large-container">
            <div class="nav-links">
                <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>

            <h1>Activity Log</h1>

            <!-- Controls Bar -->
            <div class="controls-bar">
                <div class="search-box">
                    <label for="actionFilter">Action Type:</label>
                    <select id="actionFilter" class="form-select">
                        <option value="">All Actions</option>
                        <option value="LOGIN">Login</option>
                        <option value="LOGOUT">Logout</option>
                        <option value="PATIENT_ADDED">Patient Added</option>
                        <option value="PATIENT_UPDATED">Patient Updated</option>
                        <option value="PATIENT_DELETED">Patient Deleted</option>
                        <option value="VISIT_ADD">Visit Added</option>
                        <option value="INVENTORY_ADDED">Inventory Added</option>
                        <option value="INVENTORY_DELETED">Inventory Deleted</option>
                        <option value="SCHEDULE_UPDATED">Schedule Updated</option>
                        <option value="SCHEDULE_DELETED">Schedule Deleted</option>
                        <option value="BACKUP_CREATE">Backup Created</option>
                        <option value="USER_APPROVED">User Approved</option>
                        <option value="USER_REVOKED">User Revoked</option>
                    </select>
                </div>
                
                <div class="search-box">
                    <label for="userFilter">User:</label>
                    <select id="userFilter" class="form-select">
                        <option value="">All Users</option>
                        <!-- Users will be populated dynamically -->
                    </select>
                </div>
                
                <div class="date-filter">
                    <label for="dateFilter">Date:</label>
                    <input type="date" id="dateFilter" class="form-input">
                </div>

                <div class="action-buttons">
                    <button onclick="refreshData()" class="btn btn-secondary">🔄 Refresh</button>
                    <button onclick="exportToCSV()" class="btn btn-primary">📄 Export CSV</button>
                </div>
            </div>

            <div class="status-summary">
                Showing: <span id="resultsInfo">Loading activity log...</span>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody id="activityTableBody">
                        <!-- Activity data will be loaded here -->
                    </tbody>
                </table>
            </div>

            <div id="loadingMessage" class="loading">
                <p>Loading activity log...</p>
            </div>

            <div id="noResultsMessage" class="empty-state" style="display: none;">
                <div class="empty-state-icon">📝</div>
                <h3>No activity found</h3>
                <p>No activity logs match your current filters.</p>
            </div>

            <!-- Pagination -->
            <div class="pagination" id="paginationContainer" style="display: none;">
                <!-- Pagination buttons will be added here -->
            </div>
        </div>
    </div>
</div>

<script>
let allLogs = [];
let filteredLogs = [];
let currentPage = 1;
const itemsPerPage = 25;
let uniqueUsers = new Set();

// Action type styling
const actionStyles = {
    'LOGIN': { class: 'status-stable', icon: '🔐' },
    'LOGOUT': { class: 'status-stable', icon: '🚪' },
    'PATIENT_ADDED': { class: 'status-pregnant', icon: '➕' },
    'PATIENT_UPDATED': { class: 'status-warning', icon: '✏️' },
    'PATIENT_DELETED': { class: 'status-critical', icon: '🗑️' },
    'VISIT_ADD': { class: 'status-meds', icon: '🏥' },
    'INVENTORY_ADDED': { class: 'status-stable', icon: '📦' },
    'INVENTORY_DELETED': { class: 'status-critical', icon: '📦' },
    'SCHEDULE_UPDATED': { class: 'status-warning', icon: '📅' },
    'SCHEDULE_DELETED': { class: 'status-critical', icon: '📅' },
    'BACKUP_CREATE': { class: 'status-senior', icon: '💾' },
    'USER_APPROVED': { class: 'status-stable', icon: '✅' },
    'USER_REVOKED': { class: 'status-critical', icon: '❌' },
    'FAMILY_RELATIONSHIP_ADDED': { class: 'status-pregnant', icon: '👨‍👩‍👧‍👦' },
    'FAMILY_RELATIONSHIP_REMOVED': { class: 'status-critical', icon: '👨‍👩‍👧‍👦' },
    'BATCH_ADDED': { class: 'status-stable', icon: '📦' },
    'BATCH_DELETED': { class: 'status-critical', icon: '📦' }
};

function loadActivityLog() {
    const loadingMessage = document.getElementById('loadingMessage');
    const tableBody = document.getElementById('activityTableBody');
    const resultsInfo = document.getElementById('resultsInfo');
    const noResultsMessage = document.getElementById('noResultsMessage');

    loadingMessage.style.display = 'block';
    tableBody.innerHTML = '';
    noResultsMessage.style.display = 'none';
    resultsInfo.textContent = 'Loading activity log...';

    const actionFilter = document.getElementById('actionFilter').value;
    const userFilter = document.getElementById('userFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;

    const params = new URLSearchParams({
        page: currentPage,
        limit: itemsPerPage,
        action: actionFilter,
        user_id: userFilter,
        date: dateFilter
    });

    fetch(`likod/get_activity_log.php?${params}`)
        .then(response => {
            if (!response.ok) throw new Error('Server error');
            return response.json();
        })
        .then(data => {
            loadingMessage.style.display = 'none';
            
            if (data.success && data.logs && data.logs.length > 0) {
                allLogs = data.logs;
                processActivityData(data);
            } else {
                throw new Error(data.error || 'No activity logs found');
            }
        })
        .catch(error => {
            loadingMessage.style.display = 'none';
            tableBody.innerHTML = '';
            noResultsMessage.style.display = 'block';
            resultsInfo.textContent = 'Error loading activity log';
            console.error('Error loading activity log:', error);
        });
}

function processActivityData(data) {
    const tableBody = document.getElementById('activityTableBody');
    const resultsInfo = document.getElementById('resultsInfo');
    const noResultsMessage = document.getElementById('noResultsMessage');
    const paginationContainer = document.getElementById('paginationContainer');

    // Update results info
    resultsInfo.textContent = `Showing ${data.logs.length} of ${data.totalRecords} activities`;

    // Build user filter options
    updateUserFilter(data.logs);

    // Display logs
    tableBody.innerHTML = '';
    data.logs.forEach(log => {
        const actionStyle = actionStyles[log.action] || { class: 'status-stable', icon: '📋' };
        const row = document.createElement('tr');
        
        row.innerHTML = `
            <td>
                <strong>${new Date(log.created_at).toLocaleDateString()}</strong><br>
                <small>${new Date(log.created_at).toLocaleTimeString()}</small>
            </td>
            <td>
                <strong>${log.full_name || 'Unknown User'}</strong><br>
                <small class="text-muted">ID: ${log.user_id}</small>
            </td>
            <td>
                <span class="status-badge ${actionStyle.class}">
                    ${actionStyle.icon} ${log.action}
                </span>
            </td>
            <td>${log.description || 'No description'}</td>
            <td>
                <code>${log.ip_address}</code><br>
                <small class="text-muted">${log.user_agent ? log.user_agent.substring(0, 50) + '...' : 'N/A'}</small>
            </td>
            <td>
                ${log.table_name ? `<small><strong>Table:</strong> ${log.table_name}</small><br>` : ''}
                ${log.record_id ? `<small><strong>Record ID:</strong> ${log.record_id}</small>` : ''}
            </td>
        `;
        
        tableBody.appendChild(row);
    });

    // Show/hide no results message
    if (data.logs.length === 0) {
        tableBody.style.display = 'none';
        noResultsMessage.style.display = 'block';
        paginationContainer.style.display = 'none';
    } else {
        tableBody.style.display = '';
        noResultsMessage.style.display = 'none';
        
        // Update pagination
        updatePagination(data.totalPages, data.currentPage);
    }
}

function updateUserFilter(logs) {
    const userFilter = document.getElementById('userFilter');
    const currentUsers = new Set();
    
    // Collect unique users from current logs
    logs.forEach(log => {
        if (log.full_name && log.user_id) {
            const userKey = `${log.user_id}-${log.full_name}`;
            currentUsers.add(userKey);
            uniqueUsers.add(userKey);
        }
    });

    // Only update user filter if we have new users
    if (currentUsers.size > 0) {
        // Keep existing "All Users" option
        const allUsersOption = userFilter.querySelector('option[value=""]');
        userFilter.innerHTML = '';
        userFilter.appendChild(allUsersOption);
        
        // Add current users
        uniqueUsers.forEach(userKey => {
            const [userId, userName] = userKey.split('-');
            const option = document.createElement('option');
            option.value = userId;
            option.textContent = userName;
            userFilter.appendChild(option);
        });
    }
}

function updatePagination(totalPages, currentPage) {
    const paginationContainer = document.getElementById('paginationContainer');
    
    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }

    paginationContainer.style.display = 'flex';
    let html = '';

    // Previous button
    if (currentPage > 1) {
        html += `<button onclick="goToPage(${currentPage - 1})" class="pagination-btn">← Previous</button>`;
    }

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            html += `<button class="pagination-btn active">${i}</button>`;
        } else {
            html += `<button onclick="goToPage(${i})" class="pagination-btn">${i}</button>`;
        }
    }

    // Next button
    if (currentPage < totalPages) {
        html += `<button onclick="goToPage(${currentPage + 1})" class="pagination-btn">Next →</button>`;
    }

    paginationContainer.innerHTML = html;
}

function goToPage(page) {
    currentPage = page;
    loadActivityLog();
    // Scroll to top of table
    document.querySelector('.table-container').scrollIntoView({ behavior: 'smooth' });
}

function refreshData() {
    currentPage = 1;
    loadActivityLog();
}

function exportToCSV() {
    const actionFilter = document.getElementById('actionFilter').value;
    const userFilter = document.getElementById('userFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    
    const params = new URLSearchParams({
        export: 'csv',
        action: actionFilter,
        user_id: userFilter,
        date: dateFilter
    });
    
    window.open(`likod/get_activity_log.php?${params}`, '_blank');
}

// Event listeners for filters
document.getElementById('actionFilter').addEventListener('change', function() {
    currentPage = 1;
    loadActivityLog();
});

document.getElementById('userFilter').addEventListener('change', function() {
    currentPage = 1;
    loadActivityLog();
});

document.getElementById('dateFilter').addEventListener('change', function() {
    currentPage = 1;
    loadActivityLog();
});

// Auto-refresh when coming online
window.addEventListener('online', loadActivityLog);

// Initial load
document.addEventListener('DOMContentLoaded', loadActivityLog);
</script>
</body>
</html>