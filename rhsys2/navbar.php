<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
   session_start();
}

// Get user role from session
$is_admin = $_SESSION['is_admin'] ?? false;
$is_midwife = $_SESSION['is_midwife'] ?? false;
$is_bhw = $_SESSION['is_bhw'] ?? false;
$is_bns = $_SESSION['is_bns'] ?? false;

// Function to check if current page is active
function isActivePage($pageName)
{
   $currentPage = basename($_SERVER['PHP_SELF']);
   return $currentPage === $pageName ? 'active' : '';
}

// Function to check if the Users dropdown parent should be active
function isActiveUsersParent()
{
   $currentPage = basename($_SERVER['PHP_SELF']);
   // Pages under the Users dropdown
   $userPages = ['account_settings.php', 'users_page.php'];
   // Return 'active' if the current page is one of the dropdown's children
   return in_array($currentPage, $userPages) ? 'active' : '';
}

// 💥 NEW: Function to check if the Backup/Restore parent should be active
function isActiveBackupParent()
{
   $currentPage = basename($_SERVER['PHP_SELF']);
   // Pages under the Backup/Restore dropdown
   $backupPages = ['create_backup.php', 'restore_backup.php', 'activity_log.php']; // Added activity_log.php
   // Return 'active' if the current page is one of the dropdown's children
   return in_array($currentPage, $backupPages) ? 'active' : '';
}
?>

<nav class="top-navbar">
   <button class="nav-toggle" onclick="toggleMobileMenu()">☰</button>
   <div class="nav-brand"> RHSYS - Zabali BHC</div>
</nav>

<div id="sidebar">

   <nav>
      <a href="dashboard.php" class="<?php echo isActivePage('dashboard.php'); ?>">📊 Dashboard</a>
      <a href="patients_list.php" class="<?php echo isActivePage('patients_list.php'); ?>">👥 Patients</a>
      <a href="combined_visits.php" class="<?php echo isActivePage('combined_visits.php'); ?>">📅 Daily Visits</a>

      <?php if ($is_admin || $is_midwife || $is_bhw): ?>
         <a href="inventory.php" class="<?php echo isActivePage('inventory.php'); ?>">📦 Inventory</a>
      <?php endif; ?>

      <?php if ($is_admin || $is_midwife || $is_bns): ?>
         <a href="buntis.php" class="<?php echo isActivePage('buntis.php'); ?>">🤰 Pregnancy/Infant</a>
      <?php endif; ?>

      <?php if ($is_admin || $is_midwife || $is_bhw || $is_bns): ?>
         <a href="schedule.php" class="<?php echo isActivePage('schedule.php'); ?>">🗓️ Schedules</a>
      <?php endif; ?>

      <?php
      // 1. Users & Settings Dropdown (Available to all staff)
      if ($is_admin || $is_midwife || $is_bhw || $is_bns):
         ?>
         <div class="nav-dropdown">
            <a href="javascript:void(0);" class="dropdown-toggle <?php echo isActiveUsersParent(); ?>"
               onclick="toggleDropdown(this)">
               👤 Users & Settings <span>▼</span>
            </a>
            <div class="dropdown-content <?php echo isActiveUsersParent() ? 'show' : ''; ?>">
               <a href="account_settings.php" class="<?php echo isActivePage('account_settings.php'); ?>">
                  My Account Settings
               </a>

               <?php
               // This link is only for Admins and Midwives
               if ($is_admin || $is_midwife):
                  ?>
                  <a href="users_page.php" class="<?php echo isActivePage('users_page.php'); ?>">
                     User Approvals & Management
                  </a>
               <?php endif; ?>
            </div>
         </div>
      <?php endif; ?>

      <?php
      // 2. System Management Dropdown (Available only to Admins and Midwives)
      if ($is_admin || $is_midwife):
         ?>
         <div class="nav-dropdown">
            <a href="javascript:void(0);" class="dropdown-toggle <?php echo isActiveBackupParent(); ?>"
               onclick="toggleDropdown(this)">
               ⚙️ System Management <span>▼</span>
            </a>
            <div class="dropdown-content <?php echo isActiveBackupParent() ? 'show' : ''; ?>">
               <a href="create_backup.php" class="<?php echo isActivePage('create_backup.php'); ?>">
                  📦 Create Backup
               </a>
               <a href="restore_backup.php" class="<?php echo isActivePage('restore_backup.php'); ?>">
                  🔄 Restore Data
               </a>
               <a href="activity_log.php" class="<?php echo isActivePage('activity_log.php'); ?>">
                  ❓View Activity Log
               </a>
            </div>
         </div>
      <?php endif; ?>

      <a href="likod/logout.php">🚪 Logout</a>
   </nav>
</div>

<style>
   /* ---------------------------------------------------- */
   /* NEW DROPDOWN STYLES */
   /* ---------------------------------------------------- */
   .nav-dropdown {
      position: relative;
      /* This makes sure the parent link looks like a normal nav link */
      width: 100%;
   }

   .nav-dropdown .dropdown-toggle {
      /* Ensures the toggle acts like a full-width link */
      display: flex;
      justify-content: space-between;
      align-items: center;
   }

   .nav-dropdown .dropdown-toggle span {
      transition: transform 0.3s ease;
      font-size: 0.8em;
      margin-right: 5px;
      /* The white color for the arrow */
      color: #000000ff;
   }

   /* Arrow rotation when the menu is open */
   .nav-dropdown .dropdown-toggle.active span {
      transform: rotate(180deg);
   }

   .nav-dropdown .dropdown-content {
      /* Start hidden and allow the JS to display it */
      display: none;
      flex-direction: column;
      padding-left: 15px;
      /* Gives the sub-links an indentation */
      /* A slightly different background is good for visual separation */
   }

   /* Sub-link styles (Ensures text is white/light) */
   .nav-dropdown .dropdown-content a {
      padding: 8px 10px 8px 10px;
      font-size: 0.9em;
      /* Makes the sub-links slightly smaller */
      color: #c2c2c2ff;
      /* Ensures the links match the requested white color */
   }

   /* Show the content when the 'show' class is applied by JavaScript or PHP */
   .nav-dropdown .dropdown-content.show {
      display: flex;
   }
</style>

<script>
   function toggleMobileMenu() {
      const sidebar = document.getElementById('sidebar');
      sidebar.classList.toggle('mobile-open');
   }

   /* * REMOVED ALL HOVER FUNCTIONS FOR SIDEBAR AND DROPDOWNS:
    * - hoverOpenSidebar()
    * - hoverCloseSidebar()
    * - hoverOpenDropdown()
    * - hoverCloseDropdown()
    */

   // Function to toggle the dropdown's visibility (now for BOTH desktop and mobile click)
   function toggleDropdown(element) {
      const content = element.nextElementSibling;

      // Find the closest parent nav-dropdown
      const parentDropdown = element.closest('.nav-dropdown');

      // Close other open dropdowns in the same sidebar
      document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
         if (dropdown !== parentDropdown) {
            const otherContent = dropdown.querySelector('.dropdown-content');
            const otherToggle = dropdown.querySelector('.dropdown-toggle');
            otherContent.classList.remove('show');
            otherToggle.classList.remove('active');
         }
      });

      // Toggle the clicked dropdown
      content.classList.toggle('show');
      element.classList.toggle('active');
   }

   // Close mobile menu when clicking outside (mobile only)
   document.addEventListener('click', function (event) {
      const sidebar = document.getElementById('sidebar');
      const navToggle = document.querySelector('.nav-toggle');

      if (window.innerWidth <= 768 && sidebar.classList.contains('mobile-open')) {
         if (!sidebar.contains(event.target) && !navToggle.contains(event.target)) {
            sidebar.classList.remove('mobile-open');
         }
      }

      // This handles closing dropdowns on outside click for ALL sizes
      const dropdowns = document.querySelectorAll('.nav-dropdown .dropdown-content.show');
      const isDropdownToggle = event.target.closest('.dropdown-toggle');

      dropdowns.forEach(dropdown => {
         // Check if the click was NOT on an active dropdown's toggle/content
         if (!dropdown.parentElement.contains(event.target) && !isDropdownToggle) {
            dropdown.classList.remove('show');
            const toggle = dropdown.previousElementSibling;
            toggle.classList.remove('active');
         }
      });
   });

   // Set up initial state of dropdowns on page load for better persistence.
   document.addEventListener('DOMContentLoaded', () => {
      // Find all dropdowns that are set to 'show' by PHP (i.e., the current active parent)
      document.querySelectorAll('.nav-dropdown .dropdown-content.show').forEach(content => {
         // Ensure the parent toggle gets the 'active' class on load if the content is open
         const toggle = content.previousElementSibling;
         if (toggle && !toggle.classList.contains('active')) {
            toggle.classList.add('active');
         }
      });
   });
</script>