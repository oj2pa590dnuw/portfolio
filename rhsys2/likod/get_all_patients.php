<?php
require 'session_utils.php';
enforce_login(true);

header('Content-Type: application/json');
require 'db_con.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(array("error" => "Database connection failed")));
}

$sql = "SELECT id, fullName, patient_code FROM patients ORDER BY fullName";
$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    die(json_encode(array("error" => "Query failed")));
}

$patients = array();
while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}

echo json_encode($patients);
$conn->close();
?>