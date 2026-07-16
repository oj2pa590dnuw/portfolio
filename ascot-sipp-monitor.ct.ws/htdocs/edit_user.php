<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'superadmin') {
    header("Location: index.php");
    exit;
}

$user_id = (int)$_GET['id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE advisor_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
if (!$user) die("User not found.");
if ($user['role'] == 'superadmin') die("Superadmin account cannot be edited.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit User | SIPP OJT Monitor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <?php include 'navbar.php'; ?>
    <div class="card">
        <h2>Edit User</h2>
        <form method="POST" action="save_user.php" class="superadmin-action" data-confirm="Update this user?" onsubmit="return validatePassword()">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="advisor_id" value="<?= $user['advisor_id'] ?>">
            <div class="form-grid">
                <div class="form-group"><label>Name:</label><input type="text" name="advisor_name" value="<?= htmlspecialchars($user['advisor_name']) ?>" required></div>
                <div class="form-group"><label>Email:</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></div>
                <div class="form-group">
                    <label>Department (School):</label>
                    <select name="department" required>
                        <?php foreach ($schools as $code => $name): ?>
                            <option value="<?= $code ?>" <?= $user['department'] == $code ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>New Password (leave blank to keep current):</label>
                    <div style="display: flex; gap: 5px;">
                        <input type="password" name="password" id="password" style="flex:1">
                        <button type="button" onclick="togglePassword()" class="btn btn-secondary">👁️</button>
                    </div>
                    <small id="pwd-strength" style="color: #666;"></small>
                </div>
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role" required>
                        <option value="advisor" <?= $user['role'] == 'advisor' ? 'selected' : '' ?>>Advisor</option>
                        <option value="superadmin" disabled>Superadmin (can't change)</option>
                    </select>
                </div>
            </div>
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn">Update User</button>
                <a href="users_list.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<script>
    function togglePassword() { var pwd = document.getElementById("password"); pwd.type = pwd.type === "password" ? "text" : "password"; }
    function validatePassword() { var pwd = document.getElementById("password").value; if (pwd.length > 0 && checkPasswordStrength(pwd) !== "strong") { alert("Weak password."); return false; } return true; }
    function checkPasswordStrength(pwd) { if (pwd.length < 8 || !/[A-Z]/.test(pwd) || !/[a-z]/.test(pwd) || !/[0-9]/.test(pwd) || !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)) return "weak"; return "strong"; }
    document.getElementById("password").addEventListener("input", function() { var pwd = this.value, strength = checkPasswordStrength(pwd), msg = document.getElementById("pwd-strength"); if (strength === "strong") { msg.innerHTML = "✓ Strong"; msg.style.color = "green"; } else if (pwd.length > 0) { msg.innerHTML = "❌ Weak"; msg.style.color = "red"; } else { msg.innerHTML = ""; } });
</script>
<?php include 'superadmin_modal.php'; ?>
</body>
</html>