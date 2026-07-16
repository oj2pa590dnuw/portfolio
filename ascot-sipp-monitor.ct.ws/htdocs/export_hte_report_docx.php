<?php
require_once 'db.php';
require_once 'school_programs.php';

if (!isset($_SESSION['advisor_id']) || $_SESSION['role'] != 'superadmin') {
    header("Location: login.php");
    exit;
}

// Fetch HTEs - exclude Local, only Private and Public
$sql = "SELECT * FROM host_training_establishment 
        WHERE hte_type IN ('Private', 'Public') 
        ORDER BY moa_specify, hte_name";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$htes_by_school = [];
while ($row = mysqli_fetch_assoc($result)) {
    $school_code = $row['moa_specify'];
    if (!isset($htes_by_school[$school_code])) {
        $htes_by_school[$school_code] = [];
    }
    $htes_by_school[$school_code][] = $row;
}
mysqli_stmt_close($stmt);

$filename = "HTE_Report_" . date('Y-m-d') . ".docx";

// Create temporary directory
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

// Root Relationships
$rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>';
file_put_contents($temp_dir . '/_rels/.rels', $rels);

// Word Relationships
$word_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';
file_put_contents($temp_dir . '/word/_rels/document.xml.rels', $word_rels);

// Core Properties
$core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dc:title>HTE Report</dc:title>
    <dc:subject>Host Training Establishments</dc:subject>
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
                <w:sz w:val="28"/> <!-- 14pt = 18 half-points -->
            </w:rPr>
        </w:rPrDefault>
    </w:docDefaults>
</w:styles>';
file_put_contents($temp_dir . '/word/styles.xml', $styles);

// Build document.xml body
$body_xml = '';
$has_data = false;

foreach ($htes_by_school as $school_code => $htes) {
    if (empty($htes)) continue;
    $has_data = true;
    $school_name = isset($schools[$school_code]) ? $schools[$school_code] : $school_code;
    
    // School header
    $body_xml .= '
    <w:p>
            <w:pPr><w:jc w:val="center"/></w:pPr>
            <w:r><w:rPr><w:b/><w:sz w:val="40"/></w:rPr><w:t>LIST OF HOST TRAINING ESTABLISHMENTS</w:t></w:r>
        </w:p>
        <w:p>
            <w:pPr><w:jc w:val="center"/></w:pPr>
            <w:r><w:rPr><w:b/><w:sz w:val="40"/></w:rPr><w:t> </w:t></w:r>
        </w:p>
        <w:p>
            <w:pPr><w:jc w:val="left"/></w:pPr>
            <w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t>' . htmlspecialchars($school_name, ENT_XML1) . '</w:t></w:r>
        </w:p>
        <w:p><w:r><w:br/></w:r></w:p>';
    
    // Table with full width (percentage)
    $body_xml .= '
        <w:tbl>
            <w:tblPr>
                <w:tblStyle w:val="TableGrid"/>
                <w:tblW w:w="5000" w:type="pct"/>
                <w:tblLook w:val="04A0"/>
            </w:tblPr>
            <w:tblGrid>
                <w:gridCol w:w="3118"/> <!-- HTE Name -->
                <w:gridCol w:w="2551"/> <!-- Representative -->
                <w:gridCol w:w="2268"/> <!-- Contact -->
                <w:gridCol w:w="3402"/> <!-- Address -->
                <w:gridCol w:w="2835"/> <!-- MOA Duration -->
            </w:tblGrid>
            <w:tr>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>HTE Name</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>Representative</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>Contact Number</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>Address</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/></w:rPr><w:t>MOA Duration</w:t></w:r></w:p></w:tc>
            </w:tr>';
    
    foreach ($htes as $hte) {
        // ← CHANGED: Handle missing dates – show empty string if either date is NULL
        if (!empty($hte['start_memo_of_agreement']) && !empty($hte['end_memo_of_agreement'])) {
            $duration = date('M d, Y', strtotime($hte['start_memo_of_agreement'])) . ' - ' . date('M d, Y', strtotime($hte['end_memo_of_agreement']));
        } else {
            $duration = '';
        }
        
        $body_xml .= '
            <w:tr>
                <w:tc><w:p><w:r><w:t>' . htmlspecialchars($hte['hte_name'], ENT_XML1) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . htmlspecialchars($hte['hte_representative'], ENT_XML1) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . htmlspecialchars($hte['contact_number'], ENT_XML1) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . htmlspecialchars($hte['address'], ENT_XML1) . '</w:t></w:r></w:p></w:tc>
                <w:tc><w:p><w:r><w:t>' . htmlspecialchars($duration, ENT_XML1) . '</w:t></w:r></w:p></w:tc>
            </w:tr>';
    }
    
    $body_xml .= '
        </w:tbl>
        <w:p><w:r><w:br w:type="page"/></w:r></w:p>';
}

if (!$has_data) {
    $body_xml = '
        <w:p>
            <w:pPr><w:jc w:val="center"/></w:pPr>
            <w:r><w:t>No Private or Public Host Training Establishments found.</w:t></w:r>
        </w:p>';
}

// Wrap in document with LONG BOND paper (8.5" x 13") landscape & normal 1" margins
// ← CHANGED: Page size to 18720 x 12240 (13" wide x 8.5" high) and margins to 1440 (1 inch)
$document_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p><w:r><w:br/></w:r></w:p>
        ' . $body_xml . '
        <w:sectPr>
            <w:pgSz w:w="18720" w:h="12240" w:orient="landscape"/> <!-- Long Bond landscape (13" x 8.5") -->
            <w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="0" w:footer="0" w:gutter="0"/>
        </w:sectPr>
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