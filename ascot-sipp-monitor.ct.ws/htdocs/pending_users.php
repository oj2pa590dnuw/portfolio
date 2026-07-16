<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'superadmin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'])) {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

    // Verify current user's password
    if (!isset($_POST['superadmin_password'])) {
        die("Password confirmation required.");
    }
    $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE advisor_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['advisor_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$user || !password_verify($_POST['superadmin_password'], $user['password'])) {
        die("Invalid password.");
    }

    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        $stmt = mysqli_prepare($conn, "UPDATE users SET approved = 1, approved_date = NOW() WHERE advisor_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } elseif ($action === 'reject') {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE advisor_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    regenerateCsrfToken();
    header("Location: pending_users.php");
    exit;
}

$result = mysqli_query($conn, "SELECT * FROM users WHERE approved = 0 AND role = 'advisor' ORDER BY registration_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Pending Users | SIPP OJT Monitor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <?php include 'navbar.php'; ?>
    <div class="card">
        <h2>Pending Advisor Registrations</h2>
        <?php if (mysqli_num_rows($result) == 0): ?>
            <p>No pending registrations.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Registered On</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['advisor_name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= isset($schools[$row['department']]) ? htmlspecialchars($schools[$row['department']]) : htmlspecialchars($row['department']) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($row['registration_date'])) ?></td>
                            <td>
                                <form method="POST" class="superadmin-action" data-confirm="Approve this user?">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="user_id" value="<?= $row['advisor_id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-edit">Approve</button>
                                </form>
                                <form method="POST" class="superadmin-action" data-confirm="Reject and delete this registration?">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="user_id" value="<?= $row['advisor_id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-delete">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'superadmin_modal.php'; ?>
</body>
</html>