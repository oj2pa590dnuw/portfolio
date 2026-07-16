<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['advisor_id'])) {
    header("Location: login.php");
    exit;
}

$is_superadmin = ($_SESSION['role'] == 'superadmin');
$advisor_id = $_SESSION['advisor_id'];

$stmt = mysqli_prepare($conn, "SELECT advisor_name, email, department, role, approved, registration_date FROM users WHERE advisor_id = ?");
mysqli_stmt_bind_param($stmt, "i", $advisor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current_user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$all_users = [];
if ($is_superadmin) {
    $users_result = mysqli_query($conn, "SELECT * FROM users ORDER BY advisor_name");
    while ($row = mysqli_fetch_assoc($users_result)) {
        $all_users[] = $row;
    }
}

$page_title = $is_superadmin ? 'User Management' : 'Your Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?= htmlspecialchars($page_title) ?> | SIPP OJT Monitor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <?php include 'navbar.php'; ?>

    <div id="toast" style="display: none; position: fixed; top: 20px; right: 20px; background: white; border-radius: var(--radius-md); box-shadow: var(--shadow); padding: 1rem 1.5rem; z-index: 2000; border-left: 4px solid var(--accent);"></div>

    <div class="section-card">
        <div class="section-header">
            <h2>👤 Your Profile</h2>
        </div>
        <div class="profile-info">
            <div class="info-item"><div class="info-label">Name</div><div class="info-value"><?= htmlspecialchars($current_user['advisor_name']) ?></div></div>
            <div class="info-item"><div class="info-label">Email</div><div class="info-value"><?= htmlspecialchars($current_user['email']) ?></div></div>
            <div class="info-item"><div class="info-label">Department</div><div class="info-value"><?= isset($schools[$current_user['department']]) ? $schools[$current_user['department']] : htmlspecialchars($current_user['department']) ?></div></div>
            <div class="info-item"><div class="info-label">Role</div><div class="info-value"><?= ucfirst($current_user['role']) ?></div></div>
            <div class="info-item"><div class="info-label">Status</div><div class="info-value"><?= $current_user['approved'] ? '✅ Approved' : '⏳ Pending Approval' ?></div></div>
            <div class="info-item"><div class="info-label">Registered</div><div class="info-value"><?= date('M d, Y', strtotime($current_user['registration_date'])) ?></div></div>
        </div>
        <button class="btn-change-password" onclick="openPasswordModal()">Change Password</button>
    </div>

    <!-- Password Change Modal -->
    <div class="modal-overlay" id="passwordModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Change Password</h3>
                <button class="modal-close" onclick="closePasswordModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="modalMessage" class="modal-message"></div>
                <form id="changePasswordForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <div class="form-group-modal">
                        <label>Current Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="current_password" id="currentPassword" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('currentPassword', this)">👁️</button>
                        </div>
                    </div>
                    <div class="form-group-modal">
                        <label>New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="new_password" id="newPassword" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('newPassword', this)">👁️</button>
                        </div>
                        <small id="passwordStrength" class="password-strength"></small>
                    </div>
                    <div class="form-group-modal">
                        <label>Confirm New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="confirm_password" id="confirmPassword" required>
                            <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirmPassword', this)">👁️</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Cancel</button>
                <button type="button" class="btn" onclick="submitPasswordChange()">Update Password</button>
            </div>
        </div>
    </div>

    <?php if ($is_superadmin): ?>
    <div class="section-card">
        <div class="section-header">
            <h2>👥 All Users</h2>
            <div>
                <a href="add_user.php" class="btn btn-add">+ Add New User</a>
                <a href="pending_users.php" class="btn btn-secondary" style="margin-left: 1rem;">Pending Approvals</a>
            </div>
        </div>
        <div class="table-wrapper">
            <table class="user-table">
                <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Role</th><th>Approved</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['advisor_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= isset($schools[$user['department']]) ? $schools[$user['department']] : $user['department'] ?></td>
                        <td><?= ucfirst($user['role']) ?></td>
                        <td><?= $user['approved'] ? '✅' : '❌' ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($user['role'] != 'superadmin'): ?>
                                    <a href="edit_user.php?id=<?= $user['advisor_id'] ?>" class="btn btn-edit btn-sm">Edit</a>
                                    <?php if ($user['advisor_id'] != $_SESSION['advisor_id']): ?>
                                        <form method="POST" action="delete_user.php" class="superadmin-action" data-confirm="Delete this user?" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="user_id" value="<?= $user['advisor_id'] ?>">
                                            <button type="submit" class="btn btn-delete btn-sm">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (!$user['approved']): ?>
                                        <form method="POST" action="pending_users.php" class="superadmin-action" data-confirm="Approve this user?" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="user_id" value="<?= $user['advisor_id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-edit btn-sm">Approve</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge">Protected</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    function openPasswordModal() {
        document.getElementById('passwordModal').classList.add('active');
        document.getElementById('changePasswordForm').reset();
        document.getElementById('modalMessage').className = 'modal-message';
        document.getElementById('modalMessage').innerHTML = '';
        document.getElementById('passwordStrength').innerHTML = '';
    }
    function closePasswordModal() { document.getElementById('passwordModal').classList.remove('active'); }
    document.getElementById('passwordModal').addEventListener('click', function(e) { if (e.target === this) closePasswordModal(); });

    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.textContent = input.type === 'password' ? '👁️' : '🔒';
    }

    document.getElementById('newPassword').addEventListener('input', function() {
        const pwd = this.value;
        const strength = checkPasswordStrength(pwd);
        const msg = document.getElementById('passwordStrength');
        if (pwd.length === 0) msg.innerHTML = '';
        else if (strength === 'strong') { msg.innerHTML = '✓ Strong password'; msg.style.color = 'green'; }
        else { msg.innerHTML = '❌ Weak: 8+ chars, uppercase, lowercase, number, special'; msg.style.color = 'red'; }
    });

    function checkPasswordStrength(pwd) {
        if (pwd.length < 8) return 'weak';
        if (!/[A-Z]/.test(pwd)) return 'weak';
        if (!/[a-z]/.test(pwd)) return 'weak';
        if (!/[0-9]/.test(pwd)) return 'weak';
        if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)) return 'weak';
        return 'strong';
    }

    function showToast(message, isSuccess = true) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.style.borderLeftColor = isSuccess ? 'var(--accent)' : 'var(--danger)';
        toast.style.display = 'block';
        setTimeout(() => toast.style.display = 'none', 4000);
    }

    async function submitPasswordChange() {
        const form = document.getElementById('changePasswordForm');
        const formData = new FormData(form);
        const messageDiv = document.getElementById('modalMessage');

        const current = formData.get('current_password');
        const newPass = formData.get('new_password');
        const confirm = formData.get('confirm_password');
        if (!current || !newPass || !confirm) { messageDiv.className = 'modal-message error'; messageDiv.innerHTML = 'All fields are required.'; return; }
        if (newPass !== confirm) { messageDiv.className = 'modal-message error'; messageDiv.innerHTML = 'New passwords do not match.'; return; }
        if (checkPasswordStrength(newPass) !== 'strong') { messageDiv.className = 'modal-message error'; messageDiv.innerHTML = 'Password does not meet strength requirements.'; return; }

        try {
            const response = await fetch('change_password_ajax.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) {
                messageDiv.className = 'modal-message success';
                messageDiv.innerHTML = data.message;
                form.reset();
                document.getElementById('passwordStrength').innerHTML = '';
                showToast(data.message);
                setTimeout(() => closePasswordModal(), 1500);
            } else {
                messageDiv.className = 'modal-message error';
                messageDiv.innerHTML = data.message;
            }
        } catch (error) {
            messageDiv.className = 'modal-message error';
            messageDiv.innerHTML = 'Network error. Please try again.';
        }
    }
</script>
<?php include 'superadmin_modal.php'; ?>
</body>
</html>