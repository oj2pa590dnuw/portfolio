<?php
// 💥 NEW: CHECK FOR INCOMING MESSAGES VIA URL PARAMETERS
$message_type = $_GET['msg_type'] ?? '';
$message_text = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>

   <meta charset="UTF-8">
   <title>Register - Barangay Health System</title>

   <link rel="stylesheet" href="styles/style.css?v=<?php echo time(); ?>">

   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
   <div class="app-container">
      <div class="main-content">
         <div class="large-container">
            <div class="form-container" style="max-width: 500px; margin: 50px auto;">
               <h2>Barangay Health Worker Registration</h2>
               <form method="POST" action="likod/register.php" id="registrationForm">
                  <div class="form-item">
                     <input type="text" name="first_name" id="firstName" class="form-input"
                        placeholder="First Name (Letters only)"                         required pattern="[a-zA-Z\s]+"
                        title="First name must contain letters and spaces only."
                        onkeydown="return filterNameInput(event);"
                        onkeyup="validateName('firstName', 'firstNameError')">
                     <div id="firstNameError" class="error-message"></div>

                  </div>
                  <div class="form-item">
                     <input type="text" name="last_name" id="lastName" class="form-input"
                        placeholder="Last Name (Letters only)"                         required pattern="[a-zA-Z\s]+"
                        title="Last name must contain letters and spaces only."
                        onkeydown="return filterNameInput(event);" onkeyup="validateName('lastName', 'lastNameError')">
                     <div id="lastNameError" class="error-message"></div>

                  </div>
                  <div class="form-item">
                     <input type="email" name="email" id="emailField" class="form-input" placeholder="Email" required
                        onkeyup="validateEmail()">
                     <div id="emailError" class="error-message"></div>

                  </div>

                  <div class="form-item" style="position: relative;">
                     <input type="password" id="regPasswordField" name="password" class="form-input"                    
                            placeholder="Password (Min. 8 characters)" required minlength="8"
                        style="padding-right: 40px;" onkeyup="validatePassword()">
                     <button type="button" id="toggleRegPassword"                        
                        style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #888;">
                        <i id="regToggleIcon" class="fas fa-eye"></i>
                     </button>
                     <div id="passwordError" class="error-message"></div>

                  </div>

                  <div class="form-item">
                     <label class="form-label">Role Selection</label>
                     <div class="checkbox-grid">
                        <div class="checkbox-item">
                           <input type="radio" name="role" value="bns" id="role_bns" required>
                           <label for="role_bns">Barangay Nutrition Scholar
                              (BNS)</label>
                        </div>
                        <div class="checkbox-item">
                           <input type="radio" name="role" value="bhw" id="role_bhw">
                           <label for="role_bhw">Barangay Health Worker
                              (BHW)</label>
                        </div>
                        <div class="checkbox-item">
                           <input type="radio" name="role" value="midwife" id="role_midwife">
                           <label for="role_midwife">Midwife</label>
                        </div>
                     </div>
                  </div>

                  <div class="form-item">
                     <button type="submit" id="registerBtn" class="btn btn-primary"
                        style="width: 100%;">Register</button>
                  </div>
               </form>
               <p style="text-align: center; margin-top: 20px;">
                  Already have an account? <a href="login.php" class="action-link">Login
                     here</a>
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

   // --- Global Validation State ---
   const formFields = {
      firstName: false,
      lastName: false,
      email: false,
      password: false,
   };

   function checkFormValidity() {
      // Checks if ALL required fields are valid (true)
      const isFormValid = Object.values(formFields).every(val => val === true);
      const registerBtn = document.getElementById('registerBtn');

      // Disable the button if they're wrong!
      registerBtn.disabled = !isFormValid;
      registerBtn.style.opacity = isFormValid ? '1' : '0.5';
      registerBtn.title = isFormValid ? '' : 'Fix the errors before registering!';
   }


   // --- 1. Name Input Validation (onkeyup) ---
   function validateName(fieldId, errorId) {
      const input = document.getElementById(fieldId);
      const errorDiv = document.getElementById(errorId);
      const value = input.value.trim();
      let isValid = false;

      // Check 1: Is it empty?
      if (value.length === 0) {
         errorDiv.textContent = '❌ This field cannot be empty.';
      }
      // Check 2: Does it contain ONLY letters and spaces? (The pattern we used)
      else if (!/^[a-zA-Z\s]+$/.test(value)) {
         errorDiv.textContent = '❌ Only letters and spaces are allowed.';
      }
      // Check 3: Is it too short? (Basic length check)
      else if (value.length < 2) {
         errorDiv.textContent = '❌ Name must be at least 2 characters.';
      }
      // Success
      else {
         errorDiv.textContent = '';
         isValid = true;
      }

      // Apply error styling and update global state
      input.classList.toggle('error', !isValid);
      formFields[fieldId] = isValid;
      checkFormValidity();
      return isValid;
   }

   // --- 2. Name Input Filter (onkeydown) ---
   function filterNameInput(event) {
      const key = event.key;

      // Allow special keys like Backspace, Delete, Tab, Arrows, etc.
      if (event.ctrlKey || event.altKey || event.metaKey || ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight']
         .includes(key)) {
         return true;
      }

      // Use a Regular Expression to test if the key is a letter (a-z, A-Z) or a space
      const isAllowed = /^[a-zA-Z\s]$/.test(key);

      if (!isAllowed) {
         event.preventDefault();
      }

      return isAllowed;
   }

   // --- 3. Email Validation (onkeyup) ---
   function validateEmail() {
      const input = document.getElementById('emailField');
      const errorDiv = document.getElementById('emailError');
      const value = input.value.trim();
      let isValid = false;

      // Simple regex check for email pattern
      const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (value.length === 0) {
         errorDiv.textContent = '❌ Email is required.';
      } else if (!emailPattern.test(value)) {
         errorDiv.textContent = '❌ Please enter a valid email address (e.g., user@domain.com).';
      } else {
         errorDiv.textContent = '';
         isValid = true;
      }

      input.classList.toggle('error', !isValid);
      formFields.email = isValid;
      checkFormValidity();
      return isValid;
   }

   // --- 4. Password Validation (onkeyup) ---
   function validatePassword() {
      const input = document.getElementById('regPasswordField');
      const errorDiv = document.getElementById('passwordError');
      const value = input.value;
      let isValid = false;
      let errors = [];

      // Rules check
      const minLength = 8;
      const hasNumber = /[0-9]/.test(value);
      const hasUpper = /[A-Z]/.test(value);
      const hasLower = /[a-z]/.test(value);
      const hasSpecial = /[!@#$%^&*()_+={}\[\]|\\:;"'<>,.?/~`-]/.test(value);

      // Check 1: Length
      if (value.length < minLength) {
         errors.push(`Length must be ${minLength} characters or more.`);
      }

      // Check 2: Complexity (Adding basic strength check as an extra hurdle for panelists)
      if (!hasNumber) {
         errors.push('Must contain at least one number.');
      }
      if (!hasUpper) {
         errors.push('Must contain at least one uppercase letter.');
      }
      if (!hasSpecial) {
         errors.push('Must contain at least one special character.');
      }

      if (errors.length > 0) {
         errorDiv.innerHTML = '❌ Weak Password: ' + errors.join(' | ');
         errorDiv.classList.remove('password-success');
      } else {
         errorDiv.textContent = '✅ Strong password!';
         errorDiv.classList.add('password-success'); // Use the success class from style.css
         isValid = true;
      }

      input.classList.toggle('error', !isValid);
      formFields.password = isValid;
      checkFormValidity();
      return isValid;
   }

   // JavaScript for the Password Toggle
   const toggleRegPassword = document.getElementById('toggleRegPassword');
   const regPasswordField = document.getElementById('regPasswordField');
   const regToggleIcon = document.getElementById('regToggleIcon');

   // Initial check when the page loads
   document.addEventListener('DOMContentLoaded', checkFormValidity);

   if (toggleRegPassword) {
      toggleRegPassword.addEventListener('click', function(e) {
         // Toggle the type attribute
         const type = regPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
         regPasswordField.setAttribute('type', type);

         // Toggle the eye icon
         if (regToggleIcon.classList.contains('fa-eye')) {
            regToggleIcon.classList.remove('fa-eye');
            regToggleIcon.classList.add('fa-eye-slash');
         } else {
            regToggleIcon.classList.remove('fa-eye-slash');
            regToggleIcon.classList.add('fa-eye');
         }
      });
   }

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