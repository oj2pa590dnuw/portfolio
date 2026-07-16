<?php
// CRITICAL: Start session and enforce login before doing anything
require 'session_utils.php';
// Use API mode (true) to ensure JSON response on failure and rate limiting
enforce_login(true);

// Set content type to JSON
header('Content-Type: application/json');

// Include DB connection utility
require_once 'db_con.php';

// 1. Get the password from the POST body (sent as JSON)
$data = json_decode(file_get_contents("php://input"), true);
$password_input = $data['password'] ?? '';

if (empty($password_input)) {
   http_response_code(400); // Bad Request
   die(json_encode(["success" => false, "error" => "Password required."]));
}

// 2. Get the current user's password hash from the database
$user_id = $_SESSION['user_id'];
// Fetch only the password for the logged-in user
$sql = "SELECT password FROM users WHERE id = ?";

if ($stmt = $conn->prepare($sql)) {
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $result = $stmt->get_result();

   if ($result->num_rows === 1) {
      $user = $result->fetch_assoc();
      $hashed_password = $user['password'];

      // 3. Securely verify the password
      if (password_verify($password_input, $hashed_password)) {
         // Password is correct!
         $stmt->close();
         $conn->close();
         echo json_encode(["success" => true]);
         exit;
      }
   }

   // If no user found (shouldn't happen if enforce_login worked) or password failed
   $stmt->close();
}

// If the script reaches here, the check failed.
$conn->close();
http_response_code(401); // Unauthorized
echo json_encode(["success" => false, "error" => "Invalid credentials or verification failed."]);
exit;