<div class="navbar">
    <div class="navbar-left">
        <div class="logo">SIPP OJT Monitor</div>
        <div class="user-welcome">
            Welcome, <?= htmlspecialchars($_SESSION['advisor_name'] ?? 'Guest') ?>
        </div>
    </div>
    <div class="nav-links">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        $nav_items = [
            'index.php' => 'Students',
            'hte_list.php' => 'HTEs',
            'stats.php' => 'Statistics',
            'missing_requirements.php' => 'Missing Reqs'
        ];
        if (isset($_SESSION['role']) && $_SESSION['role'] == 'superadmin') {
            $nav_items['users_list.php'] = 'User Management';
        } else {
            $nav_items['users_list.php'] = 'Profile';
        }
        $nav_items['logout.php'] = 'Logout';
        ?>
        <?php foreach ($nav_items as $file => $label): ?>
            <a href="<?= $file ?>" class="<?= ($current_page == $file) ? 'active' : '' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>