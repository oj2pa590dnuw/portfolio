<?php
require 'likod/session_utils.php';
// No role enforcement - anyone logged in can access their own account
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Account Settings - RHSYS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
    <style>
        .account-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 0;
        }

        .profile-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f1f1f1;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin-right: 1.5rem;
        }

        .profile-info h2 {
            margin: 0 0 0.5rem 0;
            color: #2d3748;
        }

        .profile-info .role-badge {
            background: #e2e8f0;
            color: #4a5568;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .user-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-item {
            padding: 1rem;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .detail-label {
            font-size: 0.875rem;
            color: #718096;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            font-size: 1rem;
            color: #2d3748;
            font-weight: 600;
        }

        .admin-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-top: 4px solid #48bb78;
        }

        .admin-section h3 {
            margin: 0 0 1.5rem 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .admin-card {
            background: #f7fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .admin-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .admin-card h4 {
            margin: 0 0 0.5rem 0;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .admin-card p {
            margin: 0 0 1rem 0;
            color: #718096;
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .admin-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
            transition: background 0.3s ease;
        }

        .admin-btn:hover {
            background: #5a6fd8;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #718096;
        }

        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include 'navbar.php'; ?>

        <div class="main-content">
            <div class="large-container">
                <div class="nav-links">
                    <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
                </div>

                <div class="account-container">
                    <h1>Account Settings</h1>

                    <!-- User Profile Section -->
                    <div class="profile-section">
                        <div class="profile-header">
                            <div class="profile-avatar" id="userAvatar">
                                👤
                            </div>
                            <div class="profile-info">
                                <h2 id="userName">Loading...</h2>
                                <span class="role-badge" id="userRole">Loading role...</span>
                            </div>
                        </div>

                        <div id="profileContent">
                            <div class="loading">Loading your profile information...</div>
                        </div>
                    </div>

                    <!-- Admin Section - Only show for admin/midwife -->
                    <?php if ($_SESSION['is_admin'] || $_SESSION['is_midwife']): ?>
                        <div class="admin-section">
                            <h3>🛡️ Administrative Tools</h3>
                            <p style="margin-bottom: 1.5rem; color: #718096;">System administration and management features
                            </p>
                            <div class="admin-grid">
                                <div class="admin-card">
                                    <h4>📋 Activity Log</h4>
                                    <p>Monitor system activity, user actions, and track all changes made within the system.
                                    </p>
                                    <a href="activity_log.php" class="admin-btn">View Activity Log</a>
                                </div>

                                <div class="admin-card">
                                    <h4>👥 User Management</h4>
                                    <p>Manage system users, approve new registrations, and maintain user access controls.
                                    </p>
                                    <a href="users_page.php" class="admin-btn">Manage Users</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load user profile data
        document.addEventListener('DOMContentLoaded', function () {
            loadUserProfile();
        });

        function loadUserProfile() {
            fetch('likod/get_user_profile.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayUserProfile(data.user);
                    } else {
                        showError(data.error || 'Failed to load profile');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Network error loading profile');
                });
        }

        function displayUserProfile(user) {
            // Update header info
            document.getElementById('userName').textContent = user.full_name;
            document.getElementById('userRole').textContent = user.role_text;

            // Set avatar based on role
            const avatar = document.getElementById('userAvatar');
            if (user.is_admin) avatar.innerHTML = '👑';
            else if (user.is_midwife) avatar.innerHTML = '👩‍⚕️';
            else if (user.is_bhw) avatar.innerHTML = '🩺';
            else if (user.is_bns) avatar.innerHTML = '📊';

            // Create profile details
            const profileContent = document.getElementById('profileContent');
            profileContent.innerHTML = `
        <div class="user-details-grid">
            <div class="detail-item">
                <div class="detail-label">User ID</div>
                <div class="detail-value">${user.id}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Email Address</div>
                <div class="detail-value">${user.email}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Account Status</div>
                <div class="detail-value">
                    ${user.approved_by_admin ?
                    '<span style="color: #38a169;">✓ Approved</span>' :
                    '<span style="color: #e53e3e;">⏳ Pending Approval</span>'
                }
                </div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Member Since</div>
                <div class="detail-value">${new Date(user.created_at).toLocaleDateString()}</div>
            </div>
        </div>
        
        <div style="background: #edf2f7; padding: 1rem; border-radius: 8px;">
            <strong>Role Permissions:</strong>
            <div style="margin-top: 0.5rem;">
                ${user.is_admin ? '<span style="background: #bee3f8; padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.5rem;">Admin</span>' : ''}
                ${user.is_midwife ? '<span style="background: #c6f6d5; padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.5rem;">Midwife</span>' : ''}
                ${user.is_bhw ? '<span style="background: #fed7d7; padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.5rem;">BHW</span>' : ''}
                ${user.is_bns ? '<span style="background: #e9d8fd; padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.5rem;">BNS</span>' : ''}
            </div>
        </div>
    `;
        }

        function showError(message) {
            const profileContent = document.getElementById('profileContent');
            profileContent.innerHTML = `
        <div class="error-message">
            ❌ Error: ${message}
        </div>
        <button onclick="loadUserProfile()" class="btn btn-secondary" style="margin-top: 1rem;">
            🔄 Retry
        </button>
    `;
        }
    </script>
</body>

</html>