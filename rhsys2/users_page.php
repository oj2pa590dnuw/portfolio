<?php
require 'likod/session_utils.php'; 
require 'likod/db_con.php'; 
enforce_role(['is_midwife', 'is_admin']);

// Helper function to convert role flags to text
function getRoleText($user) {
    if ($user['is_admin']) return 'Admin';
    if ($user['is_midwife']) return 'Midwife';
    if ($user['is_bns']) return 'BNS';
    if ($user['is_bhw']) return 'BHW';
    return 'Unknown';
}

// 1. Fetch PENDING/UNAPPROVED Users
$pending_users = [];
$sql_pending = "SELECT id, first_name, last_name, email, is_bns, is_bhw, is_midwife, is_admin FROM users WHERE approved_by_admin = 0";
$result_pending = $conn->query($sql_pending);
if ($result_pending) {
    while ($row = $result_pending->fetch_assoc()) {
        $pending_users[] = $row;
    }
}

// 2. Fetch APPROVED Users
$approved_users = [];
$sql_approved = "SELECT id, first_name, last_name, email, is_bns, is_bhw, is_midwife, is_admin FROM users WHERE approved_by_admin = 1";
$result_approved = $conn->query($sql_approved);
if ($result_approved) {
    while ($row = $result_approved->fetch_assoc()) {
        $approved_users[] = $row;
    }
}
// Close connection after all fetches
$conn->close();

$message = $_GET['message'] ?? null;
$status = $_GET['status'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management & Approval</title>
    <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">
    <script>
        // Check for status messages on load (using the GET parameters from the form handler)
        document.addEventListener('DOMContentLoaded', () => {
            // Note: The alert is fine here since it only displays success/error, not interactive
            const status = '<?php echo $status; ?>';
            const message = '<?php echo htmlspecialchars($message); ?>';
            if (status === 'success' && message) {
                alert('✅ Success: ' + message);
            } else if (status === 'error' && message) {
                alert('❌ Error: ' + message);
            }
        });
        
        // 💥 NEW: Confirmation dialog for deletion
        function confirmDelete() {
            return confirm("WARNING: Are you sure you want to permanently delete this user? This cannot be undone.");
        }
    </script>
</head>
<body>
<div class="app-container">
    <?php include 'navbar.php'; ?>

    <div class="main-content">
        <div class="large-container">
            <h1>User Management & Approval</h1>
            
            <div class="user-management-section">
                
                <div class="card">
                    <h2>Pending Approvals (<?php echo count($pending_users); ?>)</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($pending_users) > 0): ?>
                                    <?php foreach ($pending_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo getRoleText($user); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" action="likod/user_management.php" style="display:inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="btn btn-primary">Approve</button>
                                                </form>
                                                
                                                <form method="POST" action="likod/user_management.php" style="display:inline;" onsubmit="return confirmDelete()">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">🎉 No pending users!</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h2>Approved Users (<?php echo count($approved_users); ?>)</h2>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($approved_users) > 0): ?>
                                    <?php foreach ($approved_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo getRoleText($user); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" action="likod/user_management.php" style="display:inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="revoke">
                                                    <button type="submit" class="btn btn-warning" 
                                                        <?php if (isset($_SESSION['user_id']) && $user['id'] === $_SESSION['user_id']) echo 'disabled'; ?>>
                                                        Revoke
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" action="likod/user_management.php" style="display:inline;" onsubmit="return confirmDelete()">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-danger"
                                                        <?php if (isset($_SESSION['user_id']) && $user['id'] === $_SESSION['user_id']) echo 'disabled'; ?>>
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">There are no approved users yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>