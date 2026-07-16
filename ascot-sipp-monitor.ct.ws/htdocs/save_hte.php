<?php
require_once 'db.php';

if (!isset($_SESSION['advisor_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: hte_list.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    die("Invalid CSRF token.");
}

$hte_id = isset($_POST['hte_id']) ? (int)$_POST['hte_id'] : 0;
$is_superadmin = ($_SESSION['role'] == 'superadmin');

// If editing, require password confirmation
if ($hte_id > 0) {
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
    if (!$is_superadmin) {
        die("You do not have permission to edit HTEs.");
    }
}

$hte_name = trim($_POST['hte_name']);
$hte_representative = trim($_POST['hte_representative']);
if (empty($hte_name) || empty($hte_representative)) {
    die("HTE Name and Representative are required.");
}

$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$contact_number = isset($_POST['contact_number']) ? trim($_POST['contact_number']) : '';
$moa_specify = isset($_POST['moa_specify']) ? trim($_POST['moa_specify']) : 'ASCOT (Generalized)';
$hte_type = isset($_POST['hte_type']) ? trim($_POST['hte_type']) : 'Local';

// Local HTEs are always active with eternal dates
if ($hte_type === 'Local') {
    $start_memo_of_agreement = '2000-01-01';
    $end_memo_of_agreement = '2099-12-31';
    $active_moa = 1;
} else {
    $start_memo_of_agreement = !empty($_POST['start_memo_of_agreement']) ? trim($_POST['start_memo_of_agreement']) : null;
    $end_memo_of_agreement = !empty($_POST['end_memo_of_agreement']) ? trim($_POST['end_memo_of_agreement']) : null;
    $active_moa = isset($_POST['active_moa']) ? (int)$_POST['active_moa'] : 0;
}

$verified = 0;
if ($is_superadmin && isset($_POST['verified'])) {
    $verified = (int)$_POST['verified'];
}

// File upload handling
$upload_dir = __DIR__ . '/uploads/moa/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
$moa_file = null;
$old_file = null;

if ($hte_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT moa_file FROM host_training_establishment WHERE hte_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $hte_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $old = mysqli_fetch_assoc($result);
    $old_file = $old['moa_file'] ?? null;
    mysqli_stmt_close($stmt);
}

if (isset($_FILES['moa_file']) && $_FILES['moa_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['moa_file'];
    $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $max_size = 10 * 1024 * 1024;
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_extensions)) {
        die("Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG.");
    }
    if ($file['size'] > $max_size) {
        die("File too large. Maximum size is 10 MB.");
    }
    $moa_file = uniqid('moa_', true) . '.' . $file_ext;
    $target_path = $upload_dir . $moa_file;
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        die("Failed to upload file. Please check directory permissions.");
    }
    if ($old_file && file_exists($upload_dir . $old_file)) {
        unlink($upload_dir . $old_file);
    }
} else {
    $moa_file = $old_file;
}

// Insert or update
if ($hte_id == 0) {
    $created_by = $_SESSION['advisor_id'];
    $stmt = mysqli_prepare($conn, "INSERT INTO host_training_establishment 
                (hte_name, hte_representative, address, contact_number, 
                 start_memo_of_agreement, end_memo_of_agreement, 
                 moa_specify, moa_file, hte_type, active_moa, verified, created_by)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssssssssiii", 
        $hte_name, $hte_representative, $address, $contact_number,
        $start_memo_of_agreement, $end_memo_of_agreement,
        $moa_specify, $moa_file, $hte_type, $active_moa, $verified, $created_by);
} else {
    $stmt = mysqli_prepare($conn, "UPDATE host_training_establishment SET 
                hte_name=?, hte_representative=?, address=?, contact_number=?,
                start_memo_of_agreement=?, end_memo_of_agreement=?,
                moa_specify=?, moa_file=?, hte_type=?, active_moa=?, verified=?
                WHERE hte_id=?");
    mysqli_stmt_bind_param($stmt, "sssssssssiii", 
        $hte_name, $hte_representative, $address, $contact_number,
        $start_memo_of_agreement, $end_memo_of_agreement,
        $moa_specify, $moa_file, $hte_type, $active_moa, $verified, $hte_id);
}

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    regenerateCsrfToken();
    header("Location: hte_list.php");
    exit;
} else {
    error_log("MySQL Error in save_hte.php: " . mysqli_error($conn));
    if ($moa_file && file_exists($upload_dir . $moa_file)) {
        unlink($upload_dir . $moa_file);
    }
    die("An error occurred while saving. Please try again.");
}
?>