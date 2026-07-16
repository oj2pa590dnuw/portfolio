<?php
require 'session_utils.php';
enforce_login(true);

header('Content-Type: application/json');
require 'db_con.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(array("error" => "Database connection failed: " . $conn->connect_error)));
}

if (!isset($_GET['patient_id']) || empty($_GET['patient_id'])) {
    http_response_code(400);
    die(json_encode(array("error" => "Patient ID is required")));
}

$patient_id = $conn->real_escape_string($_GET['patient_id']);

// Get current patient's chief complaint for similarity analysis
$current_patient_sql = "SELECT title FROM patients WHERE id = '$patient_id'";
$current_patient_result = $conn->query($current_patient_sql);
$current_complaint = '';
if ($current_patient_result->num_rows > 0) {
    $current_patient = $current_patient_result->fetch_assoc();
    $current_complaint = $current_patient['title'] ?? '';
}

// Check if patient_relationships table exists
$table_check = $conn->query("SHOW TABLES LIKE 'patient_relationships'");
if ($table_check->num_rows == 0) {
    echo json_encode(array("similarComplaints" => array(), "otherMembers" => array()));
    $conn->close();
    exit();
}

$sql = "SELECT 
            pr.relationship_id,
            pr.relationship_type,
            p.id as related_patient_id,
            p.fullName,
            p.age,
            p.birthDate,
            p.contactNumber,
            p.location,
            p.title as chief_complaint
        FROM patient_relationships pr
        JOIN patients p ON pr.related_patient_id = p.id
        WHERE pr.patient_id = '$patient_id'
        ORDER BY pr.relationship_type, p.fullName";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    die(json_encode(array("error" => "Query failed: " . $conn->error)));
}

$similarComplaints = array();
$otherMembers = array();

while ($row = $result->fetch_assoc()) {
    // Check if complaints are similar
    $isSimilar = false;
    if (!empty($current_complaint) && !empty($row['chief_complaint'])) {
        $isSimilar = areComplaintsSimilar($current_complaint, $row['chief_complaint']);
    }
    
    if ($isSimilar) {
        $similarComplaints[] = $row;
    } else {
        $otherMembers[] = $row;
    }
}

// Return grouped results
echo json_encode(array(
    "similarComplaints" => $similarComplaints,
    "otherMembers" => $otherMembers,
    "currentPatientComplaint" => $current_complaint
));

$conn->close();

// Simple similarity function - just check for common words
function areComplaintsSimilar($complaint1, $complaint2) {
    $complaint1 = strtolower(trim($complaint1));
    $complaint2 = strtolower(trim($complaint2));
    
    // If identical, definitely similar
    if ($complaint1 === $complaint2) {
        return true;
    }
    
    // Common medical terms that indicate similarity
    $medicalKeywords = [
        'fever', 'cough', 'headache', 'pain', 'hypertension', 'diabetes', 
        'asthma', 'pregnant', 'pregnancy', 'blood pressure', 'heart', 
        'respiratory', 'infection', 'flu', 'cold', 'allergy', 'arthritis'
    ];
    
    // Check if they share any medical keywords
    foreach ($medicalKeywords as $keyword) {
        if (strpos($complaint1, $keyword) !== false && 
            strpos($complaint2, $keyword) !== false) {
            return true;
        }
    }
    
    // Check for overlapping words (simple approach)
    $words1 = array_unique(preg_split('/\s+/', $complaint1));
    $words2 = array_unique(preg_split('/\s+/', $complaint2));
    
    $commonWords = array_intersect($words1, $words2);
    
    // If they share at least 2 meaningful words (excluding very short words)
    $meaningfulCommon = array_filter($commonWords, function($word) {
        return strlen($word) > 3;
    });
    
    return count($meaningfulCommon) >= 2;
}
?>