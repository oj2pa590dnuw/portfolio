<?php
require 'session_utils.php';
enforce_login(true);
header('Content-Type: application/json');
require 'db_con.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Connection failed"]);
    exit;
}

// Get last 30 days of visits or adjust as needed
$sql = "SELECT * FROM daily_visits WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY visit_date DESC";
$result = $conn->query($sql);

$visits = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $visits[] = $row;
    }
}

$conn->close();
echo json_encode($visits);
?>