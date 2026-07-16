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

$sql = "SELECT * FROM patients";
$result = $conn->query($sql);

$patients = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}

$conn->close();
echo json_encode($patients);
?>