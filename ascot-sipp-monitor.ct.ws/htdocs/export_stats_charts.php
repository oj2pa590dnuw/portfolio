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

// Same filter gathering as stats.php
$semester_filter = isset($_GET['sem']) ? $_GET['sem'] : '2nd Semester';
$academic_year_filter = isset($_GET['ay']) ? (int)$_GET['ay'] : (int)date('Y');
$school_filter = isset($_GET['school']) ? $_GET['school'] : '';
$program_filter = isset($_GET['program']) ? $_GET['program'] : '';

$semesters = ['1st Semester', '2nd Semester', 'Midyear'];
if (!in_array($semester_filter, $semesters)) $semester_filter = '2nd Semester';

// Build WHERE conditions (same as stats.php)
$student_conditions = [];
$student_params = [];
$types = "";

$student_conditions[] = "semester = ?";
$student_params[] = $semester_filter;
$types .= "s";

$student_conditions[] = "academic_year = ?";
$student_params[] = $academic_year_filter;
$types .= "i";

if (!empty($school_filter) && $is_superadmin) {
    $student_conditions[] = "school = ?";
    $student_params[] = $school_filter;
    $types .= "s";
}

if (!empty($program_filter)) {
    $student_conditions[] = "program = ?";
    $student_params[] = $program_filter;
    $types .= "s";
}

if (!$is_superadmin) {
    $student_conditions[] = "advisor_id = ?";
    $student_params[] = $advisor_id;
    $types .= "i";
}

$student_where = "WHERE " . implode(" AND ", $student_conditions);

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

// Collect the data exactly as stats.php would
$total_students = getCount($conn, "SELECT COUNT(*) AS cnt FROM students $student_where", $types, $student_params);
$missing_sipp = getCount($conn, "SELECT COUNT(*) AS cnt FROM students WHERE has_sipp = 0 AND " . substr($student_where, 6), $types, $student_params);

// Gender distribution
$gender_counts = [];
$sql_gender = "SELECT gender, COUNT(*) AS cnt FROM students $student_where GROUP BY gender";
$stmt = mysqli_prepare($conn, $sql_gender);
if (!empty($student_params)) mysqli_stmt_bind_param($stmt, $types, ...$student_params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) { $gender_counts[$row['gender']] = $row['cnt']; }
mysqli_stmt_close($stmt);

// Program breakdown
$program_counts = [];
$sql_prog = "SELECT program, COUNT(*) AS cnt FROM students $student_where GROUP BY program ORDER BY program";
$stmt = mysqli_prepare($conn, $sql_prog);
if (!empty($student_params)) mysqli_stmt_bind_param($stmt, $types, ...$student_params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) { $program_counts[$row['program']] = $row['cnt']; }
mysqli_stmt_close($stmt);

// Requirements counts
$req_counts = [];
$req_fields = ['has_registration_form','has_medical_certificate','has_psycho_test','has_notary_p_c','has_notary_t_a','has_sipp'];
$req_labels = ['Registration Form','Medical Certificate','Psycho Test','Notary P&C','Notary T&A','SIPP'];
foreach ($req_fields as $idx => $field) {
    $sql_req = "SELECT COUNT(*) AS cnt FROM students WHERE $field = 1 AND " . substr($student_where, 6);
    $req_counts[$req_labels[$idx]] = getCount($conn, $sql_req, $types, $student_params);
}

// Students by HTE Type
$hte_type_counts = [];
$sql_type = "SELECT h.hte_type, COUNT(*) AS cnt 
             FROM host_training_establishment h 
             INNER JOIN students s ON h.hte_id = s.hte_id 
             $student_where
             GROUP BY h.hte_type";
$stmt = mysqli_prepare($conn, $sql_type);
if (!empty($student_params)) mysqli_stmt_bind_param($stmt, $types, ...$student_params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) { $hte_type_counts[$row['hte_type'] ?? 'Unknown'] = $row['cnt']; }
mysqli_stmt_close($stmt);

// MOA status
$today = date('Y-m-d');
$moa_status = ['Active' => 0, 'Upcoming' => 0, 'Expired' => 0, 'Inactive' => 0];
$sql_moa = "SELECT DISTINCT h.active_moa, h.start_memo_of_agreement, h.end_memo_of_agreement, h.hte_type
            FROM host_training_establishment h 
            INNER JOIN students s ON h.hte_id = s.hte_id 
            $student_where";
$stmt = mysqli_prepare($conn, $sql_moa);
if (!empty($student_params)) mysqli_stmt_bind_param($stmt, $types, ...$student_params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    if ($row['hte_type'] == 'Local') $moa_status['Active']++;
    elseif ($row['active_moa'] == 0) $moa_status['Inactive']++;
    else {
        if ($today >= $row['start_memo_of_agreement'] && $today <= $row['end_memo_of_agreement']) $moa_status['Active']++;
        elseif ($today < $row['start_memo_of_agreement']) $moa_status['Upcoming']++;
        else $moa_status['Expired']++;
    }
}
mysqli_stmt_close($stmt);

// Summary card data
$total_htes = getCount($conn, "SELECT COUNT(DISTINCT h.hte_id) AS cnt FROM host_training_establishment h INNER JOIN students s ON h.hte_id = s.hte_id $student_where", $types, $student_params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statistics Chart Report</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .charts-container { display: flex; flex-wrap: wrap; gap: 30px; justify-content: center; }
        .chart-card { width: 500px; max-width: 100%; background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .chart-card h3 { margin-top: 0; font-size: 1rem; color: #1b4332; border-left: 3px solid #52b788; padding-left: 10px; }
        canvas { max-height: 300px; }
        .print-btn { display: block; margin: 20px auto; padding: 10px 20px; font-size: 16px; background: #1b4332; color: white; border: none; border-radius: 6px; cursor: pointer; }
        .print-btn:hover { background: #0f2c20; }
        @media print {
            .print-btn { display: none; }
            .chart-card { break-inside: avoid; box-shadow: none; border: 1px solid #ccc; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
    <h2 style="text-align:center; margin-bottom:20px;">Statistics Report – <?= htmlspecialchars($semester_filter) ?>, AY <?= $academic_year_filter . '-' . ($academic_year_filter+1) ?></h2>

    <div class="charts-container">
        <div class="chart-card">
            <h3>Students by HTE Type</h3>
            <canvas id="hteTypeChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>Gender Distribution</h3>
            <canvas id="genderChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>Program Breakdown</h3>
            <canvas id="programChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>Requirements Fulfilled</h3>
            <canvas id="reqChart"></canvas>
        </div>
        <div class="chart-card">
            <h3>MOA Status</h3>
            <canvas id="moaChart"></canvas>
        </div>
    </div>

    <script>
        Chart.defaults.font.size = 11;

        // Students by HTE Type
        new Chart(document.getElementById('hteTypeChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($hte_type_counts)) ?>,
                datasets: [{
                    label: 'Students',
                    data: <?= json_encode(array_values($hte_type_counts)) ?>,
                    backgroundColor: '#52b788'
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // Gender Distribution
        new Chart(document.getElementById('genderChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($gender_counts)) ?>,
                datasets: [{
                    label: 'Students',
                    data: <?= json_encode(array_values($gender_counts)) ?>,
                    backgroundColor: ['#1b4332','#52b788']
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // Program Breakdown
        new Chart(document.getElementById('programChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($program_counts)) ?>,
                datasets: [{
                    label: 'Students',
                    data: <?= json_encode(array_values($program_counts)) ?>,
                    backgroundColor: '#2d6a4f'
                }]
            },
            options: { responsive: true, indexAxis: 'x', plugins: { legend: { display: false } } }
        });

        // Requirements Fulfilled
        new Chart(document.getElementById('reqChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($req_counts)) ?>,
                datasets: [{
                    label: 'Completed',
                    data: <?= json_encode(array_values($req_counts)) ?>,
                    backgroundColor: '#409b6e'
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        // MOA Status
        new Chart(document.getElementById('moaChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($moa_status)) ?>,
                datasets: [{
                    label: 'HTEs',
                    data: <?= json_encode(array_values($moa_status)) ?>,
                    backgroundColor: ['#2d6a4f','#e9c46a','#ae2012','#6c757d']
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    </script>
</body>
</html>