// SIMPLIFIED offline-helper.js
class OfflineHelper {
    constructor() {
        console.log('Offline Helper loaded');
        this.setupOnlineListener();
    }

    // Check if online
    isOnline() {
        return navigator.onLine;
    }

    // Save data for offline sync
    async saveForSync(dataType, data, endpoint) {
        const key = `${dataType}_${Date.now()}`;
        const offlineData = {
            data: data,
            endpoint: endpoint,
            timestamp: new Date().toISOString(),
            dataType: dataType
        };
        
        localStorage.setItem(key, JSON.stringify(offlineData));
        this.showMessage(`✅ Saved locally - will sync when online`, true);
        return key;
    }

    // Get all offline data of a specific type
    getOfflineData(dataType = null) {
        const results = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && key.includes('_') && !key.startsWith('cached_')) {
                try {
                    const item = JSON.parse(localStorage.getItem(key));
                    if (!dataType || item.dataType === dataType) {
                        results.push({key: key, data: item});
                    }
                } catch (e) {
                    console.error('Error parsing offline data:', e);
                }
            }
        }
        return results;
    }

    // Simple sync - just try to send all offline data
    async syncAll() {
        if (!this.isOnline()) {
            throw new Error('Cannot sync - you are offline');
        }

        const offlineItems = this.getOfflineData();
        const keysToRemove = [];
        let successCount = 0;

        for (const item of offlineItems) {
            try {
                const response = await fetch(item.data.endpoint, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(item.data.data)
                });

                if (response.ok) {
                    keysToRemove.push(item.key);
                    successCount++;
                    console.log(`Synced: ${item.key}`);
                } else {
                    console.error(`Server error for ${item.key}:`, response.status);
                }
            } catch (error) {
                console.error(`Failed to sync ${item.key}:`, error);
            }
        }

        // Remove synced items
        keysToRemove.forEach(key => localStorage.removeItem(key));
        
        if (successCount > 0) {
            this.showMessage(`✅ Synced ${successCount} items successfully`, true);
        } else if (offlineItems.length > 0) {
            throw new Error('Failed to sync any items - server may be down');
        } else {
            this.showMessage('✅ No pending items to sync', true);
        }
        
        return successCount;
    }

    // Setup online/offline listeners
    setupOnlineListener() {
        window.addEventListener('online', () => {
            console.log('Online - ready to sync');
            this.showMessage('🌐 Back online - ready to sync', true);
            
            // Auto-sync when coming online
            setTimeout(() => {
                if (this.getOfflineData().length > 0) {
                    this.syncAll().catch(error => {
                        console.log('Auto-sync failed:', error);
                    });
                }
            }, 2000);
        });

        window.addEventListener('offline', () => {
            console.log('Offline - using local storage');
            this.showMessage('📱 Offline mode - using local data', false);
        });
    }

    // Show status message
    showMessage(message, success = true) {
        // Remove existing message
        const existingMsg = document.getElementById('offlineStatusMessage');
        if (existingMsg) {
            existingMsg.remove();
        }

        const msg = document.createElement('div');
        msg.id = 'offlineStatusMessage';
        msg.textContent = message;
        msg.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 15px;
            border-radius: 5px;
            z-index: 10000;
            font-weight: bold;
            background: ${success ? '#d4edda' : '#f8d7da'};
            color: ${success ? '#155724' : '#721c24'};
            border: 1px solid ${success ? '#c3e6cb' : '#f5c6cb'};
            max-width: 300px;
        `;

        document.body.appendChild(msg);
        setTimeout(() => {
            if (msg.parentNode) {
                msg.parentNode.removeChild(msg);
            }
        }, 4000);
    }

    // Clear all offline data (for testing)
    clearAll() {
        const keysToRemove = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && key.includes('_') && !key.startsWith('cached_')) {
                keysToRemove.push(key);
            }
        }
        keysToRemove.forEach(key => localStorage.removeItem(key));
        this.showMessage('🧹 Cleared all offline data', true);
    }
}

// Create global instance
window.offlineHelper = new OfflineHelper();