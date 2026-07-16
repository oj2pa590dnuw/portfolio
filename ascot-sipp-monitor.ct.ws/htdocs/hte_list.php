<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['advisor_id'])) {
    header("Location: login.php");
    exit;
}

$is_superadmin = ($_SESSION['role'] == 'superadmin');
$advisor_department = $_SESSION['department'];

// Get filter values
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';
$school_filter = isset($_GET['school']) ? trim($_GET['school']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query conditions
$conditions = [];
$params = [];
$types = "";

// ---- Permission filter for non‑superadmin ----
if (!$is_superadmin) {
    $conditions[] = "(moa_specify = 'ASCOT (Generalized)' OR moa_specify = ?)";
    $params[] = $advisor_department;
    $types .= "s";
}

// ---- User‑applied filters ----
if (!empty($type_filter)) {
    $conditions[] = "hte_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}
if (!empty($school_filter)) {
    $conditions[] = "moa_specify = ?";
    $params[] = $school_filter;
    $types .= "s";
}
if (!empty($search)) {
    $conditions[] = "(hte_name LIKE ? OR hte_representative LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$where_clause = empty($conditions) ? "" : "WHERE " . implode(" AND ", $conditions);
$sql = "SELECT * FROM host_training_establishment $where_clause ORDER BY hte_name";
$stmt = mysqli_prepare($conn, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// ---- Categorise HTEs into three groups ----
$today = date('Y-m-d');
$expired_htes = [];
$active_htes = [];
$local_htes = [];

while ($hte = mysqli_fetch_assoc($result)) {
    if ($hte['hte_type'] == 'Local') {
        $local_htes[] = $hte;
    } else {
        $is_expired = false;
        if ($hte['active_moa'] == 1) {
            if ($today > $hte['end_memo_of_agreement']) {
                $is_expired = true;
            }
        } else {
            $is_expired = true; // inactive
        }
        if ($is_expired) {
            $expired_htes[] = $hte;
        } else {
            $active_htes[] = $hte;
        }
    }
}

// Get distinct HTE types and schools for filter dropdowns
$type_options = ['Local', 'Private', 'Public'];
$school_options = [];
if ($is_superadmin) {
    $school_res = mysqli_query($conn, "SELECT DISTINCT moa_specify FROM host_training_establishment ORDER BY moa_specify");
} else {
    $stmt_school = mysqli_prepare($conn, "SELECT DISTINCT moa_specify FROM host_training_establishment 
                                          WHERE moa_specify = 'ASCOT (Generalized)' OR moa_specify = ? ORDER BY moa_specify");
    mysqli_stmt_bind_param($stmt_school, "s", $advisor_department);
    mysqli_stmt_execute($stmt_school);
    $school_res = mysqli_stmt_get_result($stmt_school);
}
while ($row = mysqli_fetch_assoc($school_res)) {
    if (!empty($row['moa_specify'])) {
        $school_options[] = $row['moa_specify'];
    }
}
if (!$is_superadmin && isset($stmt_school)) {
    mysqli_stmt_close($stmt_school);
}

// Helper function to render a section
function renderSection($title, $htes, $schools, $is_superadmin, $today, $collapsed = false) {
    if (empty($htes)) return;
    $display = $collapsed ? 'none' : 'block';
    $icon = $collapsed ? '▼' : '▲';
    ?>
    <div class="hte-section" style="margin-bottom:1.5rem;">
        <div class="hte-header" onclick="toggleSection(this)" style="cursor:pointer;">
            <div><h3><?= htmlspecialchars($title) ?> (<?= count($htes) ?>)</h3></div>
            <div class="toggle-icon"><?= $icon ?></div>
        </div>
        <div class="hte-content" style="display:<?= $display ?>;">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>HTE Name</th>
                            <th>Representative</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th>MOA Start</th>
                            <th>MOA End</th>
                            <th>MOA Specific School</th>
                            <th>MOA File</th>
                            <th>HTE Type</th>
                            <th>MOA Status</th>
                            <?php if ($is_superadmin): ?>
                                <th>Verified</th>
                                <th>Actions</th>
                            <?php else: ?>
                                <th>Status</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($htes as $hte):
                            if ($hte['hte_type'] == 'Local') {
                                $moa_status = '♾️';
                            } elseif ($hte['active_moa'] == 1) {
                                if ($today >= $hte['start_memo_of_agreement'] && $today <= $hte['end_memo_of_agreement']) {
                                    $moa_status = '✅';
                                } elseif ($today < $hte['start_memo_of_agreement']) {
                                    $moa_status = 'Upcoming';
                                } else {
                                    $moa_status = '❌';
                                }
                            } else {
                                $moa_status = '❌';
                            }

                            $school_display = $hte['moa_specify'];
                            if (isset($schools[$hte['moa_specify']])) {
                                $school_display = $schools[$hte['moa_specify']];
                            }

                            $hte_type_display = !empty($hte['hte_type']) ? $hte['hte_type'] : 'Not specified';
                            $start_date_display = ($hte['hte_type'] == 'Local') ? '—' : $hte['start_memo_of_agreement'];
                            $end_date_display   = ($hte['hte_type'] == 'Local') ? '—' : $hte['end_memo_of_agreement'];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($hte['hte_name']) ?></td>
                            <td><?= htmlspecialchars($hte['hte_representative']) ?></td>
                            <td><?= htmlspecialchars($hte['address']) ?></td>
                            <td><?= htmlspecialchars($hte['contact_number']) ?></td>
                            <td><?= $start_date_display ?></td>
                            <td><?= $end_date_display ?></td>
                            <td><?= htmlspecialchars($school_display) ?></td>
                            <td>
                                <?php if (!empty($hte['moa_file'])): ?>
                                    <a href="uploads/moa/<?= urlencode($hte['moa_file']) ?>" target="_blank" class="btn btn-sm btn-secondary">View</a>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($hte_type_display) ?></td>
                            <td><?= $moa_status ?></td>
                            <?php if ($is_superadmin): ?>
                                <td><?= $hte['verified'] ? '✅' : '❌' ?></td>
                                <td>
                                    <a href="edit_hte.php?id=<?= $hte['hte_id'] ?>" class="btn btn-edit btn-sm">Edit</a>
                                    <form method="POST" action="delete_hte.php" class="superadmin-action" data-confirm="Delete HTE? Students will be unassigned." style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="hte_id" value="<?= $hte['hte_id'] ?>">
                                        <button type="submit" class="btn btn-delete btn-sm">Delete</button>
                                    </form>
                                </td>
                            <?php else: ?>
                                <td>
                                    <?php if ($hte['verified']): ?>
                                        ✅ Verified
                                    <?php else: ?>
                                        ⏳ Pending Verification
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <title>Manage HTEs</title>
   <link rel="stylesheet" href="style.css">
   <style>
       .hte-section {
           margin-bottom: 1rem;
           border: 1px solid var(--border);
           border-radius: var(--radius-md);
           overflow: hidden;
       }
       .hte-header {
           background: #f8faf7;
           padding: 0.75rem 1rem;
           cursor: pointer;
           display: flex;
           justify-content: space-between;
           align-items: center;
       }
       .hte-header:hover { background: #eef2e9; }
       .hte-header h3 { margin: 0; font-size: 1rem; color: var(--primary); }
       .toggle-icon { font-size: 1.2rem; font-weight: bold; }
       .hte-content {
           border-top: 1px solid var(--border);
       }
   </style>
</head>
<body>
   <div class="container">
      <?php include 'navbar.php'; ?>

      <div class="card" style="margin-bottom: 1rem;">
         <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h2>Host Training Establishments</h2>
            <div style="display: flex; gap: 0.5rem;">
                <a href="add_hte.php" class="btn btn-add">+ Add New HTE</a>
               <?php if ($is_superadmin): ?>
                  <a href="export_hte_report_docx.php" class="btn btn-secondary">📄 Export Report</a>
               <?php endif; ?>
               <a href="index.php" class="btn btn-secondary">← Back to Students</a>
            </div>
         </div>

         <!-- Filter & Search Bar -->
         <form method="GET" class="filter-group" style="margin-top: 1rem;">
            <select name="type">
               <option value="">All Types</option>
               <?php foreach ($type_options as $type): ?>
                  <option value="<?= $type ?>" <?= $type_filter == $type ? 'selected' : '' ?>><?= $type ?></option>
               <?php endforeach; ?>
            </select>
            <select name="school">
               <option value="">All Schools</option>
               <?php foreach ($school_options as $code): ?>
                  <?php $school_name = isset($schools[$code]) ? $schools[$code] : $code; ?>
                  <option value="<?= $code ?>" <?= $school_filter == $code ? 'selected' : '' ?>><?= htmlspecialchars($school_name) ?></option>
               <?php endforeach; ?>
            </select>
            <input type="text" name="search" placeholder="Search name or representative..." value="<?= htmlspecialchars($search) ?>" style="min-width: 200px;">
            <button type="submit" class="btn">Apply Filters</button>
            <a href="hte_list.php" class="btn btn-secondary">Clear</a>
         </form>
      </div>

      <!-- Grouped sections -->
      <?php
      renderSection('Expired / No Date', $expired_htes, $schools, $is_superadmin, $today, false);
      renderSection('Active', $active_htes, $schools, $is_superadmin, $today, false);
      renderSection('Local', $local_htes, $schools, $is_superadmin, $today, false);
      ?>
   </div>

   <script>
       function toggleSection(header) {
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
   </script>
   <?php include 'superadmin_modal.php'; ?>
</body>
</html>