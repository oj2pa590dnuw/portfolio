<?php
require_once 'db.php';
require_once 'school_programs.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    $advisor_name = trim($_POST['advisor_name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $password = $_POST['password'];

    function isStrongPassword($pwd) {
        return strlen($pwd) >= 8 &&
               preg_match('/[A-Z]/', $pwd) &&
               preg_match('/[a-z]/', $pwd) &&
               preg_match('/[0-9]/', $pwd) &&
               preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>\/?]/', $pwd);
    }

    if (!isStrongPassword($password)) {
        $error = "Password must be at least 8 characters, contain uppercase, lowercase, number, and special character.";
    } else {
        // Check email uniqueness with prepared statement
        $check_stmt = mysqli_prepare($conn, "SELECT advisor_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = "Email already exists. Please use another email or login.";
            mysqli_stmt_close($check_stmt);
        } else {
            mysqli_stmt_close($check_stmt);
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO users (advisor_name, email, department, password, role, approved, registration_date) VALUES (?, ?, ?, ?, 'advisor', 0, NOW())");
            mysqli_stmt_bind_param($stmt, "ssss", $advisor_name, $email, $department, $hashed);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Registration successful! Your account is pending approval. You will be notified once approved.";
                mysqli_stmt_close($stmt);
                regenerateCsrfToken();
            } else {
                $error = "Database error: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register | SIPP OJT Monitor</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Same styles as original */
        .register-container { max-width: 500px; margin: 50px auto; }
        .register-container .card { padding: 2rem; }
        .register-container .error-message { background-color: #fdf2f2; color: var(--danger); padding: 0.75rem; border-radius: var(--radius-sm); margin-bottom: 1rem; border: 1px solid #f9d6d6; }
        .register-container .success-message { background-color: #e8f3ee; color: var(--primary); padding: 0.75rem; border-radius: var(--radius-sm); margin-bottom: 1rem; border: 1px solid #cce3d8; }
        .toggle-pwd { display: flex; align-items: center; gap: 8px; margin-top: 0.5rem; margin-bottom: 1rem; }
        .register-container .login-link { text-align: center; margin-top: 1rem; font-size: 0.9rem; }
        .register-container .btn { width: 100%; }
        .pwd-strength { display: block; margin-top: 0.25rem; font-size: 0.8rem; }
    </style>
</head>
<body>
<div class="register-container">
    <div class="card">
        <h2>Advisor Registration</h2>
        <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success-message"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <div class="form-group"><label>Full Name</label><input type="text" name="advisor_name" value="<?= htmlspecialchars($advisor_name ?? '') ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required></div>
            <div class="form-group"><label>School/Department</label><select name="department" required><option value="">-- Select School/Department --</option><?php foreach ($schools as $code => $name): ?><option value="<?= $code ?>" <?= ($department ?? '') == $code ? 'selected' : '' ?>><?= $name ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" id="password" required><div class="toggle-pwd"><input type="checkbox" onclick="togglePassword()"> Show Password</div><small id="pwd-strength" class="pwd-strength"></small></div>
            <button type="submit" class="btn">Register</button>
        </form>
        <div class="login-link">Already have an account? <a href="login.php">Login</a></div>
    </div>
</div>
<script>
    function togglePassword() { var pwd = document.getElementById("password"); pwd.type = pwd.type === "password" ? "text" : "password"; }
    function checkPasswordStrength(pwd) {
        if (pwd.length < 8) return "weak"; if (!/[A-Z]/.test(pwd)) return "weak"; if (!/[a-z]/.test(pwd)) return "weak"; if (!/[0-9]/.test(pwd)) return "weak"; if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)) return "weak"; return "strong";
    }
    document.getElementById("password").addEventListener("input", function() {
        var pwd = this.value, strength = checkPasswordStrength(pwd), msg = document.getElementById("pwd-strength");
        if (strength === "strong") { msg.innerHTML = "✓ Strong password"; msg.style.color = "green"; }
        else if (pwd.length > 0) { msg.innerHTML = "❌ Weak: 8+ chars, uppercase, lowercase, number, special character"; msg.style.color = "red"; }
        else { msg.innerHTML = ""; }
    });
</script>
</body>
</html>