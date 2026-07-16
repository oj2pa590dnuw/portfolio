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

$semester = isset($_GET['semester']) ? trim($_GET['semester']) : '';
$academic_year = isset($_GET['academic_year']) ? (int)$_GET['academic_year'] : 0;
$export_school = isset($_GET['school']) ? trim($_GET['school']) : '';

if (empty($semester) || $academic_year == 0) {
    die("Semester and Academic Year are required.");
}

// Build display string
$school_year_label = $academic_year . '-' . ($academic_year + 1);

// Build query
if ($is_superadmin) {
    $conditions = ["s.semester = ?", "s.academic_year = ?"];
    $params = [$semester, $academic_year];
    $types = "si";
    if (!empty($export_school)) {
        $conditions[] = "s.school = ?";
        $params[] = $export_school;
        $types .= "s";
    }
    $where = implode(" AND ", $conditions);
    $sql = "SELECT s.student_name, s.program, s.gender, s.school_year, s.semester,
                   s.has_registration_form, s.has_medical_certificate, s.has_psycho_test,
                   s.has_notary_p_c, s.has_notary_t_a, s.has_sipp,
                   s.internship_start, s.internship_end,
                   h.hte_name, u.advisor_name, u.department AS advisor_department, s.school
            FROM students s
            LEFT JOIN host_training_establishment h ON s.hte_id = h.hte_id
            LEFT JOIN users u ON s.advisor_id = u.advisor_id
            WHERE $where
            ORDER BY h.hte_name ASC, s.student_name ASC";   // <-- SORT BY HTE NAME FIRST
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
} else {
    $sql = "SELECT s.student_name, s.program, s.gender, s.school_year, s.semester,
                   s.has_registration_form, s.has_medical_certificate, s.has_psycho_test,
                   s.has_notary_p_c, s.has_notary_t_a, s.has_sipp,
                   s.internship_start, s.internship_end,
                   h.hte_name, u.advisor_name, s.school
            FROM students s
            LEFT JOIN host_training_establishment h ON s.hte_id = h.hte_id
            LEFT JOIN users u ON s.advisor_id = u.advisor_id
            WHERE s.semester = ? AND s.academic_year = ? AND s.school = ?
            ORDER BY h.hte_name ASC, s.student_name ASC";   // <-- SORT BY HTE NAME FIRST
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sis", $semester, $academic_year, $advisor_department);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}
mysqli_stmt_close($stmt);

$filename = "SIPP_Report_" . $school_year_label . "_" . preg_replace('/[^A-Za-z0-9]/', '_', $semester);
if (!empty($export_school)) {
    $filename .= "_" . preg_replace('/[^A-Za-z0-9]/', '_', $export_school);
}
$filename .= ".docx";

// Create a temporary directory
$temp_dir = sys_get_temp_dir() . '/docx_' . uniqid();
mkdir($temp_dir, 0777, true);
mkdir($temp_dir . '/_rels');
mkdir($temp_dir . '/docProps');
mkdir($temp_dir . '/word');
mkdir($temp_dir . '/word/_rels');

// Content Types
$content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
    <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>';
file_put_contents($temp_dir . '/[Content_Types].xml', $content_types);

// Relationships
$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';
file_put_contents($temp_dir . '/_rels/.rels', $rels);

$word_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
file_put_contents($temp_dir . '/word/_rels/document.xml.rels', $word_rels);

// Core Properties
$core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:title>SIPP Report</dc:title>
    <dc:subject>Student Internship Program</dc:subject>
    <dc:creator>SIPP OJT Monitor</dc:creator>
    <cp:revision>1</cp:revision>
</cp:coreProperties>';
file_put_contents($temp_dir . '/docProps/core.xml', $core);

$app = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">
    <Application>SIPP OJT Monitor</Application>
    <Template>Normal.dotm</Template>
    <TotalTime>0</TotalTime>
    <Pages>1</Pages>
    <Words>0</Words>
    <Characters>0</Characters>
    <Lines>0</Lines>
    <Paragraphs>0</Paragraphs>
</Properties>';
file_put_contents($temp_dir . '/docProps/app.xml', $app);

// Styles (font size 9)
$styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:docDefaults>
        <w:rPrDefault>
            <w:rPr>
                <w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>
                <w:sz w:val="18"/>
            </w:rPr>
        </w:rPrDefault>
    </w:docDefaults>
</w:styles>';
file_put_contents($temp_dir . '/word/styles.xml', $styles);

// Helper for internship period
function formatInternshipPeriod($start, $end) {
    if (empty($start) && empty($end)) return '—';
    $start_str = !empty($start) ? date('M d, Y', strtotime($start)) : '';
    $end_str = !empty($end) ? date('M d, Y', strtotime($end)) : '';
    if (!empty($start_str) && !empty($end_str)) {
        return $start_str . ' - ' . $end_str;
    } elseif (!empty($start_str)) {
        return $start_str . ' - ';
    } else {
        return ' - ' . $end_str;
    }
}

// Document XML
$document_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:sectPr>
            <w:pgSz w:w="20160" w:h="12240" w:orient="landscape"/> <!-- Legal landscape -->
            <w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="0" w:footer="0" w:gutter="0"/>
        </w:sectPr>

        <!-- Header -->
        <w:p>
            <w:pPr><w:jc w:val="center"/></w:pPr>
            <w:r><w:rPr><w:b/></w:rPr><w:t>REPORT ON THE LIST OF HOST TRAINING ESTABLISHMENT (HTEs) AND STUDENT INTERNS</w:t></w:r>
        </w:p>
        <w:p>
            <w:pPr><w:jc w:val="center"/></w:pPr>
            <w:r><w:rPr><w:b/></w:rPr><w:t>PARTICIPATING IN THE STUDENT INTERNSHIP PROGRAM IN THE PHILIPPINES (SIPP)</w:t></w:r>
        </w:p>
        <w:p>
            <w:pPr><w:jc w:val="center"/></w:pPr>
            <w:r><w:t>' . htmlspecialchars($semester, ENT_XML1) . ', School Year ' . htmlspecialchars($school_year_label, ENT_XML1) . '</w:t></w:r>
        </w:p>
        <w:p>
            <w:r><w:rPr><w:b/></w:rPr><w:t>HEI :    AURORA STATE COLLEGE OF TECHNOLOGY</w:t></w:r>
        </w:p>
        <w:p>
            <w:r><w:rPr><w:b/></w:rPr><w:t>ADDRESS :    ZABALI, BALER, AURORA</w:t></w:r>
        </w:p>

        <!-- Table -->
        <w:tbl>
            <w:tblPr>
                <w:tblStyle w:val="TableGrid"/>
                <w:tblW w:w="5000" w:type="pct"/>
                <w:tblLook w:val="04A0"/>
            </w:tblPr>
            <w:tblGrid>
                <w:gridCol w:w="567"/>   <!-- No. -->
                <w:gridCol w:w="2268"/>  <!-- HTE -->
                <w:gridCol w:w="2268"/>  <!-- Student Name -->
                <w:gridCol w:w="1417"/>  <!-- Program -->
                <w:gridCol w:w="850"/>   <!-- Gender -->
                <w:gridCol w:w="2268"/>  <!-- Internship Period -->
                <w:gridCol w:w="567"/>   <!-- RF -->
                <w:gridCol w:w="567"/>   <!-- MC -->
                <w:gridCol w:w="567"/>   <!-- PT -->
                <w:gridCol w:w="567"/>   <!-- PC -->
                <w:gridCol w:w="567"/>   <!-- TA -->
                <w:gridCol w:w="567"/>   <!-- SIPP -->
            </w:tblGrid>

            <w:tr>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>No.</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>PARTNER HOST TRAINING ESTABLISHMENT</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>NAME OF STUDENT INTERNS</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>PROGRAM</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>GENDER</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>DATES OF DURATION OF THE INTERNSHIP PERIOD</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>RF</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>MC</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>PT</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>PC</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>TA</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>SIPP</w:t></w:r></w:p></w:tc>
            </w:tr>';

$counter = 1;
foreach ($students as $s) {
    $internship_dates = formatInternshipPeriod($s['internship_start'], $s['internship_end']);
    $hte_name = !empty($s['hte_name']) ? $s['hte_name'] : '—';
    $gender = !empty($s['gender']) ? $s['gender'] : '—';
    
    $document_xml .= '
            <w:tr>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>' . $counter++ . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . htmlspecialchars($hte_name, ENT_XML1) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . htmlspecialchars($s['student_name'], ENT_XML1) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . htmlspecialchars($s['program'], ENT_XML1) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>' . htmlspecialchars($gender, ENT_XML1) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . htmlspecialchars($internship_dates, ENT_XML1) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>' . ($s['has_registration_form'] ? '✅' : '❌') . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>' . ($s['has_medical_certificate'] ? '✅' : '❌') . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>' . ($s['has_psycho_test'] ? '✅' : '❌') . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>' . ($s['has_notary_p_c'] ? '✅' : '❌') . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>' . ($s['has_notary_t_a'] ? '✅' : '❌') . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>' . ($s['has_sipp'] ? '✅' : '❌') . '</w:t></w:r></w:p></w:tc>
            </w:tr>';
}

if (empty($students)) {
    $document_xml .= '
            <w:tr>
                <w:tc>
                    <w:tcPr><w:gridSpan w:val="12"/></w:tcPr>
                    <w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>No students found for this semester and academic year.</w:t></w:r></w:p>
                </w:tc>
            </w:tr>';
}

$document_xml .= '
        </w:tbl>
        <w:p>
            <w:pPr><w:jc w:val="right"/></w:pPr>
            <w:r><w:t>Generated on: ' . date('F d, Y') . '</w:t></w:r>
        </w:p>
    </w:body>
</w:document>';
file_put_contents($temp_dir . '/word/document.xml', $document_xml);

// Create ZIP
$zip = new ZipArchive();
$zip_filename = $temp_dir . '.docx';
if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Cannot create ZIP file.");
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);
foreach ($files as $name => $file) {
    $file_path = $file->getRealPath();
    $relative_path = substr($file_path, strlen($temp_dir) + 1);
    $zip->addFile($file_path, $relative_path);
}
$zip->close();

// Cleanup
array_map('unlink', glob("$temp_dir/*.*"));
foreach (glob("$temp_dir/*", GLOB_ONLYDIR) as $dir) {
    array_map('unlink', glob("$dir/*.*"));
    rmdir($dir);
}
rmdir($temp_dir);

// Send file
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($zip_filename));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
readfile($zip_filename);
unlink($zip_filename);
exit;
?>