<?php
require 'likod/auth_guard.php';
require 'likod/session_utils.php';
enforce_role(['is_bhw', 'is_midwife', 'is_admin']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Center Inventory</title>
    <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="app-container">
        <?php include 'navbar.php'; ?>

        <div class="main-content">
            <div class="large-container">
                <h1>Health Center Inventory</h1>

                <div class="action-buttons">
                    <button id="openAddItemModal" class="btn btn-primary">
                        + Add New Item
                    </button>
                    <button id="exportInventoryBtn" class="btn export-btn">
                        📄 Export Inventory to DOCX
                    </button>
                </div>

                <div class="controls-bar">
                    <div class="search-box">
                        <label for="searchInput">Search</label>
                        <input type="text" id="searchInput"
                            placeholder="Search items by name, category, or description..." class="form-input">
                    </div>
                    <div class="sort-filter">
                        <label for="categoryFilter">Category</label>
                        <select id="categoryFilter" class="form-select">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    <div class="sort-filter">
                        <label for="stockFilter">Stock Status</label>
                        <select id="stockFilter" class="form-select">
                            <option value="">All Stock Status</option>
                            <option value="critical">Critical Stock</option>
                            <option value="low">Low Stock</option>
                            <option value="stable">In Stock</option>
                            <option value="out">Out of Stock</option>
                        </select>
                    </div>
                    <div class="action-buttons">
                        <button id="clearSearch" class="btn btn-secondary">Clear Filters</button>
                    </div>
                </div>

                <div class="status-summary" id="searchStats">
                    Showing all items
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Reorder Point</th>
                                <th>Status</th>
                                <th>Latest Expiry</th>
                            </tr>
                        </thead>
                        <tbody id="inventoryTableBody">
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #555;">Loading
                                    inventory...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="itemModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('itemModal')">&times;</span>
            <h2 id="modalTitle">Add New Item</h2>
            <form id="itemForm" class="form-container">
                <input type="hidden" id="item_id" name="item_id">

                <div class="form-grid">
                    <div class="form-item">
                        <label for="item_name" class="form-label">Item Name</label>
                        <input type="text" id="item_name" name="item_name" class="form-input" required>
                    </div>

                    <div class="form-item">
                        <label for="item_category" class="form-label">Category</label>
                        <select id="item_category" name="item_category" class="form-select" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>

                    <div class="form-item">
                        <label for="dosage_form" class="form-label">Dosage Form</label>
                        <select id="dosage_form" name="dosage_form" class="form-select" required>
                            <option value="">Select Dosage Form</option>
                        </select>
                    </div>

                    <div class="form-item">
                        <label for="unit_of_issue" class="form-label">Unit of Issue</label>
                        <input type="text" id="unit_of_issue" name="unit_of_issue" class="form-input" required
                            placeholder="e.g., pc, box, bottle">
                    </div>

                    <div class="form-item">
                        <label for="reorder_point" class="form-label">Reorder Point</label>
                        <input type="number" id="reorder_point" name="reorder_point" class="form-input" required min="0"
                            value="20">
                    </div>

                    <div class="form-item">
                        <label for="description" class="form-label">Description / Notes</label>
                        <textarea id="description" name="description" class="form-textarea" rows="2"
                            placeholder="Optional description"></textarea>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="button" id="deleteItemBtn" class="btn btn-danger hidden">Delete Item</button>
                    <button type="submit" id="saveItemBtn" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>

    <div id="restockModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('restockModal')">&times;</span>
            <h2 id="restockModalTitle">Restock Item</h2>
            <form id="restockForm" class="form-container">
                <input type="hidden" id="restock_item_id" name="item_id">

                <div class="form-grid">
                    <div class="form-item">
                        <label for="batch_number" class="form-label">Batch / Lot Number</label>
                        <input type="text" id="batch_number" name="batch_number" class="form-input" required
                            placeholder="e.g., BATCH-001">
                    </div>

                    <div class="form-item">
                        <label for="quantity_in_batch" class="form-label">Quantity to Add</label>
                        <input type="number" id="quantity_in_batch" name="quantity_in_batch" class="form-input" required
                            min="1" value="1">
                    </div>

                    <div class="form-item">
                        <label for="expiration_date" class="form-label">Expiration Date</label>
                        <input type="date" id="expiration_date" name="expiration_date" class="form-input" required>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">Add Batch to Stock</button>
                </div>
            </form>
        </div>
    </div>

    <div id="batchEditModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('batchEditModal')">&times;</span>
            <h2>Edit Batch</h2>
            <form id="batchEditForm" class="form-container">
                <input type="hidden" id="edit_batch_id" name="batch_id">
                <input type="hidden" id="edit_item_id" name="item_id">

                <div class="form-grid">
                    <div class="form-item">
                        <label for="edit_batch_number" class="form-label">Batch / Lot Number</label>
                        <input type="text" id="edit_batch_number" name="batch_number" class="form-input" required>
                    </div>

                    <div class="form-item">
                        <label for="edit_current_stock" class="form-label">Current Stock</label>
                        <input type="number" id="edit_current_stock" name="current_stock" class="form-input" required
                            min="0">
                        <div id="maxStockHint" class="form-hint" style="font-size: 0.8em;">(Max: <span
                                id="maxStockValue"></span>)</div>
                    </div>

                    <div class="form-item">
                        <label for="edit_expiration_date" class="form-label">Expiration Date</label>
                        <input type="date" id="edit_expiration_date" name="expiration_date" class="form-input" required>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">Update Batch</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/docx/7.8.2/docx.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script>
        // Global variables
        let allInventoryItems = [];
        let currentCategories = [];
        let expandedRowId = null;
        let itemBatches = {};
        let maxBatchStock = 0;

        const API_PATH = 'likod/';

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function formatDate(dateString) {
            if (dateString === 'N/A' || !dateString) return 'N/A';
            try {
                const date = new Date(dateString);
                date.setDate(date.getDate() + 1);
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch {
                return dateString;
            }
        }

        // NEW FUNCTION: Loads options for select elements in the item form from an API endpoint.
        async function loadFormOptions(selectId, apiEndpoint, defaultText) {
            const selectElement = document.getElementById(selectId);
            if (!selectElement) return;

            selectElement.innerHTML = `<option value="">${defaultText}</option>`; // Reset options

            try {
                // The API_PATH is 'likod/', so we construct the full path.
                const response = await fetch(`${API_PATH}${apiEndpoint}`);
                const result = await response.json();

                // Check for success and that the response is an array named 'data' (common API pattern)
                if (result.success && Array.isArray(result.data)) {
                    result.data.forEach(optionValue => {
                        const option = document.createElement('option');
                        option.value = optionValue;
                        option.textContent = optionValue;
                        selectElement.appendChild(option);
                    });
                } else {
                    console.error(`Error loading options for ${selectId}:`, result.message || 'Invalid response format');
                    // Add a friendly error option if API fails or returns unexpected data
                    const errorOption = document.createElement('option');
                    errorOption.value = '';
                    errorOption.textContent = '❌ Failed to load options';
                    selectElement.appendChild(errorOption);
                }
            } catch (error) {
                console.error(`Network error loading options for ${selectId}:`, error);
                // Add a friendly error option if network fails
                const errorOption = document.createElement('option');
                errorOption.value = '';
                errorOption.textContent = '❌ Network Error';
                selectElement.appendChild(errorOption);
            }
        }

        function getStockStatus(currentStock, reorderPoint) {
            // ... existing code ...
            const stock = parseInt(currentStock) || 0;
            const reorder = parseInt(reorderPoint) || 0;

            if (stock <= 0) {
                return {
                    text: 'OUT OF STOCK',
                    statusClass: 'status-critical'
                };
            } else if (stock <= reorder) {
                return {
                    text: 'LOW STOCK',
                    statusClass: 'status-warning'
                };
            } else {
                return {
                    text: 'IN STOCK',
                    statusClass: 'status-stable'
                };
            }
        }

        function updateSearchStats(displayedCount) {
            // ... existing code ...
            const totalCount = allInventoryItems.length;
            const searchStats = document.getElementById('searchStats');

            if (displayedCount === totalCount) {
                searchStats.textContent = `Showing all ${totalCount} items`;
            } else {
                searchStats.textContent = `Showing ${displayedCount} of ${totalCount} items`;
            }
        }

        function loadCategoryFilters(categories) {
            const categoryFilter = document.getElementById('categoryFilter');
            currentCategories = categories;
            categoryFilter.innerHTML = '<option value="">All Categories</option>';

            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                categoryFilter.appendChild(option);
            });
        }

        function filterInventory() {
            // ... existing code ...
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const categoryFilter = document.getElementById('categoryFilter').value;
            const stockFilter = document.getElementById('stockFilter').value;

            const filteredItems = allInventoryItems.filter(item => {
                const matchesSearch = !searchTerm ||
                    item.item_name.toLowerCase().includes(searchTerm) ||
                    item.item_category.toLowerCase().includes(searchTerm) ||
                    (item.description && item.description.toLowerCase().includes(searchTerm)) ||
                    item.dosage_form.toLowerCase().includes(searchTerm);

                if (!matchesSearch) return false;

                const matchesCategory = !categoryFilter || item.item_category === categoryFilter;
                if (!matchesCategory) return false;

                let matchesStock = true;
                if (stockFilter) {
                    const {
                        text: statusText
                    } = getStockStatus(item.total_stock, item.reorder_point);
                    let requiredStatusText = '';

                    if (stockFilter === 'out' || stockFilter === 'critical') {
                        requiredStatusText = 'OUT OF STOCK';
                    } else if (stockFilter === 'low') {
                        requiredStatusText = 'LOW STOCK';
                    } else if (stockFilter === 'stable') {
                        requiredStatusText = 'IN STOCK';
                    }

                    matchesStock = statusText === requiredStatusText;
                }

                return matchesStock;
            });

            displayInventory(filteredItems);
            updateSearchStats(filteredItems.length);
        }

        function displayInventory(items) {
            // ... existing code ...
            const tableBody = document.getElementById('inventoryTableBody');
            tableBody.innerHTML = '';

            if (items.length === 0) {
                tableBody.innerHTML =
                    '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #555;">No items match your search criteria.</td></tr>';
                return;
            }

            items.forEach(item => {
                const {
                    text: statusText,
                    statusClass
                } = getStockStatus(item.total_stock, item.reorder_point);
                const expiryText = formatDate(item.latest_expiry_date);

                const isExpanded = expandedRowId == item.item_id;

                const row = `
            <tr class="inventory-row ${isExpanded ? 'expanded' : ''}" data-id="${item.item_id}">
               <td onclick="toggleRowExpansion(${item.item_id}, event)" style="cursor: pointer;">
                  <span class="expand-icon">${isExpanded ? '▼' : '▶'}</span>
               </td>
               <td onclick="setupEditModal(${item.item_id}, event)" style="cursor: pointer;">
                  <strong>${item.item_name}</strong>
                  <div style="font-size: 0.8em; color: #666;">${item.dosage_form}</div>
               </td>
               <td>${item.item_category}</td>
               <td>
                  <span class="stock-number">${item.total_stock}</span>
                  <div style="font-size: 0.8em; color: #666;">${item.unit_of_issue}</div>
               </td>
               <td>${item.reorder_point}</td>
               <td>
                  <span class="status-badge ${statusClass}">${statusText}</span>
               </td>
               <td>${expiryText}</td>
            </tr>
            ${isExpanded ? createExpandedRow(item) : ''}
        `;
                tableBody.innerHTML += row;
            });
        }

        function createExpandedRow(item) {
            const batches = itemBatches[item.item_id] || [];
            const batchesHtml = batches.length > 0 ? createBatchesTable(batches, item.item_id) :
                `<div class="batch-list-container" style="text-align: center; padding: 15px; background-color: #ffffff; border-radius: 8px;">
            <p style="color: #6c757d; margin-bottom: 15px;">No batches found for this item. Let's add some!</p>
            <button class="btn btn-success" onclick="setupRestockModal(${item.item_id}, '${item.item_name}')">➕ Restock Now</button>
        </div>`;

            return `
        <tr class="expanded-details">
           <td colspan="7" style="padding: 0;">
              <div class="expanded-content" style="
                  padding: 25px; 
                  background-color: #f7f9fa; 
                  border-top: 3px solid #007bff; 
                  border-radius: 0 0 8px 8px;
                  box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.05);
              ">
                
                 <h3 style="color: #007bff; margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #e0e0e0; padding-bottom: 10px;">
                     Item Details: ${item.item_name}
                 </h3>

                 <div class="item-details-grid" style="
                     display: grid; 
                     grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
                     gap: 15px; 
                     margin-bottom: 25px;
                     padding: 15px;
                     border: 1px solid #e9ecef;
                     border-radius: 8px;
                     background-color: #ffffff;
                 ">
                    <div class="detail-item full-width" style="grid-column: 1 / -1;">
                        <span class="detail-label" style="font-weight: bold; color: #343a40;">Description:</span>
                        <div class="detail-value" style="color: #6c757d; margin-top: 5px; padding-left: 10px; border-left: 3px solid #dee2e6;">${item.description || 'No description available'}</div>
                    </div>
                    <div class="detail-item">
                       <span class="detail-label" style="font-weight: bold; color: #343a40;">Dosage Form:</span>
                       <span class="detail-value" style="color: #495057;">${item.dosage_form}</span>
                    </div>
                    <div class="detail-item">
                       <span class="detail-label" style="font-weight: bold; color: #343a40;">Unit of Issue:</span>
                       <span class="detail-value" style="color: #495057;">${item.unit_of_issue}</span>
                    </div>
                    <div class="detail-item">
                       <span class="detail-label" style="font-weight: bold; color: #343a40;">Reorder Point:</span>
                       <span class="detail-value" style="color: #dc3545; font-weight: bold;">${item.reorder_point}</span>
                    </div>
                 </div>
                 
                 <div class="action-buttons" style="margin-bottom: 25px; display: flex; gap: 10px;">
                    <button class="btn btn-primary" onclick="setupEditModal(${item.item_id})" style="flex-grow: 1;">📝 Edit Item Details</button>
                    <button class="btn btn-success" onclick="setupRestockModal(${item.item_id}, '${item.item_name}')" style="flex-grow: 1;">📦 Add New Batch</button>
                 </div>

                 <div class="batch-section">
                    <h4 style="color: #28a745; border-bottom: 1px solid #28a745; padding-bottom: 5px; margin-bottom: 15px;">Batch Management (${batches.length} Batches)</h4>
                    ${batchesHtml}
                 </div>
              </div>
           </td>
        </tr>
     `;
        }

        function createBatchesTable(batches, itemId) {
            let html = `
        <table class="data-table batch-data-table" style="width: 100%; border: 1px solid #ced4da; border-radius: 8px; overflow: hidden; margin-bottom: 0;">
           <thead style="background-color: #e9ecef;">
              <tr>
                 <th>Batch No.</th>
                 <th>Current Stock</th>
                 <th>Original Qty</th>
                 <th>Expiry Date</th>
                 <th>Restocked By</th>
                 <th>Actions</th>
              </tr>
           </thead>
           <tbody>
     `;

            batches.forEach(batch => {
                const expiryDate = new Date(batch.expiration_date);
                const isExpired = expiryDate < new Date();
                const rowClass = isExpired ? 'expired-batch' : '';
                const expiryStyle = isExpired ? 'color: #dc3545; font-weight: bold;' : 'color: #495057;';

                html += `
           <tr class="${rowClass}" data-batch-id="${batch.batch_id}" style="background-color: ${isExpired ? '#f8d7da' : '#ffffff'};">
              <td style="font-weight: 500;">${batch.batch_number}</td>
              <td>${batch.current_stock}</td>
              <td>${batch.quantity_in_batch}</td>
              <td style="${expiryStyle}">${formatDate(batch.expiration_date)} ${isExpired ? ' (EXPIRED)' : ''}</td>
              <td>${batch.restocked_by_name || 'N/A'}</td>
              <td>
                 <div class="action-buttons" style="display: flex; gap: 5px;">
                    <button class="btn btn-primary btn-sm" onclick="setupEditBatchModal(${batch.batch_id}, ${itemId})">✏️ Edit</button> 
                    <button class="btn btn-danger btn-sm" onclick="deleteBatch(${batch.batch_id}, '${batch.batch_number}', ${itemId})">🗑️ Delete</button>
                 </div>
              </td>
           </tr>
        `;
            });

            html += `
           </tbody>
        </table>
     `;
            return html;
        }

        async function toggleRowExpansion(itemId, event) {
            // ... existing code ...
            if (event) event.stopPropagation();

            itemId = String(itemId);

            if (expandedRowId === itemId) {
                expandedRowId = null;
            } else {
                expandedRowId = itemId;
                await loadBatches(itemId);
            }
            filterInventory();
        }

        async function loadBatches(itemId) {
            // ... existing code ...
            try {
                const response = await fetch(`${API_PATH}get_batches_by_item.php?item_id=${itemId}`);
                const result = await response.json();

                if (result.success && result.batches) {
                    itemBatches[itemId] = result.batches;
                } else {
                    itemBatches[itemId] = [];
                }
            } catch (error) {
                console.error('Error loading batches:', error);
                itemBatches[itemId] = [];
            }
        }

        async function loadInventory() {
            try {
                const response = await fetch(`${API_PATH}get_inventory.php`);
                const items = await response.json();

                allInventoryItems = items.map(item => ({
                    ...item,
                    total_stock: parseInt(item.total_stock),
                    reorder_point: parseInt(item.reorder_point)
                }));

                if (allInventoryItems.length === 0) {
                    document.getElementById('inventoryTableBody').innerHTML =
                        '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #555;">No inventory items found.</td></tr>';
                    updateSearchStats(0);
                    return;
                }

                const categories = [...new Set(allInventoryItems.map(item => item.item_category))].filter(Boolean);
                loadCategoryFilters(categories); // Loads the category filter dropdown

                filterInventory();

            } catch (error) {
                console.error('Failed to load inventory:', error);
                document.getElementById('inventoryTableBody').innerHTML =
                    '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #555;">Error loading inventory.</td></tr>';
            }
        }

        function setupNewItemModal() {
            // ... existing code ...
            document.getElementById('modalTitle').textContent = 'Add New Item';
            document.getElementById('itemForm').reset();
            document.getElementById('item_id').value = '';
            document.getElementById('saveItemBtn').textContent = 'Save Item';
            document.getElementById('deleteItemBtn').classList.add('hidden');
            document.getElementById('itemModal').style.display = 'flex';
        }

        async function setupEditModal(itemId, event) {
            // ... existing code ...
            if (event) event.stopPropagation();

            let itemData = allInventoryItems.find(i => i.item_id == itemId);

            if (!itemData) {
                alert('Item data not found. Try reloading the inventory.');
                return;
            }

            try {
                const response = await fetch(`${API_PATH}get_single_item.php?item_id=${itemId}`);
                const result = await response.json();

                if (result.success && result.data) {
                    itemData = result.data;
                }

                document.getElementById('modalTitle').textContent = `Edit Item: ${itemData.item_name}`;
                document.getElementById('item_id').value = itemData.item_id;
                document.getElementById('item_name').value = itemData.item_name;
                // The values here will now correctly select the dynamically loaded options
                document.getElementById('item_category').value = itemData.item_category;
                document.getElementById('dosage_form').value = itemData.dosage_form;
                document.getElementById('unit_of_issue').value = itemData.unit_of_issue;
                document.getElementById('reorder_point').value = itemData.reorder_point;
                document.getElementById('description').value = itemData.description || '';

                document.getElementById('saveItemBtn').textContent = 'Update Item';
                document.getElementById('deleteItemBtn').classList.remove('hidden');
                document.getElementById('deleteItemBtn').onclick = () => deleteItem(itemData.item_id, itemData.item_name);
                document.getElementById('itemModal').style.display = 'flex';

            } catch (error) {
                console.error('Error loading item data:', error);
                document.getElementById('modalTitle').textContent = `Edit Item: ${itemData.item_name}`;
                document.getElementById('item_id').value = itemData.item_id;
                document.getElementById('item_name').value = itemData.item_name;
                // The values here will now correctly select the dynamically loaded options
                document.getElementById('item_category').value = itemData.item_category;
                document.getElementById('dosage_form').value = itemData.dosage_form;
                document.getElementById('unit_of_issue').value = itemData.unit_of_issue;
                document.getElementById('reorder_point').value = itemData.reorder_point;
                document.getElementById('description').value = itemData.description || '';

                document.getElementById('saveItemBtn').textContent = 'Update Item';
                document.getElementById('deleteItemBtn').classList.remove('hidden');
                document.getElementById('deleteItemBtn').onclick = () => deleteItem(itemData.item_id, itemData.item_name);
                document.getElementById('itemModal').style.display = 'flex';
            }
        }

        function setupRestockModal(itemId, itemName) {
            // ... existing code ...
            document.getElementById('restockModalTitle').textContent = `Restock ${itemName}`;
            document.getElementById('restock_item_id').value = itemId;
            document.getElementById('restockForm').reset();

            // Set expiration date to 1 year from now as default
            const nextYear = new Date();
            nextYear.setFullYear(nextYear.getFullYear() + 1);
            document.getElementById('expiration_date').value = nextYear.toISOString().split('T')[0];

            document.getElementById('restockModal').style.display = 'flex';
        }

        function setupEditBatchModal(batchId, itemId) {
            // ... existing code ...
            const batch = itemBatches[itemId].find(b => b.batch_id == batchId);
            if (!batch) {
                alert('Batch data not found.');
                return;
            }

            maxBatchStock = parseInt(batch.quantity_in_batch);

            document.getElementById('edit_batch_id').value = batch.batch_id;
            document.getElementById('edit_item_id').value = itemId;
            document.getElementById('edit_batch_number').value = batch.batch_number;
            document.getElementById('edit_current_stock').value = batch.current_stock;
            document.getElementById('edit_expiration_date').value = batch.expiration_date;
            document.getElementById('maxStockValue').textContent = maxBatchStock;
            document.getElementById('edit_current_stock').setAttribute('max', maxBatchStock);

            document.getElementById('batchEditModal').style.display = 'flex';
        }

        async function updateBatch(data) {
            // ... existing code ...
            try {
                const response = await fetch(`${API_PATH}update_batch.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    closeModal('batchEditModal');
                    await loadBatches(data.item_id);
                    loadInventory();
                } else {
                    alert(`Error updating batch: ${result.message}`);
                }
            } catch (error) {
                console.error('Network error during batch update:', error);
                alert('Network error. Failed to update batch.');
            }
        }

        async function deleteItem(itemId, itemName) {
            // ... existing code ...
            if (!confirm(
                `Are you sure you want to permanently delete "${itemName}"? This will remove the item AND ALL its batches from the system!`
            )) {
                return;
            }
            try {
                const response = await fetch(`${API_PATH}delete_inventory.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        item_id: itemId
                    })
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    closeModal('itemModal');
                    loadInventory();
                } else {
                    alert(`Error deleting item: ${result.message}`);
                }
            } catch (error) {
                console.error('Network error during item deletion:', error);
                alert('Network error. Failed to delete item.');
            }
        }

        async function deleteBatch(batchId, batchNumber, itemId) {
            // ... existing code ...
            if (!confirm(`Delete Batch ${batchNumber}? This will permanently remove the stock and the batch record.`)) {
                return;
            }

            try {
                const response = await fetch(`${API_PATH}delete_batch.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        batch_id: batchId
                    })
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    await loadBatches(itemId);
                    loadInventory();
                } else {
                    alert(`Error deleting batch: ${result.message}`);
                }
            } catch (error) {
                console.error('Network error during batch deletion:', error);
                alert('Network error. Failed to delete batch.');
            }
        }

        async function exportInventoryToDocx() {
            // ... existing code ...
            try {
                const exportInventoryBtn = document.getElementById('exportInventoryBtn');
                exportInventoryBtn.disabled = true;
                exportInventoryBtn.textContent = '⏳ Generating...';

                const date = new Date().toISOString().split('T')[0];
                const fileName = `Inventory_Report_${date}.doc`;

                const itemsToExport = allInventoryItems;

                let htmlContent = `
            <html>
            <head>
// ... existing code ...
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                    h1, h2 { color: #2c3e50; text-align: center; }
                    h3 { color: #2c3e50; margin-top: 30px; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th { color: #27ae60; background-color: white; padding: 12px 8px; text-align: left; border-bottom: 2px solid #27ae60; font-weight: bold; }
                    td { padding: 10px 8px; border-bottom: 1px solid #ecf0f1; }
                    .section { margin-bottom: 40px; }
                    .summary { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                    .status-critical { color: #e74c3c; font-weight: bold; }
                    .status-warning { color: #f39c12; font-weight: bold; }
                    .status-stable { color: #27ae60; font-weight: bold; }
                </style>
            </head>
            <body>
                <h1>Barangay Zabali Health Center</h1>
                <h2>Inventory Report</h2>
                
                <div class="summary">
                    <p><strong>Report Date:</strong> ${new Date().toLocaleDateString('en-US', {
                    year: 'numeric', month: 'long', day: 'numeric'
                })}</p>
                    <p><strong>Total Items:</strong> ${itemsToExport.length}</p>
                    <p><strong>Report Generated:</strong> ${new Date().toLocaleDateString('en-US', {
                    year: 'numeric', month: 'long', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                })}</p>
                </div>
        `;

                if (itemsToExport.length > 0) {
                    const categorySummary = {};
                    let criticalCount = 0;
                    let lowCount = 0;
                    let outOfStockCount = 0;
                    let stableCount = 0;

                    itemsToExport.forEach(item => {
                        if (!categorySummary[item.item_category]) {
                            categorySummary[item.item_category] = 0;
                        }
                        categorySummary[item.item_category]++;

                        const {
                            text: statusText
                        } = getStockStatus(item.total_stock, item.reorder_point);
                        if (statusText === 'OUT OF STOCK') outOfStockCount++;
                        else if (statusText === 'LOW STOCK') lowCount++;
                        else if (statusText === 'IN STOCK') stableCount++;
                    });

                    htmlContent += `
                <div class="section">
                    <h3>📊 Inventory Summary</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Item Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${Object.entries(categorySummary).map(([category, count]) => `
                                <tr>
                                    <td>${category}</td>
                                    <td>${count}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 20px;">
                        <p><strong>Stock Status Summary:</strong></p>
                        <ul>
                            <li>Out of Stock: ${outOfStockCount} items</li>
                            <li>Low Stock: ${lowCount} items</li>
                            <li>In Stock: ${stableCount} items</li>
                        </ul>
                    </div>
                </div>
            `;

                    htmlContent += `
                <div class="section">
                    <h3>📋 Detailed Inventory (${itemsToExport.length} items)</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Dosage Form</th>
                                <th>Current Stock</th>
                                <th>Unit</th>
                                <th>Reorder Point</th>
                                <th>Status</th>
                                <th>Latest Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsToExport.map(item => {
                        const { text: statusText } = getStockStatus(item.total_stock, item.reorder_point);
                        const expiryText = formatDate(item.latest_expiry_date);
                        let statusClass = '';
                        if (statusText === 'OUT OF STOCK') statusClass = 'status-critical';
                        else if (statusText === 'LOW STOCK') statusClass = 'status-warning';
                        else statusClass = 'status-stable';

                        return ` <
               tr >
               <
               td > < strong > $ {
                  item.item_name
               } < /strong></td >
               <
               td > $ {
                  item.item_category
               } < /td> <
            td > $ {
               item.dosage_form
            } < /td> <
            td > $ {
               item.total_stock
            } < /td> <
            td > $ {
               item.unit_of_issue
            } < /td> <
            td > $ {
               item.reorder_point
            } < /td> <
            td class = "${statusClass}" > $ {
               statusText
            } < /td> <
            td > $ {
               expiryText
            } < /td> < /
            tr >
               `;
                    }).join('')}
                        </tbody>
                    </table>
                </div>
            `;
                } else {
                    htmlContent += `
                <div class="section">
                    <p style="text-align: center; font-style: italic; margin: 40px 0;">No inventory items found.</p>
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

                alert('✅ Inventory DOC file generated successfully!');

            } catch (error) {
                console.error('Error exporting inventory document:', error);
                alert('❌ Error generating inventory document. Please try again.');
            } finally {
                const exportInventoryBtn = document.getElementById('exportInventoryBtn');
                exportInventoryBtn.disabled = false;
                exportInventoryBtn.textContent = '📄 Export Inventory to DOCX';
            }
        }

        // REMOVED the dynamic form options loading since it was causing issues
        // Using hardcoded options that match your database

        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.getElementById('inventoryTableBody');
            const itemModal = document.getElementById('itemModal');
            const restockModal = document.getElementById('restockModal');
            const batchEditModal = document.getElementById('batchEditModal');

            // NEW: Load dynamic options for the Add/Edit modal (Category and Dosage Form)
            loadFormOptions('item_category', 'get_categories.php', 'Select Category');
            loadFormOptions('dosage_form', 'get_dosage_forms.php', 'Select Dosage Form');

            window.addEventListener('click', (event) => {
                if (event.target === itemModal) itemModal.style.display = 'none';
                if (event.target === restockModal) restockModal.style.display = 'none';
                if (event.target === batchEditModal) batchEditModal.style.display = 'none';
            });

            document.getElementById('searchInput').addEventListener('input', filterInventory);
            document.getElementById('categoryFilter').addEventListener('change', filterInventory);
            document.getElementById('stockFilter').addEventListener('change', filterInventory);
            document.getElementById('clearSearch').addEventListener('click', () => {
                document.getElementById('searchInput').value = '';
                document.getElementById('categoryFilter').value = '';
                document.getElementById('stockFilter').value = '';
                filterInventory();
            });

            document.getElementById('openAddItemModal').addEventListener('click', setupNewItemModal);
            document.getElementById('exportInventoryBtn').addEventListener('click', exportInventoryToDocx);

            document.getElementById('itemForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const itemId = document.getElementById('item_id').value;
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);

                console.log('Submitting item data:', data); // Debug log

                try {
                    const apiFile = itemId ? 'update_inventory.php' : 'add_inventory.php';
                    const response = await fetch(`${API_PATH}${apiFile}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();

                    console.log('Server response:', result); // Debug log

                    if (result.success) {
                        alert(result.message);
                        closeModal('itemModal');
                        loadInventory();
                    } else {
                        alert(`Failed to save item: ${result.message}`);
                    }
                } catch (error) {
                    console.error('Network error during item save:', error);
                    alert('Network error. Failed to save item.');
                }
            });

            document.getElementById('restockForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);

                console.log('Submitting batch data:', data); // Debug log

                try {
                    const response = await fetch(`${API_PATH}add_batch.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();

                    console.log('Server response:', result); // Debug log

                    if (result.success) {
                        alert('Restock successful!');
                        closeModal('restockModal');
                        expandedRowId = data.item_id;
                        await loadBatches(data.item_id);
                        loadInventory();
                    } else {
                        alert(`Restock Failed: ${result.message}`);
                    }
                } catch (error) {
                    console.error('Network error during restock:', error);
                    alert('Network error. Failed to add batch.');
                }
            });

            document.getElementById('batchEditForm').addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const data = Object.fromEntries(formData);

                const currentStock = parseInt(data.current_stock);
                if (currentStock < 0 || currentStock > maxBatchStock) {
                    alert(`Invalid stock value. Must be between 0 and ${maxBatchStock}.`);
                    return;
                }

                updateBatch(data);
            });

            loadInventory();
        });
    </script>
</body>

</html>