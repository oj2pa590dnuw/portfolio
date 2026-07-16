<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['advisor_id'])) {
    header("Location: login.php");
    exit;
}

$is_superadmin = ($_SESSION['role'] == 'superadmin');
$advisor_id = $_SESSION['advisor_id'];
$advisor_department = $_SESSION['department'];

// Force exact semester and academic year – no “all” option
$semester_filter = isset($_GET['sem']) ? $_GET['sem'] : '2nd Semester';
$academic_year_filter = isset($_GET['ay']) ? (int)$_GET['ay'] : (int)date('Y');
$school_filter = isset($_GET['school']) ? $_GET['school'] : '';
$program_filter = isset($_GET['program']) ? $_GET['program'] : '';

// Valid semesters list for dropdown
$semesters = ['1st Semester', '2nd Semester', 'Midyear'];
if (!in_array($semester_filter, $semesters)) $semester_filter = '2nd Semester';

// Generate list of available academic years (from the database) for the dropdown
$ay_list = [];
$ay_sql = "SELECT DISTINCT academic_year FROM students" . ($is_superadmin ? "" : " WHERE advisor_id = ?") . " ORDER BY academic_year DESC";
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

// Build WHERE conditions for student statistics
$student_conditions = [];
$student_params = [];
$types = "";

// Semester (always filtered)
$student_conditions[] = "semester = ?";
$student_params[] = $semester_filter;
$types .= "s";

// Academic year (always filtered)
$student_conditions[] = "academic_year = ?";
$student_params[] = $academic_year_filter;
$types .= "i";

// School filter (superadmin)
if (!empty($school_filter) && $is_superadmin) {
    $student_conditions[] = "school = ?";
    $student_params[] = $school_filter;
    $types .= "s";
}

// Program filter
if (!empty($program_filter)) {
    $student_conditions[] = "program = ?";
    $student_params[] = $program_filter;
    $types .= "s";
}

// Advisor permission (non‑superadmin)
if (!$is_superadmin) {
    $student_conditions[] = "advisor_id = ?";
    $student_params[] = $advisor_id;
    $types .= "i";
}

$student_where = "WHERE " . implode(" AND ", $student_conditions);

// Helper: execute count query
function getCount($conn, $sql, $types = "", $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? (int)$row['cnt'] : 0;
}

// Total students
$sql_total = "SELECT COUNT(*) AS cnt FROM students " . $student_where;
$total_students = getCount($conn, $sql_total, $types, $student_params);

// Missing SIPP
$sql_missing = "SELECT COUNT(*) AS cnt FROM students WHERE has_sipp = 0 AND " . substr($student_where, 6);
$missing_sipp = getCount($conn, $sql_missing, $types, $student_params);

// Gender distribution
$gender_counts = [];
$sql_gender = "SELECT gender, COUNT(*) AS cnt FROM students " . $student_where . " GROUP BY gender";
$stmt = mysqli_prepare($conn, $sql_gender);
if (!empty($student_params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$student_params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $gender_counts[$row['gender']] = $row['cnt'];
}
mysqli_stmt_close($stmt);

// Program breakdown
$program_counts = [];
$sql_prog = "SELECT program, COUNT(*) AS cnt FROM students " . $student_where . " GROUP BY program ORDER BY program";
$stmt = mysqli_prepare($conn, $sql_prog);
if (!empty($student_params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$student_params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $program_counts[$row['program']] = $row['cnt'];
}
mysqli_stmt_close($stmt);

// Requirements counts
$req_counts = [];
$req_fields = ['has_registration_form','has_medical_certificate','has_psycho_test','has_notary_p_c','has_notary_t_a','has_sipp'];
foreach ($req_fields as $field) {
    $sql_req = "SELECT COUNT(*) AS cnt FROM students WHERE $field = 1 AND " . substr($student_where, 6);
    $req_counts[$field] = getCount($conn, $sql_req, $types, $student_params);
}

// ------------------------------------------------------------
// Students by HTE Type (NEW LOGIC – counts students, not HTEs)
// ------------------------------------------------------------
$hte_type_counts = [];
$sql_type = "SELECT h.hte_type, COUNT(*) AS cnt 
             FROM host_training_establishment h 
             INNER JOIN students s ON h.hte_id = s.hte_id 
             $student_where
             GROUP BY h.hte_type";
$stmt = mysqli_prepare($conn, $sql_type);
if (!empty($student_params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$student_params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $type = $row['hte_type'] ?? 'Not specified';
    $hte_type_counts[$type] = $row['cnt'];
}
mysqli_stmt_close($stmt);

// MOA status distribution (unchanged, but with academic year filter)
$today = date('Y-m-d');
$moa_status = ['Active' => 0, 'Upcoming' => 0, 'Expired' => 0, 'Inactive' => 0];
$sql_moa = "SELECT DISTINCT h.active_moa, h.start_memo_of_agreement, h.end_memo_of_agreement, h.hte_type
            FROM host_training_establishment h 
            INNER JOIN students s ON h.hte_id = s.hte_id 
            $student_where";
$stmt = mysqli_prepare($conn, $sql_moa);
if (!empty($student_params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$student_params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    if ($row['hte_type'] == 'Local') {
        $moa_status['Active']++;
    } elseif ($row['active_moa'] == 0) {
        $moa_status['Inactive']++;
    } else {
        if ($today >= $row['start_memo_of_agreement'] && $today <= $row['end_memo_of_agreement']) {
            $moa_status['Active']++;
        } elseif ($today < $row['start_memo_of_agreement']) {
            $moa_status['Upcoming']++;
        } else {
            $moa_status['Expired']++;
        }
    }
}
mysqli_stmt_close($stmt);

// Total HTEs with students (filtered)
$sql_total_htes = "SELECT COUNT(DISTINCT h.hte_id) AS cnt 
                   FROM host_training_establishment h 
                   INNER JOIN students s ON h.hte_id = s.hte_id 
                   $student_where";
$total_htes = getCount($conn, $sql_total_htes, $types, $student_params);

// Active HTEs among filtered
$active_htes = 0;
if ($total_htes > 0) {
    $active_conditions = $student_conditions;
    $active_conditions[] = "h.active_moa = 1";
    $active_conditions[] = "h.start_memo_of_agreement <= ?";
    $active_conditions[] = "h.end_memo_of_agreement >= ?";
    $active_where = "WHERE " . implode(" AND ", $active_conditions);
    $active_params = $student_params;
    $active_params[] = $today;
    $active_params[] = $today;
    $active_types = $types . "ss";

    $sql_active = "SELECT COUNT(DISTINCT h.hte_id) AS cnt 
                   FROM host_training_establishment h 
                   INNER JOIN students s ON h.hte_id = s.hte_id 
                   $active_where";
    $active_htes = getCount($conn, $sql_active, $active_types, $active_params);
}

// ---- Student Distribution by HTE (Local aggregated) ----
$hte_distribution = [];
$sql_dist = "SELECT IF(h.hte_type = 'Local', 'ASCOT (Local)', h.hte_name) AS display_name,
                    COUNT(s.student_id) AS cnt
             FROM host_training_establishment h
             INNER JOIN students s ON h.hte_id = s.hte_id
             $student_where
             GROUP BY display_name
             ORDER BY display_name";
$stmt = mysqli_prepare($conn, $sql_dist);
if (!empty($student_params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$student_params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    $hte_distribution[$row['display_name']] = $row['cnt'];
}
mysqli_stmt_close($stmt);

function progress_bar($value, $total, $label = null) {
    $percent = $total > 0 ? ($value / $total) * 100 : 0;
    $label = $label ?? "$value / $total";
    return "<div class='progress-container'><div class='progress-bar' style='width: {$percent}%;'></div><span class='progress-label'>{$label}</span></div>";
}

$display_school = $is_superadmin ? $school_filter : $advisor_department;
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <title>Statistics Dashboard | SIPP OJT Monitor</title>
   <link rel="stylesheet" href="style.css">
   <style>
      .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
      .stat-card { background: var(--bg-card); border-radius: var(--radius-md); box-shadow: var(--shadow); padding: 1rem 1.25rem; border: 1px solid var(--border); }
      .stat-card h3 { font-size: 1rem; color: var(--primary); margin-bottom: 0.75rem; border-left: 3px solid var(--accent); padding-left: 0.75rem; }
      .stat-list { list-style: none; margin: 0; padding: 0; max-height: 300px; overflow-y: auto; }
      .stat-list li { display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid var(--border); font-size: 0.85rem; }
      .stat-number { font-weight: 600; color: var(--primary); }
      .progress-container { background: #e9ecef; border-radius: 20px; overflow: hidden; margin: 0.5rem 0; position: relative; height: 1.8rem; }
      .progress-bar { background: var(--accent); height: 100%; width: 0%; transition: width 0.3s ease; border-radius: 20px; }
      .progress-label { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 0.75rem; font-weight: 500; color: #fff; text-shadow: 0 0 2px rgba(0,0,0,0.5); }
      .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 0.75rem; }
      .filters { margin-bottom: 1.5rem; }
      .filter-group { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
      .filter-group select { min-width: 140px; }
   </style>
</head>
<body>
<div class="container">
    <?php include 'navbar.php'; ?>
    
    <div class="summary-card">
        <p><strong>📊 Statistics Overview</strong> — <?= $is_superadmin ? 'Complete breakdown' : 'Your personal statistics' ?> (Sem: <?= $semester_filter ?>, AY: <?= $academic_year_filter . '-' . ($academic_year_filter+1) ?>)</p>
    </div>

    <div class="filters">
        <form method="GET" class="filter-group">
            <select name="sem" onchange="this.form.submit()">
                <?php foreach ($semesters as $sem): ?>
                    <option value="<?= $sem ?>" <?= $semester_filter == $sem ? 'selected' : '' ?>><?= $sem ?></option>
                <?php endforeach; ?>
            </select>

            <select name="ay" onchange="this.form.submit()">
                <?php foreach ($ay_list as $ay): ?>
                    <option value="<?= $ay ?>" <?= $academic_year_filter == $ay ? 'selected' : '' ?>><?= $ay . '-' . ($ay+1) ?></option>
                <?php endforeach; ?>
            </select>

            <?php if ($is_superadmin): ?>
            <select name="school" id="school-filter" onchange="updateProgramFilter()">
                <option value="">All Schools</option>
                <?php foreach ($schools as $code => $name): ?>
                    <option value="<?= $code ?>" <?= $school_filter == $code ? 'selected' : '' ?>><?= $name ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <select name="program" id="program-filter">
                <option value="">All Programs</option>
                <?php
                if (!empty($display_school) && isset($programs_by_school[$display_school])) {
                    foreach ($programs_by_school[$display_school] as $prog) {
                        $selected = ($program_filter == $prog) ? 'selected' : '';
                        echo "<option value='$prog' $selected>$prog</option>";
                    }
                }
                ?>
            </select>
            <button type="submit" class="btn">Apply Filters</button>
            <a href="stats.php" class="btn btn-secondary">Reset</a>
            <a href="export_stats_charts.php?<?= http_build_query($_GET) ?>" class="btn btn-secondary">📊 Export Charts</a>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><h3>👥 Students</h3><div class="two-columns"><div><div class="stat-number"><?= $total_students ?></div><div>Total students</div></div><div><div class="stat-number" style="color: #ae2012;"><?= $missing_sipp ?></div><div>Missing SIPP</div></div></div><?= progress_bar($total_students - $missing_sipp, $total_students, "SIPP completion: " . ($total_students - $missing_sipp) . "/$total_students") ?></div>
        <div class="stat-card"><h3>🏢 Host Training Establishments</h3><div class="two-columns"><div><div class="stat-number"><?= $total_htes ?></div><div>Total HTEs (with students)</div></div><div><div class="stat-number" style="color: #2d6a4f;"><?= $active_htes ?></div><div>Active MOA</div></div></div><?= progress_bar($active_htes, $total_htes, "Active HTEs: $active_htes/$total_htes") ?></div>
        <div class="stat-card"><h3>⚥ Gender Distribution</h3><ul class="stat-list"><?php foreach ($gender_counts as $gender => $count): ?><li><span><?= htmlspecialchars($gender) ?></span><span class="stat-number"><?= $count ?></span></li><?php endforeach; ?><?php if (empty($gender_counts)): ?><li>No data</li><?php endif; ?></ul></div>
        <?php if (!empty($hte_type_counts)): ?><div class="stat-card"><h3>🏷️ Students by HTE Type</h3><ul class="stat-list"><?php foreach ($hte_type_counts as $type => $count): ?><li><span><?= ucfirst(htmlspecialchars($type)) ?></span><span class="stat-number"><?= $count ?></span></li><?php endforeach; ?></ul></div><?php endif; ?>
        <div class="stat-card"><h3>📅 MOA Status</h3><ul class="stat-list"><?php foreach ($moa_status as $status => $count): ?><li><span><?= $status ?></span><span class="stat-number"><?= $count ?></span></li><?php endforeach; ?></ul></div>
        <div class="stat-card"><h3>📋 Requirements Fulfilled</h3><ul class="stat-list"><?php $labels = ['has_registration_form'=>'Registration Form','has_medical_certificate'=>'Medical Certificate','has_psycho_test'=>'Psycho Test','has_notary_p_c'=>'Notary P&C','has_notary_t_a'=>'Notary T&A','has_sipp'=>'SIPP']; foreach ($req_counts as $field => $count): ?><li><span><?= $labels[$field] ?></span><span class="stat-number"><?= $count ?>/<?= $total_students ?></span></li><?php endforeach; ?></ul></div>
    </div>
    <div class="stat-card" style="margin-bottom: 1.5rem;"><h3>📚 Program Breakdown</h3><?php if (!empty($program_counts)): ?><ul class="stat-list full-list"><?php foreach ($program_counts as $program => $count): ?><li><span><?= htmlspecialchars($program) ?></span><span class="stat-number"><?= $count ?></span></li><?php endforeach; ?></ul><?php else: ?><p>No program data available.</p><?php endif; ?></div>
    <div class="stat-card"><h3>🏫 Student Distribution by HTE</h3><?php if (!empty($hte_distribution)): ?><ul class="stat-list full-list"><?php foreach ($hte_distribution as $hte_name => $count): ?><li><span><?= htmlspecialchars($hte_name) ?></span><span class="stat-number"><?= $count ?></span></li><?php endforeach; ?></ul><?php else: ?><p>No HTE assignments yet.</p><?php endif; ?></div>
    <div style="margin-top: 1rem; text-align: right;"><small>Data as of <?= date('Y-m-d H:i:s') ?></small></div>
</div>

<script>
    const programsBySchool = <?= json_encode($programs_by_school, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const isSuperadmin = <?= json_encode($is_superadmin) ?>;
    const advisorSchool = <?= json_encode($advisor_department, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    
    function updateProgramFilter() {
        let schoolCode = isSuperadmin ? document.getElementById('school-filter').value : advisorSchool;
        const programSelect = document.getElementById('program-filter');
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
        updateProgramFilter();
        const urlParams = new URLSearchParams(window.location.search);
        const progParam = urlParams.get('program');
        if (progParam) {
            const programSelect = document.getElementById('program-filter');
            for (let i = 0; i < programSelect.options.length; i++) {
                if (programSelect.options[i].value === progParam) {
                    programSelect.options[i].selected = true;
                    break;
                }
            }
        }
    });
</script>
</body>
</html>