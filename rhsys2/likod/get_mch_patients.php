<?php
require 'session_utils.php'; // <--- NEW UTILITY
enforce_login(true);

// Set CORS headers for API access
header('Content-Type: application/json');

// PATH CORRECTION: Assuming db_con.php is in the same directory
require 'db_con.php'; 

// 1. Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
   http_response_code(500); // Server Error
   die(json_encode(array("message" => "Connection failed: " . $conn->connect_error)));
}

// 2. Build and run the SQL query
// CRITICAL: Filter where 'isPregnant' is true (1). 
// You can add 'OR age < 18' later if you decide to track children/infants on this page.
$sql = "SELECT * FROM patients WHERE isPregnant = 1 OR age < 5 ORDER BY fullName ASC";
$result = $conn->query($sql);

$patients_array = array();

// 3. Process the results
if ($result->num_rows > 0) {
   // Loop through each row and add it to the array
   while ($row = $result->fetch_assoc()) {
      $patients_array[] = $row;
   }

   // Output the success response
   http_response_code(200);
   echo json_encode($patients_array);
} else {
   // No records found (This is expected if your query is filtered and empty)
   http_response_code(200);
   echo json_encode(array());
}

// 4. Close the connection
$conn->close();
// REMOVED THE CLOSING TAG ?>