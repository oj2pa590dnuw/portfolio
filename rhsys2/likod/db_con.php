<?php
// Define a function to handle JSON errors and safely die
if (!function_exists('return_json_error')) {
    function return_json_error($code, $message, $conn = null)
    {
        if ($conn && $conn->connect_error) {
            $message .= " Connection Error: " . $conn->connect_error;
        } elseif ($conn && $conn->error) {
            $message .= " DB Error: " . $conn->error;
        }
        http_response_code($code);
        die(json_encode(array("message" => $message)));
    }
}

// db_con.php
// Database Hostname (Usually 'localhost' or '127.0.0.1' for local access)
$servername = "localhost";

// Database Username (The default is often 'root')
$username = "root";

// Database Password (The default is often an empty string '')
$password = "";

// Database Name (This will be the name you create in phpMyAdmin on your local server)
$dbname = "system2";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// If the connection attempt fails, die gracefully
if ($conn->connect_error) {
    return_json_error(500, "Database connection failed. Please check credentials.", $conn);
}
// Omit closing tag (best practice)