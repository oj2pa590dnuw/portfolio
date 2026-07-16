<?php
require_once 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Use prepared statement to prevent SQL injection
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $_POST['email']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($_POST['password'], $user['password'])) {
            if ($user['approved'] == 0) {
                $error = "Your account is pending approval. Please wait for the superadmin to approve.";
            } else {
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);
                $_SESSION['advisor_id'] = $user['advisor_id'];
                $_SESSION['advisor_name'] = $user['advisor_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department'] = $user['department'];
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Email not found.";
    }
    mysqli_stmt_close($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login | SIPP OJT Monitor</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .login-container .card {
            padding: 2rem;
        }
        .login-container .error-message {
            background-color: #fdf2f2;
            color: var(--danger);
            padding: 0.75rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
            border: 1px solid #f9d6d6;
        }
        .toggle-pwd {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 0.5rem;
            margin-bottom: 1rem;
        }
        .login-container .register-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .login-container .register-link a {
            color: var(--accent);
            text-decoration: none;
        }
        .login-container .register-link a:hover {
            text-decoration: underline;
        }
        .login-container .btn {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <h2>SIPP OJT Monitor</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    <div class="toggle-pwd">
                        <input type="checkbox" onclick="togglePassword()"> Show Password
                    </div>
                </div>
                <?php if ($error): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <button type="submit" class="btn">Login</button>
            </form>
            <div class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
    <script>
        function togglePassword() {
            var pwd = document.getElementById("password");
            if (pwd.type === "password") {
                pwd.type = "text";
            } else {
                pwd.type = "password";
            }
        }
    </script>
</body>
</html>