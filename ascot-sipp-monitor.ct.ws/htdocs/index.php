<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['advisor_id'])) {
    header("Location: login.php");
    exit;
}

$is_superadmin = ($_SESSION['role'] == 'superadmin');
$advisor_id = $_SESSION['advisor_id'];

// ---- Filter values ----
$hte_filter        = isset($_GET['hte_id'])       ? (int)$_GET['hte_id']       : 0;
$semester_filter   = isset($_GET['sem'])          ? $_GET['sem']               : '';
$school_filter     = isset($_GET['school'])       ? $_GET['school']            : '';
$program_filter    = isset($_GET['program'])      ? $_GET['program']           : '';
$academic_year_filter = isset($_GET['ay'])        ? (int)$_GET['ay']           : 0;
$search_filter     = isset($_GET['search'])       ? trim($_GET['search'])      : '';

// ---- Build WHERE conditions ----
$conditions = [];
$params = [];
$types = "";

if ($hte_filter > 0) {
    $conditions[] = "s.hte_id = ?";
    $params[] = $hte_filter;
    $types .= "i";
}
if (!empty($semester_filter)) {
    $conditions[] = "s.semester = ?";
    $params[] = $semester_filter;
    $types .= "s";
}
if (!empty($school_filter)) {
    $conditions[] = "s.school = ?";
    $params[] = $school_filter;
    $types .= "s";
}
if (!empty($program_filter)) {
    $conditions[] = "s.program = ?";
    $params[] = $program_filter;
    $types .= "s";
}
if ($academic_year_filter > 0) {
    $conditions[] = "s.academic_year = ?";
    $params[] = $academic_year_filter;
    $types .= "i";
}
if (!empty($search_filter)) {
    // 🔍 Now also searches HTE name
    $conditions[] = "(s.student_name LIKE ? OR h.hte_name LIKE ?)";
    $params[] = "%$search_filter%";
    $params[] = "%$search_filter%";
    $types .= "ss";
}
if (!$is_superadmin) {
    $conditions[] = "s.advisor_id = ?";
    $params[] = $advisor_id;
    $types .= "i";
}

$where_clause = empty($conditions) ? "" : "WHERE " . implode(" AND ", $conditions);

$sql = "SELECT s.*,
            h.hte_name, h.active_moa, h.start_memo_of_agreement, h.end_memo_of_agreement,
            u.advisor_name, u.department AS advisor_department,
            CASE
                WHEN h.active_moa = 1 AND CURDATE() BETWEEN h.start_memo_of_agreement AND h.end_memo_of_agreement THEN '✅'
                WHEN h.active_moa = 1 AND CURDATE() > h.end_memo_of_agreement THEN '❌'
                ELSE '❌'
            END AS moa_status,
            b.batch_id,
            b.endorsement_file,
            b.academic_year AS group_academic_year,
            b.semester AS group_semester
        FROM students s
        LEFT JOIN host_training_establishment h ON s.hte_id = h.hte_id
        LEFT JOIN users u ON s.advisor_id = u.advisor_id
        LEFT JOIN student_batches b ON s.batch_id = b.batch_id
        $where_clause
        ORDER BY u.advisor_name, h.hte_name, b.academic_year, b.semester, s.student_name ASC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// ---- Summary stats ----
$total_students_sql = "SELECT COUNT(*) AS total FROM students " . ($is_superadmin ? "" : "WHERE advisor_id = ?");
if (!$is_superadmin) {
    $stmt_total = mysqli_prepare($conn, $total_students_sql);
    mysqli_stmt_bind_param($stmt_total, "i", $advisor_id);
} else {
    $stmt_total = mysqli_prepare($conn, $total_students_sql);
}
mysqli_stmt_execute($stmt_total);
$res_total = mysqli_stmt_get_result($stmt_total);
$total_students = mysqli_fetch_assoc($res_total)['total'];
mysqli_stmt_close($stmt_total);

$missing_sipp_sql = "SELECT COUNT(*) AS missing FROM students WHERE has_sipp = 0" . ($is_superadmin ? "" : " AND advisor_id = ?");
if (!$is_superadmin) {
    $stmt_miss = mysqli_prepare($conn, $missing_sipp_sql);
    mysqli_stmt_bind_param($stmt_miss, "i", $advisor_id);
} else {
    $stmt_miss = mysqli_prepare($conn, $missing_sipp_sql);
}
mysqli_stmt_execute($stmt_miss);
$res_miss = mysqli_stmt_get_result($stmt_miss);
$missing_sipp = mysqli_fetch_assoc($res_miss)['missing'];
mysqli_stmt_close($stmt_miss);

$today = date('Y-m-d');
$active_htes_sql = "SELECT COUNT(*) AS active FROM host_training_establishment WHERE active_moa = 1 AND start_memo_of_agreement <= ? AND end_memo_of_agreement >= ?";
$stmt_active = mysqli_prepare($conn, $active_htes_sql);
mysqli_stmt_bind_param($stmt_active, "ss", $today, $today);
mysqli_stmt_execute($stmt_active);
$res_active = mysqli_stmt_get_result($stmt_active);
$active_htes = mysqli_fetch_assoc($res_active)['active'];
mysqli_stmt_close($stmt_active);

// ---- Academic years for filter dropdown ----
$ay_list = [];
$ay_sql = "SELECT DISTINCT s.academic_year FROM students s ";
if (!$is_superadmin) $ay_sql .= " WHERE s.advisor_id = ?";
$ay_sql .= " ORDER BY s.academic_year DESC";
if (!$is_superadmin) {
    $stmt_ay = mysqli_prepare($conn, $ay_sql);
    mysqli_stmt_bind_param($stmt_ay, "i", $advisor_id);
} else {
    $stmt_ay = mysqli_prepare($conn, $ay_sql);
}
mysqli_stmt_execute($stmt_ay);
$res_ay = mysqli_stmt_get_result($stmt_ay);
while ($row = mysqli_fetch_assoc($res_ay)) {
    $ay_list[] = $row['academic_year'];
}
mysqli_stmt_close($stmt_ay);

// ---- Group data: Advisor -> HTE -> Group (AY & Sem) -> Students ----
$grouped_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $aid = $row['advisor_id'];
    $hid = $row['hte_id'];
    $gid = $row['batch_id'] ?? 0;  // still used internally for endorsement

    if ($is_superadmin) {
        if (!isset($grouped_data[$aid])) {
            $grouped_data[$aid] = [
                'name' => $row['advisor_name'],
                'department' => $row['advisor_department'],
                'htes' => []
            ];
        }
        if (!isset($grouped_data[$aid]['htes'][$hid])) {
            $grouped_data[$aid]['htes'][$hid] = [
                'name' => $row['hte_name'] ?? 'Unassigned',
                'moa_status' => $row['moa_status'] ?? '',
                'groups' => []
            ];
        }
        if (!isset($grouped_data[$aid]['htes'][$hid]['groups'][$gid])) {
            $grouped_data[$aid]['htes'][$hid]['groups'][$gid] = [
                'academic_year' => $row['group_academic_year'],
                'semester' => $row['group_semester'],
                'endorsement_file' => $row['endorsement_file'],
                'students' => []
            ];
        }
        $grouped_data[$aid]['htes'][$hid]['groups'][$gid]['students'][] = $row;
    } else {
        // Advisor view: HTE -> Group
        if (!isset($grouped_data[$hid])) {
            $grouped_data[$hid] = [
                'name' => $row['hte_name'] ?? 'Unassigned',
                'moa_status' => $row['moa_status'] ?? '',
                'groups' => []
            ];
        }
        if (!isset($grouped_data[$hid]['groups'][$gid])) {
            $grouped_data[$hid]['groups'][$gid] = [
                'academic_year' => $row['group_academic_year'],
                'semester' => $row['group_semester'],
                'endorsement_file' => $row['endorsement_file'],
                'students' => []
            ];
        }
        $grouped_data[$hid]['groups'][$gid]['students'][] = $row;
    }
}

function formatInternshipPeriod($start, $end) {
    if (empty($start) && empty($end)) return '—';
    $start_str = !empty($start) ? date('M d, Y', strtotime($start)) : '';
    $end_str = !empty($end) ? date('M d, Y', strtotime($end)) : '';
    if (!empty($start_str) && !empty($end_str)) return $start_str . ' - ' . $end_str;
    elseif (!empty($start_str)) return $start_str . ' - ';
    else return ' - ' . $end_str;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <title>SIPP OJT - Student Evaluation</title>
   <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <?php include 'navbar.php'; ?>

    <div class="summary-card">
        <p><strong>Summary:</strong> Total students: <?= $total_students ?> | Missing SIPP: <?= $missing_sipp ?></p>
        <p>Active HTEs: <?= $active_htes ?></p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="success-message"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="filters">
        <form method="GET" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; width: 100%;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                <select name="hte_id" onchange="this.form.submit()">
                    <option value="0">All HTEs</option>
                    <?php
                    if ($is_superadmin) {
                        $htes_res = mysqli_query($conn, "SELECT hte_id, hte_name FROM host_training_establishment ORDER BY hte_name");
                    } else {
                        $stmt_hte_dd = mysqli_prepare($conn, "SELECT hte_id, hte_name FROM host_training_establishment 
                                                              WHERE moa_specify = 'ASCOT (Generalized)' OR moa_specify = ? ORDER BY hte_name");
                        mysqli_stmt_bind_param($stmt_hte_dd, "s", $_SESSION['department']);
                        mysqli_stmt_execute($stmt_hte_dd);
                        $htes_res = mysqli_stmt_get_result($stmt_hte_dd);
                    }
                    while ($hte = mysqli_fetch_assoc($htes_res)) {
                        $sel = ($hte_filter == $hte['hte_id']) ? 'selected' : '';
                        echo "<option value='{$hte['hte_id']}' $sel>" . htmlspecialchars($hte['hte_name']) . "</option>";
                    }
                    ?>
                </select>

                <select name="sem" onchange="this.form.submit()">
                    <option value="">All Semesters</option>
                    <?php foreach (['1st Semester','2nd Semester','Midyear'] as $sem): ?>
                        <option value="<?= $sem ?>" <?= $semester_filter == $sem ? 'selected' : '' ?>><?= $sem ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="ay" onchange="this.form.submit()">
                    <option value="0">All Academic Years</option>
                    <?php foreach ($ay_list as $ay): ?>
                        <?php $label = $ay . '-' . ($ay+1); ?>
                        <option value="<?= $ay ?>" <?= $academic_year_filter == $ay ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="school" id="school-filter" onchange="updateProgramFilter()">
                    <option value="">All Schools</option>
                    <?php foreach ($schools as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $school_filter == $code ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="program" id="program-filter">
                    <option value="">All Programs</option>
                    <?php
                    if (!empty($school_filter) && isset($programs_by_school[$school_filter])) {
                        foreach ($programs_by_school[$school_filter] as $prog) {
                            $sel = ($program_filter == $prog) ? 'selected' : '';
                            echo "<option value='$prog' $sel>$prog</option>";
                        }
                    }
                    ?>
                </select>
                <input type="text" name="search" placeholder="Search student or HTE name..." value="<?= htmlspecialchars($search_filter) ?>" class="search-input">
                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="index.php" class="btn btn-secondary">Clear</a>
            </div>

            <div style="display: flex; gap: 0.5rem; margin-left: auto;">
                <a href="add_student.php<?= $hte_filter ? "?hte_id=$hte_filter" : '' ?>" class="btn btn-add">+ Add Student</a>
                <button type="button" class="btn btn-secondary" onclick="openExportModal()">📄 Export Report</button>
            </div>
        </form>
    </div>

    <?php if (empty($grouped_data)): ?>
        <div class="empty-message">No students found.</div>
    <?php else: ?>
        <?php if ($is_superadmin): ?>
            <?php foreach ($grouped_data as $aid => $advisor): ?>
                <?php if (empty($advisor['htes'])) continue; ?>
                <?php $school_name = isset($schools[$advisor['department']]) ? $schools[$advisor['department']] : $advisor['department']; ?>
                <div class="advisor-section">
                    <div class="advisor-header">
                        <div><h2><?= htmlspecialchars($advisor['name']) ?> (<?= htmlspecialchars($school_name) ?>)</h2></div>
                        <div class="toggle-icon">▼</div>
                    </div>
                    <div class="advisor-content">
                        <?php foreach ($advisor['htes'] as $hid => $hte): ?>
                            <div class="hte-section">
                                <div class="hte-header">
                                    <div><h3><?= htmlspecialchars($hte['name']) ?> <small>(<?= $hte['moa_status'] ?>)</small></h3></div>
                                    <div class="toggle-icon">▼</div>
                                </div>
                                <div class="hte-content">
                                    <?php foreach ($hte['groups'] as $gid => $group): ?>
                                        <?php
                                            $ay_label = $group['academic_year'] . '-' . ($group['academic_year']+1);
                                            $sem_label = $group['semester'];
                                        ?>
                                        <div class="group-section">
                                            <div class="group-header">
                                                <div>
                                                    <strong><?= htmlspecialchars($ay_label . ' | ' . $sem_label) ?></strong>
                                                    <?php if ($group['endorsement_file']): ?>
                                                        <span class="endorsement-badge endorsement-ok">✔ Letter Uploaded</span>
                                                    <?php else: ?>
                                                        <span class="endorsement-badge endorsement-pending">⏳ Pending</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="display:flex; gap:0.3rem;">
                                                    <a href="view_batch.php?batch_id=<?= $gid ?>" class="btn-view">👁 View</a>
                                                    <a href="edit_student.php?batch_id=<?= $gid ?>" class="btn-view">✏️ Edit</a>
                                                </div>
                                            </div>
                                            <div class="table-wrapper">
                                                <table class="compact-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Student Name</th><th>School</th><th>Program</th><th>Internship Period</th>
                                                            <th>RF</th><th>MC</th><th>PT</th><th>PC</th><th>TA</th><th>SIPP</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($group['students'] as $student): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($student['student_name']) ?></td>
                                                            <td><?= isset($schools[$student['school']]) ? $schools[$student['school']] : $student['school'] ?></td>
                                                            <td><?= htmlspecialchars($student['program']) ?></td>
                                                            <td class="internship-cell"><?= formatInternshipPeriod($student['internship_start'], $student['internship_end']) ?></td>
                                                            <td><?= $student['has_registration_form'] ? '✅' : '❌' ?></td>
                                                            <td><?= $student['has_medical_certificate'] ? '✅' : '❌' ?></td>
                                                            <td><?= $student['has_psycho_test'] ? '✅' : '❌' ?></td>
                                                            <td><?= $student['has_notary_p_c'] ? '✅' : '❌' ?></td>
                                                            <td><?= $student['has_notary_t_a'] ? '✅' : '❌' ?></td>
                                                            <td><?= $student['has_sipp'] ? '✅' : '❌' ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- ADVISOR VIEW -->
            <?php foreach ($grouped_data as $hid => $hte): ?>
                <div class="hte-section">
                    <div class="hte-header">
                        <div><h3><?= htmlspecialchars($hte['name']) ?> <small>(<?= $hte['moa_status'] ?>)</small></h3></div>
                        <div class="toggle-icon">▼</div>
                    </div>
                    <div class="hte-content">
                        <?php foreach ($hte['groups'] as $gid => $group): ?>
                            <?php
                                $ay_label = $group['academic_year'] . '-' . ($group['academic_year']+1);
                                $sem_label = $group['semester'];
                            ?>
                            <div class="group-section">
                                <div class="group-header">
                                    <div>
                                        <strong><?= htmlspecialchars($ay_label . ' | ' . $sem_label) ?></strong>
                                        <?php if ($group['endorsement_file']): ?>
                                            <span class="endorsement-badge endorsement-ok">✔ Letter Uploaded</span>
                                        <?php else: ?>
                                            <span class="endorsement-badge endorsement-pending">⏳ Pending</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display:flex; gap:0.3rem;">
                                        <a href="view_batch.php?batch_id=<?= $gid ?>" class="btn-view">👁 View</a>
                                        <a href="edit_student.php?batch_id=<?= $gid ?>" class="btn-view">✏️ Edit</a>
                                    </div>
                                </div>
                                <div class="table-wrapper">
                                    <table class="compact-table">
                                        <thead>
                                            <tr>
                                                <th>Student Name</th><th>School</th><th>Program</th><th>Internship Period</th>
                                                <th>RF</th><th>MC</th><th>PT</th><th>PC</th><th>TA</th><th>SIPP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($group['students'] as $student): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($student['student_name']) ?></td>
                                                <td><?= isset($schools[$student['school']]) ? $schools[$student['school']] : $student['school'] ?></td>
                                                <td><?= htmlspecialchars($student['program']) ?></td>
                                                <td class="internship-cell"><?= formatInternshipPeriod($student['internship_start'], $student['internship_end']) ?></td>
                                                <td><?= $student['has_registration_form'] ? '✔' : '✘' ?></td>
                                                <td><?= $student['has_medical_certificate'] ? '✔' : '✘' ?></td>
                                                <td><?= $student['has_psycho_test'] ? '✔' : '✘' ?></td>
                                                <td><?= $student['has_notary_p_c'] ? '✔' : '✘' ?></td>
                                                <td><?= $student['has_notary_t_a'] ? '✔' : '✘' ?></td>
                                                <td><?= $student['has_sipp'] ? '✔' : '✘' ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Export Modal -->
    <div class="modal-overlay" id="exportModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Export Report to Word</h3>
                <button class="modal-close" onclick="closeExportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group-modal">
                    <label>Semester</label>
                    <select id="exportSemester" required>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Midyear">Midyear</option>
                    </select>
                </div>
                <div class="form-group-modal">
                    <label>Academic Year</label>
                    <select id="exportSchoolYear" required>
                        <?php
                        $current_year = (int)date('Y');
                        for ($i = -5; $i <= 5; $i++):
                            $start = $current_year + $i;
                            $end = $start + 1;
                            $year_range = $start . '-' . $end;
                            ?>
                            <option value="<?= $start ?>"><?= $year_range ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php if ($is_superadmin): ?>
                <div class="form-group-modal">
                    <label>School (optional)</label>
                    <select id="exportSchool">
                        <option value="">All Schools</option>
                        <?php foreach ($schools as $code => $name): ?>
                            <option value="<?= $code ?>"><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeExportModal()">Cancel</button>
                <button type="button" class="btn" onclick="generateExport()">Generate</button>
            </div>
        </div>
    </div>

    <script>
        const programsBySchool = <?= json_encode($programs_by_school, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const isSuperadmin = <?= json_encode($is_superadmin) ?>;

        function updateProgramFilter() {
            const schoolSelect = document.getElementById('school-filter');
            const programSelect = document.getElementById('program-filter');
            const schoolCode = schoolSelect.value;
            programSelect.innerHTML = '<option value="">All Programs</option>';
            if (schoolCode && programsBySchool[schoolCode]) {
                programsBySchool[schoolCode].forEach(program => {
                    const option = document.createElement('option');
                    option.value = program;
                    option.text = program;
                    programSelect.appendChild(option);
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.advisor-header').forEach(header => {
                const content = header.nextElementSibling;
                const icon = header.querySelector('.toggle-icon');
                content.classList.add('expanded');
                icon.textContent = '▲';
                header.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (content.classList.contains('expanded')) { content.classList.remove('expanded'); icon.textContent = '▼'; }
                    else { content.classList.add('expanded'); icon.textContent = '▲'; }
                });
            });

            document.querySelectorAll('.hte-header').forEach(header => {
                const content = header.nextElementSibling;
                const icon = header.querySelector('.toggle-icon');
                content.classList.add('expanded');
                icon.textContent = '▲';
                header.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (content.classList.contains('expanded')) { content.classList.remove('expanded'); icon.textContent = '▼'; }
                    else { content.classList.add('expanded'); icon.textContent = '▲'; }
                });
            });
        });

        function openExportModal() { document.getElementById('exportModal').classList.add('active'); }
        function closeExportModal() { document.getElementById('exportModal').classList.remove('active'); }
        function generateExport() {
            const sem = document.getElementById('exportSemester').value;
            const sy = document.getElementById('exportSchoolYear').value;
            let url = 'export_report_docx.php?semester=' + encodeURIComponent(sem) + '&academic_year=' + encodeURIComponent(sy);
            if (isSuperadmin) {
                const school = document.getElementById('exportSchool').value;
                if (school) url += '&school=' + encodeURIComponent(school);
            }
            window.location.href = url;
            closeExportModal();
        }
        document.getElementById('exportModal').addEventListener('click', function(e) { if (e.target === this) closeExportModal(); });
    </script>
</div>
<?php include 'superadmin_modal.php'; ?>
</body>
</html>