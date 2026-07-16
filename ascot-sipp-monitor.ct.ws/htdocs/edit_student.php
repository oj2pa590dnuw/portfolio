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
    if (!$can_access) die("You do not have permission to edit this batch.");
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

// HTE options for superadmin
$hte_options = [];
if ($is_superadmin) {
    $hte_sql = "SELECT hte_id, hte_name FROM host_training_establishment ORDER BY hte_name";
    $hte_stmt = mysqli_prepare($conn, $hte_sql);
    mysqli_stmt_execute($hte_stmt);
    $hte_list_result = mysqli_stmt_get_result($hte_stmt);
    while ($row = mysqli_fetch_assoc($hte_list_result)) {
        $hte_options[] = $row;
    }
    mysqli_stmt_close($hte_stmt);
}

$default_sy_label = $batch['academic_year'] . '-' . ($batch['academic_year']+1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <title>Edit Batch | <?= htmlspecialchars($batch['hte_name']) ?></title>
   <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <?php include 'navbar.php'; ?>
    <div class="card">
        <div class="batch-info">
            <h3><?= htmlspecialchars($batch['hte_name']) ?> – Batch <?= htmlspecialchars($default_sy_label . ' ' . $batch['semester']) ?></h3>
            <p><strong>Academic Year:</strong> <?= $default_sy_label ?> | <strong>Semester:</strong> <?= $batch['semester'] ?></p>
        </div>

        <form method="POST" action="save_student.php" id="batch-edit-form">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="batch_edit" value="1">
            <input type="hidden" name="hte_id" value="<?= $batch['hte_id'] ?>">

            <div class="table-wrapper">
                <table class="compact-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <?php if (!$is_superadmin): ?>
                            <th>Gender</th>
                            <th>Program</th>
                            <th>Internship Start</th>
                            <th>Internship End</th>
                            <?php endif; ?>
                            <th>AY</th>
                            <th>Sem</th>
                            <?php if ($is_superadmin): ?>
                            <th class="check-col"><input type="checkbox" id="toggle-reg" title="Toggle All">RF</th>
                            <th class="check-col"><input type="checkbox" id="toggle-med" title="Toggle All">MC</th>
                            <th class="check-col"><input type="checkbox" id="toggle-psych" title="Toggle All">PT</th>
                            <th class="check-col"><input type="checkbox" id="toggle-pc" title="Toggle All">PC</th>
                            <th class="check-col"><input type="checkbox" id="toggle-ta" title="Toggle All">TA</th>
                            <th class="check-col"><input type="checkbox" id="toggle-sipp" title="Toggle All">SIPP</th>
                            <th>HTE</th>
                            <?php endif; ?>
                            <th style="width:60px;">Del</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $row): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="students[<?= $index ?>][id]" value="<?= $row['student_id'] ?>">
                                <?php if (!$is_superadmin): ?>
                                    <!-- Advisor can edit student name -->
                                    <input type="text" name="students[<?= $index ?>][student_name]" value="<?= htmlspecialchars($row['student_name']) ?>" style="width:100%;" required>
                                <?php else: ?>
                                    <!-- Superadmin sees name as readonly -->
                                    <input type="hidden" name="students[<?= $index ?>][student_name]" value="<?= htmlspecialchars($row['student_name']) ?>">
                                    <?= htmlspecialchars($row['student_name']) ?>
                                <?php endif; ?>
                                <input type="hidden" name="students[<?= $index ?>][extra_notes]" value="<?= htmlspecialchars($row['extra_notes']) ?>">
                                <input type="hidden" name="students[<?= $index ?>][school]" value="<?= $row['school'] ?>">
                                <input type="hidden" name="students[<?= $index ?>][academic_year]" value="<?= $row['academic_year'] ?>">
                                <input type="hidden" name="students[<?= $index ?>][semester]" value="<?= $row['semester'] ?>">
                            </td>

                            <?php if (!$is_superadmin): ?>
                            <td>
                                <select name="students[<?= $index ?>][gender]">
                                    <option value="Male" <?= $row['gender']=='Male'?'selected':'' ?>>Male</option>
                                    <option value="Female" <?= $row['gender']=='Female'?'selected':'' ?>>Female</option>
                                </select>
                            </td>
                            <td>
                                <select name="students[<?= $index ?>][program]" style="min-width:120px;">
                                    <?php
                                    $student_school = $row['school'];
                                    if (isset($programs_by_school[$student_school])) {
                                        foreach ($programs_by_school[$student_school] as $prog) {
                                            $selected = ($prog == $row['program']) ? 'selected' : '';
                                            echo "<option value='$prog' $selected>$prog</option>";
                                        }
                                    } else {
                                        echo "<option value='" . htmlspecialchars($row['program']) . "' selected>" . htmlspecialchars($row['program']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td><input type="date" name="students[<?= $index ?>][internship_start]" value="<?= $row['internship_start'] ?? '' ?>"></td>
                            <td><input type="date" name="students[<?= $index ?>][internship_end]" value="<?= $row['internship_end'] ?? '' ?>"></td>
                            <?php else: ?>
                                <input type="hidden" name="students[<?= $index ?>][gender]" value="<?= $row['gender'] ?>">
                                <input type="hidden" name="students[<?= $index ?>][program]" value="<?= htmlspecialchars($row['program']) ?>">
                                <input type="hidden" name="students[<?= $index ?>][internship_start]" value="<?= $row['internship_start'] ?? '' ?>">
                                <input type="hidden" name="students[<?= $index ?>][internship_end]" value="<?= $row['internship_end'] ?? '' ?>">
                            <?php endif; ?>

                            <td class="readonly-text"><?= $row['academic_year'] . '-' . ($row['academic_year']+1) ?></td>
                            <td class="readonly-text"><?= $row['semester'] ?></td>

                            <?php if ($is_superadmin): ?>
                            <td class="check-col"><input type="checkbox" name="students[<?= $index ?>][has_registration_form]" value="1" <?= $row['has_registration_form']?'checked':'' ?> class="chk-reg"></td>
                            <td class="check-col"><input type="checkbox" name="students[<?= $index ?>][has_medical_certificate]" value="1" <?= $row['has_medical_certificate']?'checked':'' ?> class="chk-med"></td>
                            <td class="check-col"><input type="checkbox" name="students[<?= $index ?>][has_psycho_test]" value="1" <?= $row['has_psycho_test']?'checked':'' ?> class="chk-psych"></td>
                            <td class="check-col"><input type="checkbox" name="students[<?= $index ?>][has_notary_p_c]" value="1" <?= $row['has_notary_p_c']?'checked':'' ?> class="chk-pc"></td>
                            <td class="check-col"><input type="checkbox" name="students[<?= $index ?>][has_notary_t_a]" value="1" <?= $row['has_notary_t_a']?'checked':'' ?> class="chk-ta"></td>
                            <td class="check-col"><input type="checkbox" name="students[<?= $index ?>][has_sipp]" value="1" <?= $row['has_sipp']?'checked':'' ?> class="chk-sipp"></td>
                            <td>
                                <select name="students[<?= $index ?>][hte_id]" style="min-width:150px;">
                                    <?php foreach ($hte_options as $opt): ?>
                                        <option value="<?= $opt['hte_id'] ?>" <?= $opt['hte_id']==$row['hte_id']?'selected':'' ?>><?= htmlspecialchars($opt['hte_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <?php else: ?>
                                <input type="hidden" name="students[<?= $index ?>][has_registration_form]" value="<?= $row['has_registration_form'] ?>">
                                <input type="hidden" name="students[<?= $index ?>][has_medical_certificate]" value="<?= $row['has_medical_certificate'] ?>">
                                <input type="hidden" name="students[<?= $index ?>][has_psycho_test]" value="<?= $row['has_psycho_test'] ?>">
                                <input type="hidden" name="students[<?= $index ?>][has_notary_p_c]" value="<?= $row['has_notary_p_c'] ?>">
                                <input type="hidden" name="students[<?= $index ?>][has_notary_t_a]" value="<?= $row['has_notary_t_a'] ?>">
                                <input type="hidden" name="students[<?= $index ?>][has_sipp]" value="<?= $row['has_sipp'] ?>">
                                <input type="hidden" name="students[<?= $index ?>][hte_id]" value="<?= $row['hte_id'] ?>">
                            <?php endif; ?>

                            <td style="text-align:center;">
                                <button type="button" class="btn btn-delete btn-sm superadmin-delete-btn"
                                        data-student-id="<?= $row['student_id'] ?>"
                                        data-confirm="Delete this student?"
                                        data-redirect="edit_student.php?batch_id=<?= $batch_id ?>">✕</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn">Save All Changes</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <span class="row-count-badge"><?= count($students) ?> student(s)</span>
            </div>
        </form>
    </div>
</div>

<script>
<?php if ($is_superadmin): ?>
document.getElementById('toggle-reg').addEventListener('change', function(){ document.querySelectorAll('.chk-reg').forEach(cb => cb.checked = this.checked); });
document.getElementById('toggle-med').addEventListener('change', function(){ document.querySelectorAll('.chk-med').forEach(cb => cb.checked = this.checked); });
document.getElementById('toggle-psych').addEventListener('change', function(){ document.querySelectorAll('.chk-psych').forEach(cb => cb.checked = this.checked); });
document.getElementById('toggle-pc').addEventListener('change', function(){ document.querySelectorAll('.chk-pc').forEach(cb => cb.checked = this.checked); });
document.getElementById('toggle-ta').addEventListener('change', function(){ document.querySelectorAll('.chk-ta').forEach(cb => cb.checked = this.checked); });
document.getElementById('toggle-sipp').addEventListener('change', function(){ document.querySelectorAll('.chk-sipp').forEach(cb => cb.checked = this.checked); });
<?php endif; ?>
</script>
<?php require_once 'superadmin_modal.php'; ?>
</body>
</html>