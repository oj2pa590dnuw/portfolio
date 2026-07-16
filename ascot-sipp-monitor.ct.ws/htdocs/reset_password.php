<?php
require_once 'db.php';
$token = $_GET['token'] ?? '';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    if ($password !== $confirm) $error = "Passwords don't match.";
    elseif (!checkStrong($password)) $error = "Weak password.";
    else {
        $stmt = mysqli_prepare($conn, "SELECT advisor_id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $upd = mysqli_prepare($conn, "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE advisor_id = ?");
            mysqli_stmt_bind_param($upd, "si", $hash, $row['advisor_id']);
            mysqli_stmt_execute($upd);
            $success = "Password updated. <a href='login.php'>Login</a>";
        } else {
            $error = "Invalid or expired token.";
        }
        mysqli_stmt_close($stmt);
    }
}
function checkStrong($pwd) {
    return strlen($pwd)>=8 && preg_match('/[A-Z]/',$pwd) && preg_match('/[a-z]/',$pwd) && preg_match('/[0-9]/',$pwd) && preg_match('/[^A-Za-z0-9]/',$pwd);
}
?>
<!DOCTYPE html>
<html>
<head><title>Reset Password</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
    <div class="card" style="max-width:400px; margin:100px auto;">
        <h2>Set New Password</h2>
        <?php if($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if($success): ?><div class="success-message"><?= $success ?></div><?php else: ?>
        <form method="post">
            <div class="form-group"><label>New Password</label><input type="password" name="password" required></div>
            <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm" required></div>
            <button type="submit" class="btn">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>