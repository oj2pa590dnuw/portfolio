<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['advisor_id'])) {
    header("Location: login.php");
    exit;
}

$is_superadmin = ($_SESSION['role'] == 'superadmin');
$advisor_department = $_SESSION['department'];
$current_advisor_id = $_SESSION['advisor_id'];

$batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
if ($batch_id <= 0) die("Invalid batch.");

// Fetch batch info
$batch_stmt = mysqli_prepare($conn,
    "SELECT b.*, h.hte_name, h.moa_specify, h.hte_id
     FROM student_batches b
     JOIN host_training_establishment h ON b.hte_id = h.hte_id
     WHERE b.batch_id = ?");
mysqli_stmt_bind_param($batch_stmt, "i", $batch_id);
mysqli_stmt_execute($batch_stmt);
$batch_result = mysqli_stmt_get_result($batch_stmt);
$batch = mysqli_fetch_assoc($batch_result);
mysqli_stmt_close($batch_stmt);

if (!$batch) die("Batch not found.");

// Permission check
if (!$is_superadmin) {
    $can_access = ($batch['moa_specify'] == 'ASCOT (Generalized)') || ($batch['moa_specify'] == $advisor_department);
    if (!$can_access) die("You do not have permission to view this batch.");
}

// Handle endorsement upload (superadmin only)
$message = '';
if ($is_superadmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_endorsement'])) {
    if (!verifyCsrfToken($_POST['csrf_token'])) die("Invalid CSRF token.");
    if (isset($_FILES['endorsement_file']) && $_FILES['endorsement_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/endorsements/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['endorsement_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','jpg','jpeg','png'];
        if (!in_array($ext, $allowed)) $message = "Invalid file type.";
        elseif ($_FILES['endorsement_file']['size'] > 10*1024*1024) $message = "File too large.";
        else {
            $filename = 'endorse_' . $batch_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['endorsement_file']['tmp_name'], $upload_dir . $filename)) {
                $upd_stmt = mysqli_prepare($conn, "UPDATE student_batches SET endorsement_file = ? WHERE batch_id = ?");
                mysqli_stmt_bind_param($upd_stmt, "si", $filename, $batch_id);
                mysqli_stmt_execute($upd_stmt);
                mysqli_stmt_close($upd_stmt);
                $batch['endorsement_file'] = $filename;
                $message = "Endorsement letter uploaded successfully.";
                regenerateCsrfToken();
            } else {
                $message = "Upload failed.";
            }
        }
    }
}

// Fetch students
$students_stmt = mysqli_prepare($conn,
    "SELECT * FROM students WHERE batch_id = ? ORDER BY student_name");
mysqli_stmt_bind_param($students_stmt, "i", $batch_id);
mysqli_stmt_execute($students_stmt);
$students_result = mysqli_stmt_get_result($students_stmt);
$students = [];
while ($row = mysqli_fetch_assoc($students_result)) {
    $students[] = $row;
}
mysqli_stmt_close($students_stmt);

function formatInternshipPeriod($start, $end) {
    if (empty($start) && empty($end)) return '—';
    $start_str = !empty($start) ? date('M d, Y', strtotime($start)) : '';
    $end_str = !empty($end) ? date('M d, Y', strtotime($end)) : '';
    if (!empty($start_str) && !empty($end_str)) return $start_str . ' - ' . $end_str;
    elseif (!empty($start_str)) return $start_str . ' - ';
    else return ' - ' . $end_str;
}

$default_sy_label = $batch['academic_year'] . '-' . ($batch['academic_year']+1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <title>Batch View | <?= htmlspecialchars($batch['hte_name']) ?></title>
   <link rel="stylesheet" href="style.css">
   <style>
      .batch-info { background: #f8faf7; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid var(--border); }
      .endorsement-section { background: #fff; border: 1px dashed #ccc; border-radius: var(--radius-sm); padding: 1rem; margin: 1rem 0; }
      .compact-table { font-size: 0.75rem; width: 100%; }
      .compact-table th, .compact-table td { padding: 0.3rem 0.4rem; white-space: nowrap; }
      .readonly-text { color: var(--text-light); }
      .table-wrapper { overflow-x: auto; }
   </style>
</head>
<body>
<div class="container">
    <?php include 'navbar.php'; ?>

    <div class="card">
        <div class="batch-info">
            <h3><?= htmlspecialchars($batch['hte_name']) ?> – Batch <?= htmlspecialchars($default_sy_label . ' ' . $batch['semester']) ?></h3>
            <p><strong>Academic Year:</strong> <?= $default_sy_label ?> | <strong>Semester:</strong> <?= $batch['semester'] ?></p>
            <?php if ($batch['endorsement_file']): ?>
                <p><strong>Endorsement Letter:</strong> <a href="view_endorsement.php?id=<?= $batch_id ?>" target="_blank">View / Download</a></p>
            <?php else: ?>
                <p><strong>Endorsement Letter:</strong> <span style="color: #856404;">Not yet uploaded</span></p>
            <?php endif; ?>
        </div>

        <?php if ($is_superadmin): ?>
        <div class="endorsement-section">
            <h4><?= $batch['endorsement_file'] ? 'Replace' : 'Upload' ?> Endorsement Letter</h4>
            <?php if ($message): ?><div class="success-message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <form method="post" enctype="multipart/form-data" style="display:flex; gap:1rem; align-items:center;">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="upload_endorsement" value="1">
                <input type="file" name="endorsement_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                <button type="submit" class="btn btn-sm">Upload</button>
            </form>
        </div>
        <?php endif; ?>

        <h4>Students in this Batch</h4>
        <div class="table-wrapper">
            <table class="compact-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>School</th>
                        <th>Program</th>
                        <th>Gender</th>
                        <th>Internship Period</th>
                        <th>RF</th><th>MC</th><th>PT</th><th>PC</th><th>TA</th><th>SIPP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['student_name']) ?></td>
                        <td><?= isset($schools[$s['school']]) ? $schools[$s['school']] : $s['school'] ?></td>
                        <td><?= htmlspecialchars($s['program']) ?></td>
                        <td><?= $s['gender'] ?></td>
                        <td><?= formatInternshipPeriod($s['internship_start'], $s['internship_end']) ?></td>
                        <td><?= $s['has_registration_form'] ? '✅' : '❌' ?></td>
                        <td><?= $s['has_medical_certificate'] ? '✅' : '❌' ?></td>
                        <td><?= $s['has_psycho_test'] ? '✅' : '❌' ?></td>
                        <td><?= $s['has_notary_p_c'] ? '✅' : '❌' ?></td>
                        <td><?= $s['has_notary_t_a'] ? '✅' : '❌' ?></td>
                        <td><?= $s['has_sipp'] ? '✅' : '❌' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="form-actions">
            <a href="index.php" class="btn btn-secondary">Back to Index</a>
            <a href="edit_student.php?batch_id=<?= $batch_id ?>" class="btn">Edit Batch</a>
        </div>
    </div>
</div>
<?php include 'superadmin_modal.php'; ?>
</body>
</html>