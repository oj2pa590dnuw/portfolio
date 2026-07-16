<?php require_once 'db.php'; ?>
<!DOCTYPE html>
<html>
<head><title>Forgot Password</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
    <div class="card" style="max-width:400px; margin:100px auto;">
        <h2>Reset Password</h2>
        <?php
        $msg = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $stmt = mysqli_prepare($conn, "SELECT advisor_id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $upd = mysqli_prepare($conn, "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE advisor_id = ?");
                mysqli_stmt_bind_param($upd, "ssi", $token, $expires, $row['advisor_id']);
                mysqli_stmt_execute($upd);
                // Send email (replace with real logic)
                $reset_link = "https://yourdomain.com/reset_password.php?token=" . $token;
                mail($email, "Password Reset", "Click here to reset: " . $reset_link);
                $msg = "If that email exists, a reset link has been sent.";
            } else {
                $msg = "If that email exists, a reset link has been sent.";
            }
            mysqli_stmt_close($stmt);
        }
        ?>
        <?php if ($msg): ?><div class="success-message"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
            <button type="submit" class="btn">Send Reset Link</button>
        </form>
        <p style="text-align:center; margin-top:1rem;"><a href="login.php">Back to Login</a></p>
    </div>
</div>
</body>
</html>