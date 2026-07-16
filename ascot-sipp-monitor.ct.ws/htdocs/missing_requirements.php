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

// ── Filter values ─────────────────────────────────
$semester_filter   = isset($_GET['sem'])    ? $_GET['sem']            : '';
$academic_year_filter = isset($_GET['ay']) ? (int)$_GET['ay']        : 0;
$school_filter     = isset($_GET['school']) ? $_GET['school']         : '';
$program_filter    = isset($_GET['program']) ? $_GET['program']       : '';
$search_filter     = isset($_GET['search'])  ? trim($_GET['search'])  : '';

// ── Build WHERE conditions ────────────────────────
$conditions = [];
$params = [];
$types = "";

if (!empty($semester_filter)) {
    $conditions[] = "s.semester = ?";
    $params[] = $semester_filter;
    $types .= "s";
}
if ($academic_year_filter > 0) {
    $conditions[] = "s.academic_year = ?";
    $params[] = $academic_year_filter;
    $types .= "i";
}
if (!empty($school_filter) && $is_superadmin) {
    $conditions[] = "s.school = ?";
    $params[] = $school_filter;
    $types .= "s";
}
if (!empty($program_filter)) {
    $conditions[] = "s.program = ?";
    $params[] = $program_filter;
    $types .= "s";
}
if (!empty($search_filter)) {
    $conditions[] = "s.student_name LIKE ?";
    $params[] = "%$search_filter%";
    $types .= "s";
}
if (!$is_superadmin) {
    $conditions[] = "s.advisor_id = ?";
    $params[] = $advisor_id;
    $types .= "i";
}

$where_clause = empty($conditions) ? "" : "WHERE " . implode(" AND ", $conditions);

// ── Fetch all students for the checklist ──────────
if ($is_superadmin) {
    $sql = "SELECT s.*, u.advisor_name, u.department AS advisor_department,
                   h.hte_name, h.hte_type, h.verified, h.moa_file,
                   h.active_moa, h.start_memo_of_agreement, h.end_memo_of_agreement
            FROM students s
            LEFT JOIN users u ON s.advisor_id = u.advisor_id
            LEFT JOIN host_training_establishment h ON s.hte_id = h.hte_id
            $where_clause
            ORDER BY u.advisor_name, h.hte_name, s.student_name";
} else {
    $sql = "SELECT s.*, h.hte_name, h.hte_type, h.verified, h.moa_file,
                   h.active_moa, h.start_memo_of_agreement, h.end_memo_of_agreement
            FROM students s
            LEFT JOIN host_training_establishment h ON s.hte_id = h.hte_id
            $where_clause
            ORDER BY h.hte_name, s.student_name";
}
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// ── Group data ────────────────────────────────────
$today = date('Y-m-d');
$hte_data = [];
$advisors = [];
$total_missing = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $hid = $row['hte_id'];
    if (!isset($hte_data[$hid])) {
        $hte_data[$hid] = [
            'name'         => $row['hte_name'] ?? 'Unassigned',
            'type'         => $row['hte_type'],
            'verified'     => $row['verified'],
            'moa_file'     => $row['moa_file'],
            'active_moa'   => $row['active_moa'],
            'start_date'   => $row['start_memo_of_agreement'],
            'end_date'     => $row['end_memo_of_agreement'],
            'students'     => [],
            'advisor_name' => $is_superadmin ? $row['advisor_name'] : '',
            'advisor_dept' => $is_superadmin ? $row['advisor_department'] : ''
        ];
        if ($is_superadmin) {
            $aid = $row['advisor_id'];
            if (!isset($advisors[$aid])) {
                $advisors[$aid] = [
                    'name'       => $row['advisor_name'],
                    'department' => $row['advisor_department'],
                    'htes'       => []
                ];
            }
            $advisors[$aid]['htes'][$hid] = true;
        }
    }
    $hte_data[$hid]['students'][] = $row;

    $missing_fields = ['has_registration_form','has_medical_certificate','has_psycho_test',
                       'has_notary_p_c','has_notary_t_a','has_sipp'];
    foreach ($missing_fields as $field) {
        if ($row[$field] == 0) {
            $total_missing++;
            break;
        }
    }
}
mysqli_stmt_close($stmt);

// ── Compute checklist & MOA summary ──────────────
$htes_missing_moa = [];

foreach ($hte_data as $hid => &$hte) {
    $missing_rf = []; $missing_mc = []; $missing_pt = [];
    $missing_pc = []; $missing_ta = []; $missing_sipp = [];

    $all_rf = true; $all_mc = true; $all_pt = true;
    $all_pc = true; $all_ta = true; $all_sipp = true;

    foreach ($hte['students'] as $s) {
        $name = $s['student_name'];
        $note = trim($s['extra_notes'] ?? '');
        $display = $name . ($note ? " ($note)" : '');

        if ($s['has_registration_form'] == 0) { $all_rf = false; $missing_rf[] = $display; }
        if ($s['has_medical_certificate'] == 0) { $all_mc = false; $missing_mc[] = $display; }
        if ($s['has_psycho_test'] == 0) { $all_pt = false; $missing_pt[] = $display; }
        if ($s['has_notary_p_c'] == 0) { $all_pc = false; $missing_pc[] = $display; }
        if ($s['has_notary_t_a'] == 0) { $all_ta = false; $missing_ta[] = $display; }
        if ($s['has_sipp'] == 0) { $all_sipp = false; $missing_sipp[] = $display; }
    }

    $hte['checklist'] = [
        'moa_active' => ($hte['verified'] == 1),
        'rf'         => $all_rf,
        'mc'         => $all_mc,
        'pt'         => $all_pt,
        'pc'         => $all_pc,
        'ta'         => $all_ta,
        'sipp'       => $all_sipp
    ];

    $hte['missing_lists'] = [
        'rf'   => $missing_rf,
        'mc'   => $missing_mc,
        'pt'   => $missing_pt,
        'pc'   => $missing_pc,
        'ta'   => $missing_ta,
        'sipp' => $missing_sipp
    ];

    if ($hte['type'] !== 'Local') {
        $moa_ok = $hte['checklist']['moa_active'];
        if (!$moa_ok) {
            $advisor_label = '';
            if ($is_superadmin) {
                $advisor_label = $hte['advisor_name'] . ' (' . ($schools[$hte['advisor_dept']] ?? $hte['advisor_dept']) . ')';
            }
            $htes_missing_moa[] = [
                'hte_id'   => $hid,
                'hte_name' => $hte['name'],
                'verified' => $hte['verified'],
                'moa_file' => $hte['moa_file'],
                'active'   => $hte['active_moa'],
                'start'    => $hte['start_date'],
                'end'      => $hte['end_date'],
                'advisor'  => $advisor_label
            ];
        }
    }
}
unset($hte);

// ── Filter dropdowns data ─────────────────────────
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <title>Missing Requirements | SIPP OJT Monitor</title>
   <link rel="stylesheet" href="style.css">
   <style>
       .checklist-grid {
           display: grid;
           grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
           gap: 1rem;
           margin-bottom: 1.5rem;
       }
       .checklist-card {
           border: 1px solid var(--border);
           border-radius: var(--radius-md);
           background: var(--bg-card);
           box-shadow: var(--shadow);
           overflow: hidden;
       }
       .card-header {
           background: #f8faf7;
           padding: 0.6rem 0.9rem;
           border-bottom: 1px solid var(--border);
           font-weight: 700;
           color: var(--primary);
           display: flex;
           justify-content: space-between;
           align-items: center;
       }
       .card-body { padding: 0.7rem 0.9rem; }
       .checklist-item {
           display: flex;
           align-items: center;
           gap: 0.5rem;
           padding: 0.25rem 0;
           font-size: 0.78rem;
           border-bottom: 1px dashed var(--border);
       }
       .checklist-item:last-child { border-bottom: none; }
       .check-icon {
           font-weight: bold;
           width: 1.2rem;
           text-align: center;
           flex-shrink: 0;
       }
       .check-ok { color: #2d6a4f; }
       .check-not { color: var(--danger); }

       .missing-documents {
           border-top: 1px dashed var(--border);
           margin-top: 0.5rem;
           padding: 0.5rem 0.9rem 0.3rem;
           font-size: 0.78rem;
       }
       .missing-documents div {
           margin-bottom: 0.3rem;
           line-height: 1.3;
       }

       .advisor-section { margin-bottom: 1.5rem; }
       .advisor-header {
           background: #e8f0fe;
           padding: 0.75rem 1rem;
           cursor: pointer;
           display: flex;
           justify-content: space-between;
           align-items: center;
           font-weight: bold;
           border-radius: var(--radius-sm);
           margin-bottom: 0.5rem;
       }
       .advisor-header:hover { background: #d9e4fa; }
       .advisor-content { display: none; }
       .advisor-content.expanded { display: block; }
   </style>
</head>
<body>
<div class="container">
    <?php include 'navbar.php'; ?>

    <div class="summary-card">
        <p><strong>📋 Missing Requirements Report</strong></p>
        <p>Total students missing at least one requirement: <?= $total_missing ?></p>
        <div style="margin-top: 0.5rem;">
            <button class="btn expand-all">Expand All Advisors</button>
            <button class="btn btn-secondary collapse-all">Collapse All Advisors</button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filters">
        <form method="GET" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem; width: 100%;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                <select name="sem">
                    <option value="">All Semesters</option>
                    <?php foreach (['1st Semester','2nd Semester','Midyear'] as $sem): ?>
                        <option value="<?= $sem ?>" <?= $semester_filter == $sem ? 'selected' : '' ?>><?= $sem ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="ay">
                    <option value="0">All Academic Years</option>
                    <?php foreach ($ay_list as $ay): ?>
                        <?php $label = $ay . '-' . ($ay+1); ?>
                        <option value="<?= $ay ?>" <?= $academic_year_filter == $ay ? 'selected' : '' ?>><?= $label ?></option>
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
                    $display_school = $is_superadmin ? $school_filter : $advisor_department;
                    if (!empty($display_school) && isset($programs_by_school[$display_school])) {
                        foreach ($programs_by_school[$display_school] as $prog) {
                            $sel = ($program_filter == $prog) ? 'selected' : '';
                            echo "<option value='$prog' $sel>$prog</option>";
                        }
                    }
                    ?>
                </select>

                <input type="text" name="search" placeholder="Search student name..." value="<?= htmlspecialchars($search_filter) ?>" class="search-input">
                <button type="submit" class="btn btn-secondary">Filter</button>
                <a href="missing_requirements.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <!-- MOA warning section -->
    <?php if (!empty($htes_missing_moa)): ?>
    <div class="moa-summary-card">
        <div class="moa-summary-header" onclick="toggleMOASection(this)">
            <span>⚠️ HTEs with Incomplete MOA Documentation (<?= count($htes_missing_moa) ?>)</span>
            <span class="toggle-icon">▲</span>
        </div>
        <div class="moa-list" id="moaSection">
            <?php foreach ($htes_missing_moa as $moa): ?>
            <div class="moa-item">
                <div>
                    <strong><?= htmlspecialchars($moa['hte_name']) ?></strong>
                    <?php if ($is_superadmin && !empty($moa['advisor'])): ?>
                        <br><small style="color: var(--text-light);">Advisor: <?= htmlspecialchars($moa['advisor']) ?></small>
                    <?php endif; ?>
                    <div style="margin-top: 0.25rem;">
                        <?php if (!$moa['verified']): ?><span class="badge-warning">Unverified</span><?php endif; ?>
                        <?php if (empty($moa['moa_file'])): ?><span class="badge-warning">No File</span><?php endif; ?>
                        <?php if (!$moa['active']): ?><span class="badge-warning">Inactive</span><?php endif; ?>
                        <?php if ($moa['active'] && ($today < $moa['start'] || $today > $moa['end'])): ?>
                            <span class="badge-warning">Expired/Not Started</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($is_superadmin): ?>
                <a href="edit_hte.php?id=<?= $moa['hte_id'] ?>" class="btn btn-edit btn-sm">Edit HTE</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($hte_data)): ?>
        <div class="card">✅ No students found.</div>
    <?php else: ?>
        <?php if ($is_superadmin): ?>
            <?php foreach ($advisors as $aid => $advisor): ?>
                <?php $school_name = isset($schools[$advisor['department']]) ? $schools[$advisor['department']] : $advisor['department']; ?>
                <div class="advisor-section">
                    <div class="advisor-header">
                        <div><h2><?= htmlspecialchars($advisor['name']) ?> (<?= htmlspecialchars($school_name) ?>)</h2></div>
                        <div class="toggle-icon">▼</div>
                    </div>
                    <div class="advisor-content expanded">
                        <div class="checklist-grid">
                            <?php foreach ($advisor['htes'] as $hid => $dummy): ?>
                                <?php renderChecklistCard($hid, $hte_data[$hid]); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="checklist-grid">
                <?php foreach ($hte_data as $hid => $hte): ?>
                    <?php renderChecklistCard($hid, $hte); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    function toggleMOASection(header) {
        const content = header.nextElementSibling;
        const icon = header.querySelector('.toggle-icon');
        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.textContent = '▲';
        } else {
            content.style.display = 'none';
            icon.textContent = '▼';
        }
    }

    const programsBySchool = <?= json_encode($programs_by_school, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const isSuperadmin = <?= json_encode($is_superadmin) ?>;
    const advisorSchool = <?= json_encode($advisor_department, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function updateProgramFilter() {
        let schoolCode = isSuperadmin ? document.getElementById('school-filter')?.value : advisorSchool;
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

        document.querySelectorAll('.advisor-header').forEach(header => {
            const content = header.nextElementSibling;
            const icon = header.querySelector('.toggle-icon');
            header.addEventListener('click', function(e) {
                if (content.classList.contains('expanded')) {
                    content.classList.remove('expanded');
                    icon.textContent = '▼';
                } else {
                    content.classList.add('expanded');
                    icon.textContent = '▲';
                }
            });
        });

        document.querySelector('.expand-all')?.addEventListener('click', () => {
            document.querySelectorAll('.advisor-content').forEach(c => {
                c.classList.add('expanded');
                c.previousElementSibling.querySelector('.toggle-icon').textContent = '▲';
            });
        });
        document.querySelector('.collapse-all')?.addEventListener('click', () => {
            document.querySelectorAll('.advisor-content').forEach(c => {
                c.classList.remove('expanded');
                c.previousElementSibling.querySelector('.toggle-icon').textContent = '▼';
            });
        });

        const moaSection = document.getElementById('moaSection');
        if (moaSection) moaSection.style.display = 'block';
    });
</script>
<?php if ($is_superadmin) include 'superadmin_modal.php'; ?>
</body>
</html>

<?php
function renderChecklistCard($hte_id, $hte) {
    $c = $hte['checklist'];
    $miss = $hte['missing_lists'];

    // Build semicolon‑separated strings with notes inline
    $labels = [
        'rf'   => 'Registration Form (RF)',
        'mc'   => 'Medical Certificate (MC)',
        'pt'   => 'Psychological Test (PT)',
        'pc'   => 'Notarized Parent Consent (PC)',
        'ta'   => 'Notarized Training Agreement (TA)',
        'sipp' => 'Signed SIPP Plan (SIPP)'
    ];

    $missing_lines = [];
    foreach ($labels as $key => $label) {
        if (!empty($miss[$key])) {
            $names = implode('; ', $miss[$key]);
            $missing_lines[] = "<strong>{$label}:</strong> {$names}";
        }
    }
    ?>
    <div class="checklist-card">
        <div class="card-header">
            <span><?= htmlspecialchars($hte['name']) ?></span>
            <span style="font-size:0.7rem; color:var(--text-light);"><?= count($hte['students']) ?> student(s)</span>
        </div>
        <div class="card-body">
            <div class="checklist-item">
                <span class="check-icon <?= $c['moa_active'] ? 'check-ok' : 'check-not' ?>"><?= $c['moa_active'] ? '✔' : '✘' ?></span>
                <span>Effective Notarized MOA with HTE</span>
            </div>
            <div class="checklist-item">
                <span class="check-icon <?= $c['rf'] ? 'check-ok' : 'check-not' ?>"><?= $c['rf'] ? '✔' : '✘' ?></span>
                <span>Registration Form (RF)</span>
            </div>
            <div class="checklist-item">
                <span class="check-icon <?= ($c['mc'] && $c['pt']) ? 'check-ok' : 'check-not' ?>">
                    <?= ($c['mc'] && $c['pt']) ? '✔' : '✘' ?>
                </span>
                <span>Medical Certificate (MC) &amp; Psychological Test (PT)</span>
            </div>
            <div class="checklist-item">
                <span class="check-icon <?= $c['ta'] ? 'check-ok' : 'check-not' ?>"><?= $c['ta'] ? '✔' : '✘' ?></span>
                <span>Notarized Training Agreement (TA)</span>
            </div>
            <div class="checklist-item">
                <span class="check-icon <?= $c['pc'] ? 'check-ok' : 'check-not' ?>"><?= $c['pc'] ? '✔' : '✘' ?></span>
                <span>Notarized Parent Consent (PC)</span>
            </div>
            <div class="checklist-item">
                <span class="check-icon <?= $c['sipp'] ? 'check-ok' : 'check-not' ?>"><?= $c['sipp'] ? '✔' : '✘' ?></span>
                <span>Signed SIPP Plan (SIPP)</span>
            </div>
        </div>

        <?php if (!empty($missing_lines)): ?>
        <div class="missing-documents">
            <?php foreach ($missing_lines as $line): ?>
                <div><?= $line ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
?>