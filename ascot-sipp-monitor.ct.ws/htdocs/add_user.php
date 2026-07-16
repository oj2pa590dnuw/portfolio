<?php
require_once 'db.php';
require_once 'school_programs.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'superadmin') { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head><title>Add User | SIPP OJT Monitor</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container"><?php include 'navbar.php'; ?>
    <div class="card"><h2>Add New User</h2>
        <form method="POST" action="save_user.php" onsubmit="return validatePassword()">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="form-grid">
                <div class="form-group"><label>Name:</label><input type="text" name="advisor_name" required></div>
                <div class="form-group"><label>Email:</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Department (School):</label><select name="department" required><?php foreach ($schools as $code => $name): ?><option value="<?= $code ?>"><?= $name ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label>Password:</label><div style="display: flex; gap: 5px;"><input type="password" name="password" id="password" required style="flex:1"><button type="button" onclick="togglePassword()" class="btn btn-secondary">👁️</button></div><small id="pwd-strength" style="color: #666;"></small></div>
                <div class="form-group"><label>Role:</label><select name="role" required><option value="advisor">Advisor</option><option value="superadmin">Superadmin</option></select></div>
            </div>
            <div style="margin-top: 1rem;"><button type="submit" class="btn">Save User</button><a href="users_list.php" class="btn btn-secondary">Cancel</a></div>
        </form>
    </div>
</div>
<script>
    function togglePassword() { var pwd = document.getElementById("password"); pwd.type = pwd.type === "password" ? "text" : "password"; }
    function validatePassword() { var pwd = document.getElementById("password").value; if (checkPasswordStrength(pwd) !== "strong") { alert("Weak password."); return false; } return true; }
    function checkPasswordStrength(pwd) { if (pwd.length < 8 || !/[A-Z]/.test(pwd) || !/[a-z]/.test(pwd) || !/[0-9]/.test(pwd) || !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)) return "weak"; return "strong"; }
    document.getElementById("password").addEventListener("input", function() { var pwd = this.value, strength = checkPasswordStrength(pwd), msg = document.getElementById("pwd-strength"); if (strength === "strong") { msg.innerHTML = "✓ Strong"; msg.style.color = "green"; } else if (pwd.length > 0) { msg.innerHTML = "❌ Weak"; msg.style.color = "red"; } else { msg.innerHTML = ""; } });
</script>
<?php include 'superadmin_modal.php'; ?>
</body>
</html>