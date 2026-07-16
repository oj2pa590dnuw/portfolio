<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['advisor_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    die("Invalid CSRF token.");
}

$is_superadmin = ($_SESSION['role'] == 'superadmin');
$current_advisor_id = $_SESSION['advisor_id'];
$current_advisor_department = $_SESSION['department'];

// ========== BATCH EDIT (Existing Students) ==========
if (isset($_POST['batch_edit']) && $_POST['batch_edit'] == '1') {
    $hte_id = isset($_POST['hte_id']) ? (int)$_POST['hte_id'] : 0;
    if ($hte_id <= 0) die("Error: Invalid HTE selected.");

    // Permission and validation unchanged...
    $hte_stmt = mysqli_prepare($conn, "SELECT moa_specify FROM host_training_establishment WHERE hte_id = ?");
    mysqli_stmt_bind_param($hte_stmt, "i", $hte_id);
    mysqli_stmt_execute($hte_stmt);
    $hte_result = mysqli_stmt_get_result($hte_stmt);
    $hte = mysqli_fetch_assoc($hte_result);
    mysqli_stmt_close($hte_stmt);
    if (!$hte) die("HTE not found.");

    if (!$is_superadmin) {
        $can_access = ($hte['moa_specify'] == 'ASCOT (Generalized)') || ($hte['moa_specify'] == $current_advisor_department);
        if (!$can_access) die("You do not have permission to edit this HTE.");
    }

    $students = $_POST['students'];
    $success_count = 0;
    $errors = [];

    foreach ($students as $index => $student_data) {
        if (empty($student_data['student_name']) || empty($student_data['gender'])) {
            $errors[] = "Row " . ($index + 1) . ": Name and gender required.";
            continue;
        }

        $student_id = isset($student_data['id']) ? (int)$student_data['id'] : 0;
        if ($student_id == 0) {
            $errors[] = "Row " . ($index + 1) . ": Missing student ID.";
            continue;
        }

        if (!$is_superadmin) {
            $perm_stmt = mysqli_prepare($conn, "SELECT advisor_id, school FROM students WHERE student_id = ?");
            mysqli_stmt_bind_param($perm_stmt, "i", $student_id);
            mysqli_stmt_execute($perm_stmt);
            $perm_res = mysqli_stmt_get_result($perm_stmt);
            $student_info = mysqli_fetch_assoc($perm_res);
            mysqli_stmt_close($perm_stmt);
            if (!$student_info || $student_info['advisor_id'] != $current_advisor_id) {
                $errors[] = "Row " . ($index + 1) . ": Permission denied.";
                continue;
            }
            $student_school = $student_info['school'];
        } else {
            $student_school = isset($student_data['school']) ? trim($student_data['school']) : '';
        }

        $student_name = trim($student_data['student_name']);
        $gender = trim($student_data['gender']);
        $extra_notes = trim($student_data['extra_notes'] ?? '');
        $school = trim($student_data['school'] ?? '');
        $program = trim($student_data['program'] ?? '');
        $academic_year = isset($student_data['academic_year']) ? (int)$student_data['academic_year'] : 0;
        $semester = trim($student_data['semester'] ?? '');
        $internship_start = !empty($student_data['internship_start']) ? trim($student_data['internship_start']) : null;
        $internship_end = !empty($student_data['internship_end']) ? trim($student_data['internship_end']) : null;
        $new_hte_id = isset($student_data['hte_id']) ? (int)$student_data['hte_id'] : $hte_id;

        if ($academic_year < 2020 || $academic_year > 2100) {
            $errors[] = "Row " . ($index + 1) . ": Invalid academic year."; continue;
        }
        if (!in_array($semester, ['1st Semester', '2nd Semester', 'Midyear'])) {
            $errors[] = "Row " . ($index + 1) . ": Invalid semester."; continue;
        }
        if (!empty($program) && $student_school) {
            if (!isset($programs_by_school[$student_school]) || !in_array($program, $programs_by_school[$student_school])) {
                $errors[] = "Row " . ($index + 1) . ": Program not allowed for school $student_school."; continue;
            }
        }
        if ($internship_start && $internship_end && $internship_start > $internship_end) {
            $errors[] = "Row " . ($index + 1) . ": Internship start date cannot be after end date."; continue;
        }

        if ($is_superadmin) {
            $has_reg = isset($student_data['has_registration_form']) ? 1 : 0;
            $has_med = isset($student_data['has_medical_certificate']) ? 1 : 0;
            $has_psycho = isset($student_data['has_psycho_test']) ? 1 : 0;
            $has_pc = isset($student_data['has_notary_p_c']) ? 1 : 0;
            $has_ta = isset($student_data['has_notary_t_a']) ? 1 : 0;
            $has_sipp = isset($student_data['has_sipp']) ? 1 : 0;
        } else {
            $preserve_stmt = mysqli_prepare($conn, "SELECT has_registration_form, has_medical_certificate, has_psycho_test, has_notary_p_c, has_notary_t_a, has_sipp FROM students WHERE student_id = ?");
            mysqli_stmt_bind_param($preserve_stmt, "i", $student_id);
            mysqli_stmt_execute($preserve_stmt);
            $preserve_res = mysqli_stmt_get_result($preserve_stmt);
            $preserve_row = mysqli_fetch_assoc($preserve_res);
            mysqli_stmt_close($preserve_stmt);
            $has_reg = $preserve_row['has_registration_form'];
            $has_med = $preserve_row['has_medical_certificate'];
            $has_psycho = $preserve_row['has_psycho_test'];
            $has_pc = $preserve_row['has_notary_p_c'];
            $has_ta = $preserve_row['has_notary_t_a'];
            $has_sipp = $preserve_row['has_sipp'];
        }

        $stmt = mysqli_prepare($conn, "UPDATE students SET 
                    student_name=?, school=?, program=?, gender=?,
                    has_registration_form=?, has_medical_certificate=?, has_psycho_test=?,
                    has_notary_p_c=?, has_notary_t_a=?, has_sipp=?, extra_notes=?,
                    academic_year=?, semester=?, internship_start=?, internship_end=?,
                    hte_id=?
                    WHERE student_id=?");
        mysqli_stmt_bind_param($stmt, "ssssiiiiiisisssii",
            $student_name, $school, $program, $gender,
            $has_reg, $has_med, $has_psycho, $has_pc, $has_ta, $has_sipp, $extra_notes,
            $academic_year, $semester, $internship_start, $internship_end, $new_hte_id,
            $student_id);

        if (mysqli_stmt_execute($stmt)) {
            $success_count++;
        } else {
            $errors[] = "Row " . ($index + 1) . ": DB error - " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }

    regenerateCsrfToken();
    $redirect = "index.php?hte_id=$hte_id";
    if ($success_count > 0) $redirect .= "&msg=" . urlencode("Updated $success_count students.");
    if (!empty($errors)) $redirect .= "&error=" . urlencode(implode("; ", $errors));
    header("Location: $redirect");
    exit;
}

// ========== BATCH ADD (New Students) ==========
if (isset($_POST['students']) && is_array($_POST['students'])) {
    $hte_id = isset($_POST['hte_id']) ? (int)$_POST['hte_id'] : 0;
    if ($hte_id <= 0) die("Error: Invalid HTE selected.");

    $hte_stmt = mysqli_prepare($conn, "SELECT moa_specify FROM host_training_establishment WHERE hte_id = ?");
    mysqli_stmt_bind_param($hte_stmt, "i", $hte_id);
    mysqli_stmt_execute($hte_stmt);
    $hte_result = mysqli_stmt_get_result($hte_stmt);
    $hte = mysqli_fetch_assoc($hte_result);
    mysqli_stmt_close($hte_stmt);
    if (!$hte) die("HTE not found.");

    // Determine advisor_id and department for this batch
    if ($is_superadmin) {
        $batch_advisor_id = isset($_POST['batch_advisor_id']) ? (int)$_POST['batch_advisor_id'] : 0;
        if ($batch_advisor_id <= 0) die("Error: Please select an advisor.");

        // Fetch advisor's department
        $adv_stmt = mysqli_prepare($conn, "SELECT department FROM users WHERE advisor_id = ?");
        mysqli_stmt_bind_param($adv_stmt, "i", $batch_advisor_id);
        mysqli_stmt_execute($adv_stmt);
        $adv_res = mysqli_stmt_get_result($adv_stmt);
        $adv_row = mysqli_fetch_assoc($adv_res);
        mysqli_stmt_close($adv_stmt);
        if (!$adv_row) die("Advisor not found.");
        $batch_advisor_department = $adv_row['department'];
    } else {
        $batch_advisor_id = $current_advisor_id;
        $batch_advisor_department = $current_advisor_department;

        $can_access = ($hte['moa_specify'] == 'ASCOT (Generalized)') || ($hte['moa_specify'] == $current_advisor_department);
        if (!$can_access) die("You do not have permission to add students to this HTE.");
    }

    $batch_school = trim($_POST['batch_school']);
    $batch_program = trim($_POST['batch_program']);
    $batch_academic_year = isset($_POST['batch_academic_year']) ? (int)$_POST['batch_academic_year'] : 0;
    $batch_semester = trim($_POST['batch_semester']);
    $batch_internship_start = !empty($_POST['batch_internship_start']) ? trim($_POST['batch_internship_start']) : null;
    $batch_internship_end = !empty($_POST['batch_internship_end']) ? trim($_POST['batch_internship_end']) : null;

    if (empty($batch_school) || empty($batch_program)) {
        die("Error: Please select a school and program.");
    }
    if ($batch_academic_year < 2020 || $batch_academic_year > 2100) {
        die("Error: Invalid academic year.");
    }
    if (!in_array($batch_semester, ['1st Semester', '2nd Semester', 'Midyear'])) {
        die("Error: Invalid semester.");
    }
    if (!isset($programs_by_school[$batch_school]) || !in_array($batch_program, $programs_by_school[$batch_school])) {
        die("Error: Invalid program for the selected school.");
    }

    // Create batch record
    $stmt_batch = mysqli_prepare($conn, "INSERT INTO student_batches (hte_id, academic_year, semester, created_by) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt_batch, "iisi", $hte_id, $batch_academic_year, $batch_semester, $batch_advisor_id);
    mysqli_stmt_execute($stmt_batch);
    $batch_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt_batch);

    $school_year_str = $batch_academic_year . '-' . ($batch_academic_year + 1);

    $students = $_POST['students'];
    $success_count = 0;
    $errors = [];

    foreach ($students as $index => $student_data) {
        if (empty($student_data['student_name']) || empty($student_data['gender'])) {
            $errors[] = "Row " . ($index + 1) . ": Name and gender required.";
            continue;
        }

        $student_name = trim($student_data['student_name']);
        $gender = trim($student_data['gender']);
        $extra_notes = trim($student_data['extra_notes'] ?? '');

        if ($is_superadmin) {
            $has_reg = isset($student_data['has_registration_form']) ? 1 : 0;
            $has_med = isset($student_data['has_medical_certificate']) ? 1 : 0;
            $has_psycho = isset($student_data['has_psycho_test']) ? 1 : 0;
            $has_pc = isset($student_data['has_notary_p_c']) ? 1 : 0;
            $has_ta = isset($student_data['has_notary_t_a']) ? 1 : 0;
            $has_sipp = isset($student_data['has_sipp']) ? 1 : 0;
        } else {
            $has_reg = 0;
            $has_med = 0;
            $has_psycho = 0;
            $has_pc = 0;
            $has_ta = 0;
            $has_sipp = 0;
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO students 
                    (student_name, school, program, gender, 
                     has_registration_form, has_medical_certificate, has_psycho_test,
                     has_notary_p_c, has_notary_t_a, has_sipp, extra_notes,
                     hte_id, school_year, academic_year, semester, advisor_id, department, 
                     internship_start, internship_end, batch_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssiiiiiisisisisssi",
            $student_name, $batch_school, $batch_program, $gender,
            $has_reg, $has_med, $has_psycho, $has_pc, $has_ta, $has_sipp, $extra_notes,
            $hte_id, $school_year_str, $batch_academic_year, $batch_semester,
            $batch_advisor_id, $batch_advisor_department,
            $batch_internship_start, $batch_internship_end, $batch_id);

        if (mysqli_stmt_execute($stmt)) {
            $success_count++;
        } else {
            $errors[] = "Row " . ($index + 1) . ": DB error - " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }

    regenerateCsrfToken();
    $redirect = "index.php?hte_id=$hte_id";
    if ($success_count > 0) $redirect .= "&msg=" . urlencode("Added $success_count students.");
    if (!empty($errors)) $redirect .= "&error=" . urlencode(implode("; ", $errors));
    header("Location: $redirect");
    exit;
}

die("Invalid request.");
?>