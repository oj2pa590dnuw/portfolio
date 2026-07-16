<?php
// 💥 NEW: CHECK FOR INCOMING MESSAGES VIA URL PARAMETERS
$message_type = $_GET['msg_type'] ?? '';
$message_text = $_GET['msg'] ?? '';
// Note: We don't clear the parameters here, we let the browser redirect handle it.
?>
<!DOCTYPE html>
<html lang="en">

<head>

   <meta charset="UTF-8">

   <title>Login - Barangay Health System</title>

   <link rel="stylesheet" href="styles/style.css">


   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
   <div class="app-container">
      <div class="main-content">
         <div class="large-container">
            <div class="form-container" style="max-width: 400px; margin: 50px auto;">
               <h1>🏥 RHSYS - Zabali BHC</h1>
               <h2>Login to Your Account</h2>
               <form method="POST" action="likod/login.php">
                  <div class="form-item">
                     <input type="email" name="email" class="form-input" placeholder="Email" required>
                  </div>

                  <div class="form-item" style="position: relative;">
                     <input type="password" id="passwordField"                             name="password"              
                        class="form-input"                             placeholder="Password (Min. 8 characters)"    
                        required         minlength="8"                             style="padding-right: 40px;"        >
                     <button type="button"                   id="togglePassword"                            
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #888;">
                        <i id="toggleIcon" class="fas fa-eye"></i>
                     </button>
                  </div>

                  <div class="form-item">
                     <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                  </div>
               </form>
               <p style="text-align: center; margin-top: 20px;">
                  No account yet? <a href="registerform.php" class="action-link">Register
                     here</a>
               </p>
               <p style="text-align: center; margin-top: 20px;">
                  <a href="https://drive.google.com/file/d/168K_GZqvI76j1jezTkBEDLw5d4uRPWM6/view?usp=sharing">Download
                     the Android Application</a>
               </p>
            </div>
         </div>
      </div>
   </div>

   <div id="messageBox" class="message-box"></div>

   <script>
      // 💥 NEW: UNIVERSAL MESSAGE DISPLAY FUNCTION (Uses classes from style.css)
      function showMessage(type, message, redirectUrl = null) {
         const messageBox = document.getElementById('messageBox');

         // Ensure the box is clear before adding new content
         messageBox.className = 'message-box';
         messageBox.innerHTML = '';

         if (message.length > 0) {
            // Apply appropriate styling
            if (type === 'success') {
               messageBox.classList.add('message-success');
            } else if (type === 'error' || type === 'warning') {
               // Use error style for both warnings and critical errors
               messageBox.classList.add('message-error');
            }

            messageBox.innerHTML = `<div>${message}</div>`;
            messageBox.classList.add('show');

            // Optionally redirect after a short delay
            if (redirectUrl) {
               setTimeout(() => {
                  messageBox.classList.remove('show');
                  window.location.href = redirectUrl;
               }, 4000); // Wait 4 seconds to let the user read the important message
            }
         }
      }

      const togglePassword = document.getElementById('togglePassword');
      const passwordField = document.getElementById('passwordField');
      const toggleIcon = document.getElementById('toggleIcon');

      togglePassword.addEventListener('click', function (e) {
         // Toggle the type attribute
         const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
         passwordField.setAttribute('type', type);

         // Toggle the eye icon
         if (toggleIcon.classList.contains('fa-eye')) {
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
         } else {
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
         }
      });

      // 💥 NEW: PHP-to-JavaScript communication to display the message
      <?php if ($message_type && $message_text): ?>
         showMessage('<?php echo $message_type; ?>', '<?php echo htmlspecialchars($message_text); ?>');

         // Optional: Clear the URL bar of the query parameters after displaying (for clean look)
         if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.pathname);
         }
      <?php endif; ?>
   </script>

</body>

</html>